<?php

namespace App\Modules\Customer\src\Repositories;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function query(): Builder
    {
        return Customer::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class);
    }

    public function findById(int $id, array $with = []): ?Customer
    {
        return $this->query()->with($with)->find($id);
    }

    public function findByCode(string $code, int $companyId): ?Customer
    {
        return $this->query()
            ->where('code', $code)
            ->where('company_id', $companyId)
            ->first();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function restore(int $id): Customer
    {
        $model = Customer::onlyTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete(int $id): bool
    {
        return Customer::onlyTrashed()->findOrFail($id)->forceDelete();
    }

    public function nextCode(int $companyId): string
    {
        $maxSeq = Customer::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'CU-%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_seq")
            ->value('max_seq') ?? 0;

        $nextSeq = $maxSeq + 1;

        return 'CU-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }

    public function getWithRelations(int $id, array $with = []): ?Customer
    {
        return $this->query()->with($with)->find($id);
    }

    public function getAccounts(int $companyId, ?string $search = null): SupportCollection
    {
        $query = Customer::query()
            ->with(['customerGroup:id,name_ar', 'customerAccountType:id,name_ar,code'])
            ->where('is_active', true)
            ->where('company_id', $companyId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name_ar')->get([
            'id', 'code', 'name_ar', 'name_en', 'mobile', 'phone',
            'credit_limit', 'customer_group_id', 'customer_account_type_id',
        ]);

        $customerIds = $customers->pluck('id')->toArray();

        $invoiceStats = SalesInvoice::whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('customer_id, COALESCE(SUM(net_total), 0) as total_invoices, COALESCE(SUM(paid_amount), 0) as total_paid, COUNT(*) as invoice_count')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $standaloneCollections = Collection::whereIn('customer_id', $customerIds)
            ->whereNull('sales_invoice_id')
            ->where('status', 'approved')
            ->selectRaw('customer_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        return $customers->map(function ($customer) use ($invoiceStats, $standaloneCollections) {
            $stats = $invoiceStats->get($customer->id);
            $standalone = $standaloneCollections->get($customer->id);
            $totalInvoices = $stats->total_invoices ?? 0;
            $totalPaid = ($stats->total_paid ?? 0) + ($standalone->total ?? 0);
            $balance = $totalInvoices - $totalPaid;

            return [
                'id' => $customer->id,
                'code' => $customer->code,
                'name_ar' => $customer->name_ar,
                'name_en' => $customer->name_en,
                'mobile' => $customer->mobile,
                'phone' => $customer->phone,
                'credit_limit' => (float) $customer->credit_limit,
                'customer_group' => $customer->customerGroup,
                'customer_account_type' => $customer->customerAccountType,
                'total_invoices' => (float) $totalInvoices,
                'total_paid' => (float) $totalPaid,
                'debit_amount' => (float) $totalInvoices,
                'credit_amount' => (float) $totalPaid,
                'balance' => (float) $balance,
                'invoice_count' => (int) ($stats->invoice_count ?? 0),
            ];
        });
    }

    public function getLedgerData(int $customerId, ?string $from = null, ?string $to = null): array
    {
        $customer = $this->query()->findOrFail($customerId);

        $allInvoices = SalesInvoice::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)
            ->where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'paid_amount', 'remaining_amount', 'status', 'mode']);

        $openingBalance = 0;
        $periodInvoices = $allInvoices;

        $obLines = \App\Models\OpeningBalanceDocumentLine::where('customer_id', $customerId)
            ->whereHas('document', fn($q) => $q->where('status', 'posted'))
            ->get();
        foreach ($obLines as $line) {
            $openingBalance += (float) $line->debit - (float) $line->credit;
        }

        if ($from) {
            $beforeInvoices = $allInvoices->filter(fn($inv) => $inv->invoice_date < $from);
            foreach ($beforeInvoices as $inv) {
                $openingBalance += $inv->net_total;
                $openingBalance -= (float) ($inv->paid_amount ?? 0);
            }
            $periodInvoices = $allInvoices->filter(fn($inv) => $inv->invoice_date >= $from);

            $beforeReceipts = \App\Models\ReceiptVoucher::where('customer_id', $customerId)
                ->where('status', '!=', 'cancelled')
                ->where('voucher_date', '<', $from)
                ->get(['amount']);
            foreach ($beforeReceipts as $receipt) {
                $openingBalance -= (float) $receipt->amount;
            }

            $beforeCollections = Collection::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)
                ->where('customer_id', $customerId)
                ->where('status', '!=', 'cancelled')
                ->where('collection_date', '<', $from)
                ->get(['amount']);
            foreach ($beforeCollections as $col) {
                $openingBalance -= (float) $col->amount;
            }
        }

        if ($to) {
            $periodInvoices = $periodInvoices->filter(fn($inv) => $inv->invoice_date <= $to);
        }

        $collectionsQuery = Collection::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)
            ->where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled');
        if ($from) $collectionsQuery->where('collection_date', '>=', $from);
        if ($to) $collectionsQuery->where('collection_date', '<=', $to);
        $collections = $collectionsQuery->get(['id', 'collection_no', 'collection_date', 'amount', 'sales_invoice_id', 'payment_method_id']);

        $paymentMethodIds = $collections->pluck('payment_method_id')->filter()->unique()->values();
        $paymentMethodNames = $paymentMethodIds->isEmpty()
            ? collect()
            : DB::table('payment_methods')->whereIn('id', $paymentMethodIds)->pluck('name', 'id');

        $pmName = function ($id) use ($paymentMethodNames): ?string {
            if ($id === null || $id === '') return null;
            return $paymentMethodNames[$id] ?? $paymentMethodNames[(int) $id] ?? $paymentMethodNames[(string) $id] ?? null;
        };

        $methodsByInvoice = [];
        foreach ($collections as $col) {
            $name = $pmName($col->payment_method_id);
            if ($col->sales_invoice_id && $name) {
                $methodsByInvoice[$col->sales_invoice_id][] = $name;
            }
        }

        $invoiceModeLabel = function (?string $mode): string {
            $value = trim((string) $mode);
            if ($value === '') return '-';
            return match (strtolower($value)) {
                'cash', 'نقدي' => 'نقدي',
                'credit', 'آجل' => 'آجل',
                default => $value,
            };
        };

        $formatDate = function ($date): ?string {
            if ($date instanceof \DateTimeInterface) return $date->format('Y-m-d');
            if (is_string($date) && $date !== '') return substr($date, 0, 10);
            return null;
        };

        $invoicesWithPaid = $periodInvoices
            ->filter(fn($inv) => (float) ($inv->paid_amount ?? 0) > 0)
            ->keyBy('id');

        $transactions = [];

        foreach ($periodInvoices as $invoice) {
            $invoicePm = $invoiceModeLabel($invoice->mode);
            if ($invoicePm === '-') {
                $fromCollections = array_values(array_unique($methodsByInvoice[$invoice->id] ?? []));
                if ($fromCollections) {
                    $invoicePm = implode(' / ', $fromCollections);
                }
            }
            $transactions[] = [
                'date' => $formatDate($invoice->invoice_date),
                'description' => 'فاتورة بيع ' . $invoice->invoice_no,
                'reference_no' => $invoice->invoice_no,
                'debit' => (float) $invoice->net_total,
                'credit' => 0,
                'payment_method' => $invoicePm,
                'sort_seq' => 1,
            ];
        }

        foreach ($periodInvoices as $invoice) {
            $paid = (float) ($invoice->paid_amount ?? 0);
            if ($paid <= 0) continue;
            $names = array_values(array_unique($methodsByInvoice[$invoice->id] ?? []));
            $transactions[] = [
                'date' => $formatDate($invoice->invoice_date),
                'description' => 'سداد فاتورة ' . $invoice->invoice_no,
                'reference_no' => $invoice->invoice_no,
                'debit' => 0,
                'credit' => $paid,
                'payment_method' => $names ? implode(' / ', $names) : $invoiceModeLabel($invoice->mode),
                'sort_seq' => 2,
            ];
        }

        $standaloneCollections = $collections->filter(function ($col) use ($invoicesWithPaid) {
            return !($col->sales_invoice_id && $invoicesWithPaid->has($col->sales_invoice_id));
        });

        foreach ($standaloneCollections as $col) {
            $transactions[] = [
                'date' => $formatDate($col->collection_date),
                'description' => 'سند سداد ' . $col->collection_no,
                'reference_no' => $col->collection_no,
                'debit' => 0,
                'credit' => (float) $col->amount,
                'payment_method' => $pmName($col->payment_method_id) ?? '-',
                'sort_seq' => 3,
            ];
        }

        $sorted = collect($transactions)->sortBy([
            ['date', 'asc'],
            ['sort_seq', 'asc'],
        ])->values();

        $runningBalance = $openingBalance;
        $sorted = $sorted->map(function ($row) use (&$runningBalance) {
            $runningBalance += (float) ($row['debit'] ?? 0) - (float) ($row['credit'] ?? 0);
            $row['balance'] = (float) $runningBalance;
            unset($row['sort_seq']);
            return $row;
        });

        $totalDebit = (float) $periodInvoices->sum('net_total');
        $totalCredit = (float) $periodInvoices->sum('paid_amount') + (float) $standaloneCollections->sum('amount');

        return [
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name_ar' => $customer->name_ar,
                'name_en' => $customer->name_en,
            ],
            'opening_balance' => (float) $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'final_balance' => (float) $runningBalance,
            'transactions' => $sorted->all(),
        ];
    }

    public function importFromCsv(array $rows, int $companyId): array
    {
        $success = 0;
        $errors = [];
        $maxSeq = $this->getCurrentMaxSequence($companyId);

        DB::beginTransaction();
        try {
            foreach ($rows as $rowNum => $row) {
                if (empty($row['name_ar']) && empty($row['name_en'])) {
                    $errors[] = "الصف {$rowNum}: اسم العميل مطلوب";
                    continue;
                }

                if (empty($row['code'])) {
                    $maxSeq++;
                    $row['code'] = 'CU-' . str_pad($maxSeq, 5, '0', STR_PAD_LEFT);
                } else {
                    $exists = Customer::where('company_id', $companyId)
                        ->where('code', $row['code'])
                        ->exists();
                    if ($exists) {
                        $maxSeq++;
                        $row['code'] = 'CU-' . str_pad($maxSeq, 5, '0', STR_PAD_LEFT);
                    }
                }

                $row['company_id'] = $companyId;
                $row['is_active'] = $row['is_active'] ?? true;

                $this->castImportFields($row);
                Customer::create($row);
                $success++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return ['success' => $success, 'errors' => $errors];
    }

    public function importFromJson(array $rows, int $companyId): array
    {
        $success = 0;
        $errors = [];
        $maxSeq = $this->getCurrentMaxSequence($companyId);

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNum = $i + 2;
                $data = ['company_id' => $companyId];

                $fields = [
                    'name_ar', 'name_en', 'phone', 'mobile', 'email', 'code',
                    'tax_number', 'national_id', 'address_line', 'pos_code',
                    'responsible_person', 'credit_limit', 'payment_term_days',
                    'account_type', 'notes',
                ];

                foreach ($fields as $field) {
                    if (!empty($row[$field])) {
                        $data[$field] = trim($row[$field]);
                    }
                }

                if (isset($row['average_withdrawals']) && $row['average_withdrawals'] !== '') {
                    $data['average_withdrawals'] = (float) $row['average_withdrawals'];
                }

                foreach (['governorate_id', 'city_id', 'area_id'] as $fk) {
                    if (!empty($row[$fk])) $data[$fk] = (int) $row[$fk];
                }

                if (empty($data['name_ar']) && empty($data['name_en'])) {
                    $errors[] = "الصف {$rowNum}: اسم العميل مطلوب";
                    continue;
                }

                if (empty($data['code'])) {
                    $maxSeq++;
                    $data['code'] = 'CU-' . str_pad($maxSeq, 5, '0', STR_PAD_LEFT);
                } else {
                    $exists = Customer::where('company_id', $companyId)
                        ->where('code', $data['code'])
                        ->exists();
                    if ($exists) {
                        $maxSeq++;
                        $data['code'] = 'CU-' . str_pad($maxSeq, 5, '0', STR_PAD_LEFT);
                    }
                }

                $data['is_active'] = true;
                Customer::create($data);
                $success++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return ['success' => $success, 'errors' => $errors];
    }

    public function exportData(int $companyId, ?string $search = null): array
    {
        $query = Customer::query()->where('company_id', $companyId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'asc')->get()->toArray();
    }

    public function getLastInvoices(array $customerIds, int $companyId): SupportCollection
    {
        $invoices = DB::table('sales_invoices as si')
            ->join('sales_invoice_items as sii', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('items as it', 'it.id', '=', 'sii.item_id')
            ->whereIn('si.customer_id', $customerIds)
            ->where('si.company_id', $companyId)
            ->whereNull('si.deleted_at')
            ->select(
                'si.customer_id',
                'si.id as invoice_id',
                'si.invoice_date',
                'si.invoice_no',
                'it.id as item_id',
                'it.name_ar as item_name',
                'sii.qty'
            )
            ->orderByDesc('si.invoice_date')
            ->orderByDesc('si.id')
            ->get();

        $result = collect();
        foreach ($customerIds as $cid) {
            $custInvoices = $invoices->where('customer_id', $cid)->values();
            $invoiceIds = [];
            foreach ($custInvoices as $inv) {
                if (!in_array($inv->invoice_id, $invoiceIds)) {
                    $invoiceIds[] = $inv->invoice_id;
                }
                if (count($invoiceIds) >= 4) break;
            }
            $last4Ids = array_slice($invoiceIds, 0, 4);
            $relevant = $custInvoices->filter(fn($r) => in_array($r->invoice_id, $last4Ids));

            $invoicesData = [];
            $totalQty = 0;
            $itemsSummary = [];

            foreach ($last4Ids as $invId) {
                $invItems = $relevant->where('invoice_id', $invId);
                if ($invItems->isEmpty()) continue;
                $first = $invItems->first();
                $invQty = 0;
                $items = [];
                foreach ($invItems as $row) {
                    $qty = (float) $row->qty;
                    $invQty += $qty;
                    $items[] = [
                        'item_id' => $row->item_id,
                        'item_name' => $row->item_name,
                        'qty' => $qty,
                    ];
                    $itemsSummary[$row->item_id] = $itemsSummary[$row->item_id] ?? [
                        'item_id' => $row->item_id,
                        'item_name' => $row->item_name,
                        'total_qty' => 0,
                    ];
                    $itemsSummary[$row->item_id]['total_qty'] += $qty;
                }
                $invoicesData[] = [
                    'invoice_id' => $invId,
                    'invoice_no' => $first->invoice_no,
                    'invoice_date' => $first->invoice_date,
                    'total_qty' => $invQty,
                    'items' => $items,
                ];
                $totalQty += $invQty;
            }

            $result[$cid] = [
                'total_qty' => round($totalQty, 0),
                'invoices' => $invoicesData,
                'items_summary' => array_values($itemsSummary),
            ];
        }

        return $result;
    }

    private function getCurrentMaxSequence(int $companyId): int
    {
        $maxSeq = Customer::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'CU-%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_seq")
            ->value('max_seq') ?? 0;

        return (int) $maxSeq;
    }

    private function castImportFields(array &$row): void
    {
        foreach (['average_withdrawals', 'credit_limit'] as $field) {
            if (isset($row[$field])) $row[$field] = (float) $row[$field];
        }

        if (isset($row['payment_term_days'])) {
            $row['payment_term_days'] = (int) $row['payment_term_days'];
        }

        foreach (['governorate_id', 'city_id', 'area_id', 'customer_group_id', 'customer_class_id', 'customer_type_id', 'customer_account_type_id', 'trade_program_type_id'] as $fk) {
            if (isset($row[$fk])) $row[$fk] = (int) $row[$fk];
        }
    }
}
