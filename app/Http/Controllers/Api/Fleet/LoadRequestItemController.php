<?php
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Sales\LoadRequestItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LoadRequestItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LoadRequestItem::with($with);
        if ($request->load_request_id) $query->where('load_request_id', $request->load_request_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('load_request_item', 'store'));
        $item = LoadRequestItem::create($data);
        return response()->json($item, 201);
    }

    public function show(LoadRequestItem $loadRequestItem)
    {
        return $loadRequestItem->load(['loadRequest', 'item', 'unit']);
    }

    public function update(Request $request, LoadRequestItem $loadRequestItem)
    {
        $data = $request->validate(ValidationRules::for('load_request_item', 'update', $loadRequestItem));
        $loadRequestItem->update($data);
        return response()->json($loadRequestItem);
    }

    public function destroy(LoadRequestItem $loadRequestItem)
    {
        $loadRequestItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = LoadRequestItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        LoadRequestItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('load_request_item', 'store');
    }
}
