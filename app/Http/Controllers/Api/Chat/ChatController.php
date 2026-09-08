<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $companyId = $request->header('X-Company-Id') ?? $user->company_id;
            $perPage = min((int) $request->input('per_page', 15), 100);

            $conversations = Conversation::query()
                ->forCompany($companyId)
                ->forUser($user->id)
                ->with(['participants:id,name'])
                ->withCount([
                    'messages as latest_message_id' => function ($q) {
                        $q->select(DB::raw('MAX(id)'));
                    },
                ])
                ->orderByDesc('updated_at')
                ->paginate($perPage);

            $conversationIds = $conversations->pluck('id');
            $latestMessages = ChatMessage::whereIn('conversation_id', $conversationIds)
                ->select('id', 'conversation_id', 'body', 'user_id', 'created_at')
                ->orderByDesc('id')
                ->get()
                ->unique('conversation_id')
                ->keyBy('conversation_id');

            $conversations->getCollection()->transform(function ($conv) use ($latestMessages, $user) {
                $conv->latestMessage = $latestMessages->get($conv->id);
                $conv->unread_count = $conv->unreadCount($user->id);
                unset($conv->latest_message_id);
                return $conv;
            });

            return response()->json($conversations);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch conversations', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $companyId = $request->header('X-Company-Id') ?? $user->company_id;

            $validated = $request->validate([
                'participant_ids' => 'required|array|min:1',
                'participant_ids.*' => 'integer|exists:users,id',
                'title' => 'nullable|string|max:255',
                'type' => 'nullable|string|in:direct,group',
            ]);

            $participantIds = collect($validated['participant_ids'])->unique()->values()->toArray();
            $type = $validated['type'] ?? (count($participantIds) === 1 ? 'direct' : 'group');

            if ($type === 'direct' && count($participantIds) === 1) {
                $otherUserId = $participantIds[0];

                $existing = Conversation::where('company_id', $companyId)
                    ->where('type', 'direct')
                    ->whereHas('participants', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }, '=', 1)
                    ->whereHas('participants', function ($q) use ($otherUserId) {
                        $q->where('user_id', $otherUserId);
                    }, '=', 1)
                    ->with('participants:id,name')
                    ->first();

                if ($existing) {
                    return response()->json($existing);
                }
            }

            $conversation = DB::transaction(function () use ($validated, $participantIds, $user, $companyId, $type) {
                $conversation = Conversation::create([
                    'company_id' => $companyId,
                    'title' => $validated['title'] ?? null,
                    'type' => $type,
                    'created_by' => $user->id,
                ]);

                $allParticipantIds = array_unique(array_merge($participantIds, [$user->id]));
                $timestamp = now();
                $attachData = [];
                foreach ($allParticipantIds as $pid) {
                    $attachData[$pid] = ['created_at' => $timestamp, 'updated_at' => $timestamp];
                }
                $conversation->participants()->attach($attachData);

                return $conversation;
            });

            $conversation->load('participants:id,name');

            return response()->json($conversation, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create conversation', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, Conversation $conversation)
    {
        try {
            $user = $request->user();

            $conversation->load([
                'participants' => function ($q) {
                    $q->withPivot('last_read_at');
                },
                'participants.user:id,name',
            ]);

            if (!$conversation->participants->contains($user->id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $perPage = min((int) $request->input('per_page', 50), 100);

            $messages = $conversation->messages()
                ->with('user:id,name')
                ->latest()
                ->paginate($perPage);

            $conversation->setRelation('messages', $messages);

            return response()->json($conversation);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch conversation', 'error' => $e->getMessage()], 500);
        }
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        try {
            $user = $request->user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'body' => 'required|string|max:5000',
            ]);

            $message = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => $validated['body'],
                'type' => 'text',
            ]);

            $conversation->update(['updated_at' => now()]);

            $message->load('user:id,name');

            return response()->json($message, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send message', 'error' => $e->getMessage()], 500);
        }
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        try {
            $user = $request->user();

            DB::table('conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);

            return response()->json(['message' => 'Marked as read']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to mark as read', 'error' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $user = $request->user();
            $companyId = $request->header('X-Company-Id') ?? $user->company_id;

            $validated = $request->validate([
                'q' => 'required|string|min:1|max:255',
            ]);

            $query = $validated['q'];

            $messages = ChatMessage::query()
                ->where('body', 'like', "%{$query}%")
                ->whereHas('conversation', function ($q) use ($companyId, $user) {
                    $q->forCompany($companyId)
                        ->forUser($user->id);
                })
                ->with(['conversation:id,title,type', 'user:id,name'])
                ->orderByDesc('created_at')
                ->paginate(min((int) $request->input('per_page', 15), 100));

            return response()->json($messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to search messages', 'error' => $e->getMessage()], 500);
        }
    }
}