<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OpeningBalanceDocument;
use App\Models\OpeningBalanceDocumentLine;
use App\Models\Accounting\Account;
use App\Models\CRM\Customer;
use App\Models\Suppliers\Supplier;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\Unit;
use App\Models\Company\Branch;
use App\Services\CompanyContext;
use App\Services\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OpeningBalanceDocumentWebController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContext::id();
        $branchId = BranchContext::id();

        $query = OpeningBalanceDocument::with(['branch', 'createdBy', 'postedBy'])
            ->orderByDesc('id');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('document_no', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('balance_type')) {
            $query->where('balance_type', $request->balance_type);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('account_id')) {
            $query->whereHas('lines', function ($q) use ($request) {
                $q->where('account_id', $request->account_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('document_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('document_date', '<=', $request->date_to);
        }

        if ($request->filled('min_amount')) {
            $query->whereHas('lines', function ($q) use ($request) {
                $q->where('debit', '>=', $request->min_amount)
                  ->orWhere('credit', '>=', $request->min_amount);
            });
        }

        if ($request->filled('max_amount')) {
            $query->whereHas('lines', function ($q) use ($request) {
                $q->where('debit', '<=', $request->max_amount)
                  ->orWhere('credit', '<=', $request->max_amount);
            });
        }

        if ($request->has('trashed') && $request->trashed) {
            $query->onlyTrashed();
        }

        $documents = $query->paginate(15);

        $statsQuery = OpeningBalanceDocument::query();
        if ($companyId) {
            $statsQuery->where('company_id', $companyId);
        }
        if ($branchId) {
            $statsQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'posted' => (clone $statsQuery)->where('status', 'posted')->count(),
            'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
        ];

        $branches = Branch::where('is_active', true)->orderBy('name_ar')->get();
        $accounts = Account::where('is_active', true)->orderBy('code')->get();

        return view('opening-balances.index', compact('documents', 'stats', 'branches', 'accounts'));
    }

    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->orderBy('name_ar')->get();
        $accounts = Account::where('is_active', true)->orderBy('code')->get();
        $customers = Customer::where('is_active', true)->orderBy('name_ar')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name_ar')->get();
        $items = Item::where('is_active', true)->orderBy('name_ar')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get();
        $units = Unit::orderBy('name_ar')->get();

        $balanceType = $request->get('balance_type', 'accounts');

        return view('opening-balances.create', compact(
            'branches', 'accounts', 'customers', 'suppliers',
            'items', 'warehouses', 'units', 'balanceType'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'document_date' => 'required|date',
            'balance_type' => 'required|string|in:accounts,customers,suppliers,inventory,assets,cash',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.customer_id' => 'nullable|exists:customers,id',
            'lines.*.supplier_id' => 'nullable|exists:suppliers,id',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'lines.*.unit_id' => 'nullable|exists:units,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.qty' => 'nullable|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:500',
        ]);

        $document = DB::transaction(function () use ($data) {
            $document = OpeningBalanceDocument::create([
                'company_id' => Auth::user()->company_id ?? CompanyContext::id(),
                'branch_id' => $data['branch_id'] ?? BranchContext::id(),
                'document_date' => $data['document_date'],
                'balance_type' => $data['balance_type'],
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['lines'] as $line) {
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

        return redirect()
            ->route('opening-balances.show', $document->id)
            ->with('success', 'تم إنشاء قيد الأرصدة الافتتاحية بنجاح');
    }

    public function show(OpeningBalanceDocument $openingBalance)
    {
        $openingBalance->load([
            'branch', 'createdBy', 'postedBy',
            'lines.account', 'lines.customer', 'lines.supplier',
            'lines.item', 'lines.warehouse', 'lines.unit',
        ]);

        return view('opening-balances.show', compact('openingBalance'));
    }

    public function edit(OpeningBalanceDocument $openingBalance)
    {
        if ($openingBalance->status === 'posted') {
            return redirect()->back()->with('error', 'لا يمكن تعديل قيد معتمد');
        }

        $openingBalance->load('lines');

        $branches = Branch::where('is_active', true)->orderBy('name_ar')->get();
        $accounts = Account::where('is_active', true)->orderBy('code')->get();
        $customers = Customer::where('is_active', true)->orderBy('name_ar')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name_ar')->get();
        $items = Item::where('is_active', true)->orderBy('name_ar')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get();
        $units = Unit::orderBy('name_ar')->get();

        return view('opening-balances.edit', compact(
            'openingBalance', 'branches', 'accounts', 'customers',
            'suppliers', 'items', 'warehouses', 'units'
        ));
    }

    public function update(Request $request, OpeningBalanceDocument $openingBalance)
    {
        if ($openingBalance->status === 'posted') {
            return redirect()->back()->with('error', 'لا يمكن تعديل قيد معتمد');
        }

        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'document_date' => 'required|date',
            'balance_type' => 'required|string|in:accounts,customers,suppliers,inventory,assets,cash',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.customer_id' => 'nullable|exists:customers,id',
            'lines.*.supplier_id' => 'nullable|exists:suppliers,id',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'lines.*.unit_id' => 'nullable|exists:units,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.qty' => 'nullable|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($openingBalance, $data) {
            $openingBalance->update([
                'branch_id' => $data['branch_id'] ?? null,
                'document_date' => $data['document_date'],
                'balance_type' => $data['balance_type'],
                'notes' => $data['notes'] ?? null,
            ]);

            $openingBalance->lines()->delete();

            foreach ($data['lines'] as $line) {
                OpeningBalanceDocumentLine::create([
                    'opening_balance_document_id' => $openingBalance->id,
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
        });

        return redirect()
            ->route('opening-balances.show', $openingBalance->id)
            ->with('success', 'تم تحديث قيد الأرصدة الافتتاحية بنجاح');
    }

    public function destroy(OpeningBalanceDocument $openingBalance)
    {
        if ($openingBalance->status === 'posted') {
            return redirect()->back()->with('error', 'لا يمكن حذف قيد معتمد');
        }

        $openingBalance->delete();

        return redirect()
            ->route('opening-balances.index')
            ->with('success', 'تم حذف قيد الأرصدة الافتتاحية بنجاح');
    }

    public function post(OpeningBalanceDocument $openingBalance)
    {
        if ($openingBalance->status === 'posted') {
            return redirect()->back()->with('error', 'القيد معتمد بالفعل');
        }

        if ($openingBalance->lines()->count() === 0) {
            return redirect()->back()->with('error', 'لا يمكن اعتماد قيد فارغ');
        }

        DB::transaction(function () use ($openingBalance) {
            $openingBalance->posted_by = Auth::id();
            $openingBalance->post();
        });

        return redirect()
            ->route('opening-balances.show', $openingBalance->id)
            ->with('success', 'تم اعتماد قيد الأرصدة الافتتاحية بنجاح');
    }

    public function cancel(OpeningBalanceDocument $openingBalance)
    {
        if ($openingBalance->status !== 'posted') {
            return redirect()->back()->with('error', 'فقط القواعد المعتمدة يمكن إلغاؤها');
        }

        DB::transaction(function () use ($openingBalance) {
            $openingBalance->cancel();
        });

        return redirect()
            ->route('opening-balances.show', $openingBalance->id)
            ->with('success', 'تم إلغاء اعتماد القيد بنجاح');
    }

    public function restore(int $id)
    {
        $document = OpeningBalanceDocument::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($document) {
            $document->restore();
            if ($document->status === 'posted') {
                $document->post();
            }
        });

        return redirect()
            ->route('opening-balances.index')
            ->with('success', 'تم استعادة القيد بنجاح');
    }
}