<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingPhoto;
use Illuminate\Http\Request;

class MerchandisingPhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingPhoto::with('visit');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('photo_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('merchandising_visit_id')) $query->where('merchandising_visit_id', $request->merchandising_visit_id);
        if ($request->filled('photo_type')) $query->where('photo_type', $request->photo_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'merchandising_visit_id' => 'required|exists:merchandising_visits,id',
            'photo_type' => 'required|in:STORE_FRONT,SHELF,FRIDGE,DISPLAY,PROMOTION',
            'file_path' => 'required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $photo = MerchandisingPhoto::create($data);
        return response()->json($photo, 201);
    }

    public function show($id)
    {
        return MerchandisingPhoto::with('visit')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $photo = MerchandisingPhoto::findOrFail($id);

        $data = $request->validate([
            'merchandising_visit_id' => 'sometimes|required|exists:merchandising_visits,id',
            'photo_type' => 'sometimes|required|in:STORE_FRONT,SHELF,FRIDGE,DISPLAY,PROMOTION',
            'file_path' => 'sometimes|required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $photo->update($data);
        return $photo;
    }

    public function destroy($id)
    {
        $photo = MerchandisingPhoto::findOrFail($id);
        $photo->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $photo = MerchandisingPhoto::withTrashed()->findOrFail($id);
        $photo->restore();
        return $photo;
    }

    public function forceDelete($id)
    {
        $photo = MerchandisingPhoto::withTrashed()->findOrFail($id);
        $photo->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
