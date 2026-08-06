<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RepItemDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepItemDistributionController extends Controller
{
    public function index(Request $request)
    {
        $query = RepItemDistribution::with(['item', 'issueOrder', 'returnOrder', 'employee'])
            ->where('company_id', $request->user()->company_id);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('issue_order_id')) {
            $query->where('issue_order_id', $request->issue_order_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query->orderByDesc('id')->paginate($request->get('per_page', 50));

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'item_id' => 'required|integer',
            'issue_order_id' => 'nullable|integer',
            'loaded_qty' => 'required|numeric|min:0',
            'sold_qty' => 'required|numeric|min:0',
            'returned_qty' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->user()->company_id;

        $record = RepItemDistribution::where('company_id', $companyId)
            ->where('employee_id', $validated['employee_id'])
            ->where('item_id', $validated['item_id'])
            ->where('issue_order_id', $validated['issue_order_id'] ?? null)
            ->where('status', 'active')
            ->first();

        $remaining = $validated['loaded_qty'] - $validated['sold_qty'] - $validated['returned_qty'];

        if ($record) {
            $record->update([
                'loaded_qty' => $validated['loaded_qty'],
                'sold_qty' => $validated['sold_qty'],
                'returned_qty' => $validated['returned_qty'],
                'remaining_qty' => max(0, $remaining),
                'unit_price' => $validated['unit_price'],
            ]);
        } else {
            $record = RepItemDistribution::create([
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'item_id' => $validated['item_id'],
                'issue_order_id' => $validated['issue_order_id'] ?? null,
                'loaded_qty' => $validated['loaded_qty'],
                'sold_qty' => $validated['sold_qty'],
                'returned_qty' => $validated['returned_qty'],
                'remaining_qty' => max(0, $remaining),
                'unit_price' => $validated['unit_price'],
                'status' => 'active',
            ]);
        }

        return response()->json(['data' => $record], 201);
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'issue_order_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.loaded_qty' => 'required|numeric|min:0',
            'items.*.sold_qty' => 'required|numeric|min:0',
            'items.*.returned_qty' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->user()->company_id;
        $results = [];

        DB::transaction(function () use ($companyId, $validated, &$results) {
            foreach ($validated['items'] as $item) {
                $remaining = $item['loaded_qty'] - $item['sold_qty'] - $item['returned_qty'];

                $record = RepItemDistribution::where('company_id', $companyId)
                    ->where('employee_id', $validated['employee_id'])
                    ->where('item_id', $item['item_id'])
                    ->where('issue_order_id', $validated['issue_order_id'] ?? null)
                    ->where('status', 'active')
                    ->first();

                if ($record) {
                    $record->update([
                        'loaded_qty' => $item['loaded_qty'],
                        'sold_qty' => $item['sold_qty'],
                        'returned_qty' => $item['returned_qty'],
                        'remaining_qty' => max(0, $remaining),
                        'unit_price' => $item['unit_price'],
                    ]);
                } else {
                    $record = RepItemDistribution::create([
                        'company_id' => $companyId,
                        'employee_id' => $validated['employee_id'],
                        'item_id' => $item['item_id'],
                        'issue_order_id' => $validated['issue_order_id'] ?? null,
                        'loaded_qty' => $item['loaded_qty'],
                        'sold_qty' => $item['sold_qty'],
                        'returned_qty' => $item['returned_qty'],
                        'remaining_qty' => max(0, $remaining),
                        'unit_price' => $item['unit_price'],
                        'status' => 'active',
                    ]);
                }

                $results[] = $record;
            }
        });

        return response()->json(['data' => $results], 201);
    }

    public function linkReturnOrder(Request $request, $returnOrderId)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'issue_order_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.returned_qty' => 'required|numeric|min:0',
        ]);

        $companyId = $request->user()->company_id;

        DB::transaction(function () use ($companyId, $returnOrderId, $validated) {
            foreach ($validated['items'] as $item) {
                if ($item['returned_qty'] <= 0) continue;

                RepItemDistribution::where('company_id', $companyId)
                    ->where('employee_id', $validated['employee_id'])
                    ->where('item_id', $item['item_id'])
                    ->where('issue_order_id', $validated['issue_order_id'] ?? null)
                    ->where('status', 'active')
                    ->update(['return_order_id' => $returnOrderId]);
            }
        });

        return response()->json(['message' => 'ØªÙ… Ø±Ø¨Ø· Ø£Ø°Ù† Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹ Ø¨Ø§Ù„ØªÙˆØ²ÙŠØ¹Ø§Øª']);
    }
}
