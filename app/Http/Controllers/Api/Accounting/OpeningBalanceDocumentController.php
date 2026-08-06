<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\OpeningBalanceDocument;
use App\Models\OpeningBalanceDocumentLine;
use App\Services\CompanyContext;
use App\Services\BranchContext;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpeningBalanceDocumentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContext::id();
        $branchId = BranchContext::id();

        $query = OpeningBalanceDocument::with(['branch', 'createdBy']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('document_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->filled('date_from')) {
            $query->where('document_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('document_date', '<=', $request->date_to);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('opening_balance_document', 'store'));

        if (empty($validated['company_id'])) {
            $validated['company_id'] = CompanyContext::id();
        }
        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = BranchContext::id();
        }

        $lines = $request->input('lines', []);

        $document = DB::transaction(function () use ($validated, $lines) {
            $document = OpeningBalanceDocument::create($validated);

            foreach ($lines as $line) {
                OpeningBalanceDocumentLine::create([
                    'opening_balance_document_id' => $document->id,
                    'account_id' => $line['account_id'] ?? null,
                    'customer_id' => $line['customer_id'] ?? null,
                    'supplier_id' => $line['supplier_id'] ?? null,
                    'item_id' => $line['item_id'] ?? null,
                    'warehouse_id' => $line['warehouse_id'] ?? null,
                    'unit_id' => $line['unit_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'qty' => $line['qty'] ?? 0,
                    'unit_cost' => $line['unit_cost'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $document;
        });

        $document->load(['lines.account', 'lines.customer', 'lines.supplier', 'lines.item', 'lines.warehouse', 'lines.unit']);

        return response()->json($document, 201);
    }

    public function show($id)
    {
        $companyId = CompanyContext::id();

        $openingBalanceDocument = OpeningBalanceDocument::findOrFail($id);

        if ($companyId && $openingBalanceDocument->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }

        $openingBalanceDocument->load([
            'branch', 'createdBy', 'postedBy',
            'lines.account', 'lines.customer', 'lines.supplier',
            'lines.item', 'lines.warehouse', 'lines.unit',
        ]);

        return response()->json($openingBalanceDocument);
    }

    public function update(Request $request, $id)
    {
        $companyId = CompanyContext::id();

        $openingBalanceDocument = OpeningBalanceDocument::findOrFail($id);

        if ($companyId && $openingBalanceDocument->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }
        if ($openingBalanceDocument->status === 'posted') {
            return response()->json(['message' => 'Cannot edit posted document'], 422);
        }

        $validated = $request->validate(ValidationRules::for('opening_balance_document', 'update', $openingBalanceDocument));
        $lines = $request->input('lines');

        DB::transaction(function () use ($openingBalanceDocument, $validated, $lines) {
            $openingBalanceDocument->update($validated);

            if (is_array($lines)) {
                $openingBalanceDocument->lines()->delete();
                foreach ($lines as $line) {
                    OpeningBalanceDocumentLine::create([
                        'opening_balance_document_id' => $openingBalanceDocument->id,
                        'account_id' => $line['account_id'] ?? null,
                        'customer_id' => $line['customer_id'] ?? null,
                        'supplier_id' => $line['supplier_id'] ?? null,
                        'item_id' => $line['item_id'] ?? null,
                        'warehouse_id' => $line['warehouse_id'] ?? null,
                        'unit_id' => $line['unit_id'] ?? null,
                        'debit' => $line['debit'] ?? 0,
                        'credit' => $line['credit'] ?? 0,
                        'qty' => $line['qty'] ?? 0,
                        'unit_cost' => $line['unit_cost'] ?? 0,
                        'description' => $line['description'] ?? null,
                    ]);
                }
            }
        });

        $openingBalanceDocument->load(['lines.account', 'lines.customer', 'lines.supplier', 'lines.item', 'lines.warehouse', 'lines.unit']);

        return response()->json($openingBalanceDocument);
    }

    public function destroy($id)
    {
        $companyId = CompanyContext::id();

        $openingBalanceDocument = OpeningBalanceDocument::findOrFail($id);

        if ($companyId && $openingBalanceDocument->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }
        if ($openingBalanceDocument->status === 'posted') {
            DB::transaction(function () use ($openingBalanceDocument) {
                $openingBalanceDocument->cancel();
                $openingBalanceDocument->delete();
            });
        } else {
            $openingBalanceDocument->delete();
        }

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $companyId = CompanyContext::id();

        $document = OpeningBalanceDocument::onlyTrashed()->findOrFail($id);

        if ($companyId && $document->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }

        DB::transaction(function () use ($document) {
            $document->restore();
            if ($document->status === 'posted') {
                $document->post();
            }
        });

        return response()->json($document);
    }

    public function forceDelete(int $id)
    {
        $companyId = CompanyContext::id();

        $document = OpeningBalanceDocument::onlyTrashed()->findOrFail($id);

        if ($companyId && $document->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }

        $document->forceDelete();

        return response()->json(null, 204);
    }

    public function post(Request $request, $id)
    {
        $companyId = CompanyContext::id();

        $openingBalanceDocument = OpeningBalanceDocument::findOrFail($id);

        if ($companyId && $openingBalanceDocument->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }
        if ($openingBalanceDocument->status === 'posted') {
            return response()->json(['message' => 'Document already posted'], 422);
        }

        if ($openingBalanceDocument->lines()->count() === 0) {
            return response()->json(['message' => 'Cannot post document with no lines'], 422);
        }

        DB::transaction(function () use ($openingBalanceDocument, $request) {
            $openingBalanceDocument->posted_by = $request->user()->id;
            $openingBalanceDocument->post();
        });

        $openingBalanceDocument->load(['lines.account', 'lines.customer', 'lines.supplier', 'lines.item', 'lines.warehouse', 'lines.unit']);

        return response()->json($openingBalanceDocument);
    }

    public function cancel(Request $request, $id)
    {
        $companyId = CompanyContext::id();

        $openingBalanceDocument = OpeningBalanceDocument::findOrFail($id);

        if ($companyId && $openingBalanceDocument->company_id != $companyId) {
            abort(403, 'غير مصرح');
        }
        if ($openingBalanceDocument->status !== 'posted') {
            return response()->json(['message' => 'Only posted documents can be cancelled'], 422);
        }

        DB::transaction(function () use ($openingBalanceDocument) {
            $openingBalanceDocument->cancel();
        });

        return response()->json($openingBalanceDocument);
    }

    public function schema()
    {
        return ValidationRules::for('opening_balance_document', 'store');
    }
}
