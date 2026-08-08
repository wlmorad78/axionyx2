<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Customer;
use App\Models\Governorate;
use App\Models\Settings\City;
use App\Models\Settings\District;
use App\Models\CustomerGroup;
use App\Models\CustomerClass;
use App\Models\CustomerType;
use App\Models\CustomerAccountType;
use App\Models\TradeProgramType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Customer::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)->with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->customer_group_id) {
            $query->where('customer_group_id', $request->customer_group_id);
        }

        if ($request->customer_class_id) {
            $query->where('customer_class_id', $request->customer_class_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%")
                    ->orWhere('national_id', 'like', "%$s%")
                    ->orWhere('tax_number', 'like', "%$s%")
                    ->orWhere('mobile', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function accounts(Request $request)
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        $query = Customer::query()
            ->with(['customerGroup:id,name_ar', 'customerAccountType:id,name_ar,code'])
            ->where('is_active', true);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%")
                    ->orWhere('mobile', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%");
            });
        }

        $customers = $query->with(['customerGroup', 'customerAccountType'])->orderBy('name_ar')->get([
            'id', 'code', 'name_ar', 'name_en', 'mobile', 'phone',
            'credit_limit', 'customer_group_id', 'customer_account_type_id',
        ]);

        $customerIds = $customers->pluck('id')->toArray();

        // Consolidate 3 separate queries into 1
        $invoiceStats = \App\Models\Sales\SalesInvoice::whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('customer_id, COALESCE(SUM(net_total), 0) as total_invoices, COALESCE(SUM(paid_amount), 0) as total_paid, COUNT(*) as invoice_count')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $result = $customers->map(function ($customer) use ($invoiceStats) {
            $stats = $invoiceStats->get($customer->id);
            $totalInvoices = $stats->total_invoices ?? 0;
            $totalPaid = $stats->total_paid ?? 0;
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
                'balance' => (float) $balance,
                'invoice_count' => (int) ($stats->invoice_count ?? 0),
            ];
        });

        $totalBalance = $result->sum('balance');
        $totalDebit = $result->sum('total_invoices');
        $totalCredit = $result->sum('total_paid');

        return response()->json([
            'data' => $result,
            'summary' => [
                'total_customers' => $result->count(),
                'total_balance' => $totalBalance,
                'total_credit' => $totalCredit,
                'total_debit' => abs($totalDebit),
            ],
        ]);
    }

    public function ledger(Request $request, int $id)
    {
        $customer = Customer::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)->findOrFail($id);

        $from = $request->from;
        $to = $request->to;

        $allInvoices = \App\Models\Sales\SalesInvoice::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)
            ->where('customer_id', $id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'paid_amount', 'remaining_amount', 'status']);

        $openingBalance = 0;
        $periodInvoices = $allInvoices;

        // Include opening balance document lines for this customer
        $obLines = \App\Models\OpeningBalanceDocumentLine::where('customer_id', $id)
            ->whereHas('document', fn($q) => $q->where('status', 'posted'))
            ->get();
        foreach ($obLines as $line) {
            $openingBalance += (float) $line->debit - (float) $line->credit;
        }

        if ($from) {
            $beforeInvoices = $allInvoices->filter(fn($inv) => $inv->invoice_date < $from);
            foreach ($beforeInvoices as $inv) {
                $openingBalance += $inv->net_total - $inv->paid_amount;
            }
            $periodInvoices = $allInvoices->filter(fn($inv) => $inv->invoice_date >= $from);

            // Include receipt vouchers before from date
            $beforeReceipts = \App\Models\Treasury\ReceiptVoucher::where('customer_id', $id)
                ->where('status', '!=', 'cancelled')
                ->where('voucher_date', '<', $from)
                ->get(['amount']);
            foreach ($beforeReceipts as $receipt) {
                $openingBalance -= (float) $receipt->amount;
            }
        }

        if ($to) {
            $periodInvoices = $periodInvoices->filter(fn($inv) => $inv->invoice_date <= $to);
        }

        $runningBalance = $openingBalance;
        $transactions = [];

        foreach ($periodInvoices as $invoice) {
            $runningBalance += $invoice->net_total - $invoice->paid_amount;
            $transactions[] = [
                'date' => $invoice->invoice_date,
                'description' => 'فاتورة بيع ' . $invoice->invoice_no,
                'reference_no' => $invoice->invoice_no,
                'debit' => (float) $invoice->net_total,
                'credit' => (float) $invoice->paid_amount,
                'balance' => (float) $runningBalance,
            ];
        }

        $totalDebit = $periodInvoices->sum('net_total');
        $totalCredit = $periodInvoices->sum('paid_amount');

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name_ar' => $customer->name_ar,
                'name_en' => $customer->name_en,
            ],
            'opening_balance' => (float) $openingBalance,
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'final_balance' => (float) $runningBalance,
            'data' => $transactions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer', 'store'));
        return response()->json(Customer::create($data), 201);
    }

    public function show(Customer $customer)
    {
        return $customer->load(['company', 'customerGroup', 'customerClass', 'customerType', 'customerAccountType', 'tradeProgramType', 'governorate', 'city', 'area', 'addresses', 'contacts']);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate(ValidationRules::for('customer', 'update', $customer));
        $customer->update($data);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = Customer::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        Customer::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer', 'store');
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;

        if (!$companyId) {
            return response()->json(['code' => 'CU-0001', 'next_code' => 'CU-0001', 'warning' => null]);
        }

        // Use a single SQL query to find max sequence instead of loading all customers
        $maxSeq = Customer::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'CU-%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_seq")
            ->value('max_seq') ?? 0;

        $nextSeq = $maxSeq + 1;
        $code = 'CU-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        return response()->json([
            'code' => $code,
            'next_code' => $code,
            'warning' => null,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;
        if (!$companyId) {
            return response()->json(['message' => 'company_id مطلوب'], 422);
        }

        $file = $request->file('file');
        $rawContent = file_get_contents($file->getPathname());

        if (substr($rawContent, 0, 3) === "\xEF\xBB\xBF") {
            $rawContent = substr($rawContent, 3);
        }

        $firstLine = strtok($rawContent, "\n\r");
        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');
        $delimiter = $semicolonCount > $commaCount ? ';' : ',';

        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            return response()->json(['message' => 'لا يمكن قراءة الملف'], 422);
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'الملف فارغ'], 422);
        }

        if (!empty($header[0]) && substr($header[0], 0, 3) === "\xEF\xBB\xBF") {
            $header[0] = substr($header[0], 3);
        }

        $header = array_map('trim', $header);
        $header = array_map('mb_strtolower', $header);

        $columnMap = [
            'name_ar' => 'name_ar',
            'الاسم arabic' => 'name_ar',
            'الاسم بالعربي' => 'name_ar',
            'اسم العميل' => 'name_ar',
            'name_en' => 'name_en',
            'الاسم english' => 'name_en',
            'الاسم بالانجليزي' => 'name_en',
            'phone' => 'phone',
            'التليفون' => 'phone',
            'الهاتف' => 'phone',
            'mobile' => 'mobile',
            'الموبايل' => 'mobile',
            'الجوال' => 'mobile',
            'mobile_number' => 'mobile',
            'code' => 'code',
            'الكود' => 'code',
            'رقم العميل' => 'code',
            'tax_number' => 'tax_number',
            'الرقم الضريبي' => 'tax_number',
            'national_id' => 'national_id',
            'الرقم القومي' => 'national_id',
            'email' => 'email',
            'الايميل' => 'email',
            'البريد' => 'email',
            'address_line' => 'address_line',
            'address' => 'address_line',
            'العنوان' => 'address_line',
            'credit_limit' => 'credit_limit',
            'حد الائتمان' => 'credit_limit',
            'payment_term_days' => 'payment_term_days',
            'pos_code' => 'pos_code',
            'responsible_person' => 'responsible_person',
            'الشخص المسؤول' => 'responsible_person',
            'notes' => 'notes',
            'ملاحظات' => 'notes',
            'average_withdrawals' => 'average_withdrawals',
            'متوسط المسحوبات' => 'average_withdrawals',
            'customer_group_id' => 'customer_group_id',
            'customer_class_id' => 'customer_class_id',
            'customer_type_id' => 'customer_type_id',
            'customer_account_type_id' => 'customer_account_type_id',
            'trade_program_type_id' => 'trade_program_type_id',
            'governorate_id' => 'governorate_id',
            'city_id' => 'city_id',
            'area_id' => 'area_id',
            'account_type' => 'account_type',
            'trade_program_type' => 'trade_program_type',
            'pos_material' => 'pos_material',
            'cus_sings' => 'cus_sings',
            'has_whatsapp' => 'has_whatsapp',
            'whatsapp_number' => 'whatsapp_number',
            'is_active' => 'is_active',
            'company_id' => '_skip_company',
        ];

        $fieldMap = [];
        foreach ($header as $i => $col) {
            $normalized = preg_replace('/\s+/', ' ', strtolower(trim($col)));
            if (isset($columnMap[$col])) {
                $fieldMap[$i] = $columnMap[$col];
            } elseif (isset($columnMap[$normalized])) {
                $fieldMap[$i] = $columnMap[$normalized];
            }
        }

        $success = 0;
        $errors = [];
        $rowNum = 1;

        $maxSeq = 0;
        $existingCustomers = Customer::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'CU-%')
            ->get();
        foreach ($existingCustomers as $c) {
            if ($c->code && preg_match('/^CU-(\d+)$/', $c->code, $m)) {
                $num = (int) $m[1];
                if ($num > $maxSeq) $maxSeq = $num;
            }
        }

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
                if (count($row) < 2) continue;

                $data = [];
                foreach ($fieldMap as $i => $field) {
                    if (isset($row[$i]) && trim($row[$i]) !== '') {
                        $val = trim($row[$i]);
                        if ($field === '_skip_company') continue;
                        $data[$field] = $val;
                    }
                }

                if (empty($data['name_ar']) && empty($data['name_en'])) {
                    $errors[] = "الصف $rowNum: اسم العميل مطلوب";
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

                $data['company_id'] = $companyId;
                if (!isset($data['is_active'])) $data['is_active'] = true;

                if (isset($data['average_withdrawals'])) {
                    $data['average_withdrawals'] = (float) $data['average_withdrawals'];
                }
                if (isset($data['credit_limit'])) {
                    $data['credit_limit'] = (float) $data['credit_limit'];
                }
                if (isset($data['payment_term_days'])) {
                    $data['payment_term_days'] = (int) $data['payment_term_days'];
                }
                foreach (['governorate_id', 'city_id', 'area_id', 'customer_group_id', 'customer_class_id', 'customer_type_id', 'customer_account_type_id', 'trade_program_type_id'] as $fk) {
                    if (isset($data[$fk])) $data[$fk] = (int) $data[$fk];
                }

                Customer::create($data);
                $success++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ في الاستيراد: ' . $e->getMessage(), 'errors' => $errors], 500);
        }

        fclose($handle);

        return response()->json([
            'message' => "تم استيراد $success عميل بنجاح",
            'success' => $success,
            'errors' => $errors,
            'total_errors' => count($errors),
        ]);
    }

    public function importJson(Request $request)
    {
        $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*' => 'required|array',
        ]);

        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;
        if (!$companyId) {
            return response()->json(['message' => 'company_id مطلوب'], 422);
        }

        $rows = $request->input('rows');
        $success = 0;
        $errors = [];

        $maxSeq = 0;
        $existingCustomers = Customer::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'CU-%')
            ->get();
        foreach ($existingCustomers as $c) {
            if ($c->code && preg_match('/^CU-(\d+)$/', $c->code, $m)) {
                $num = (int) $m[1];
                if ($num > $maxSeq) $maxSeq = $num;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNum = $i + 2;
                $data = ['company_id' => $companyId];

                if (!empty($row['name_ar'])) $data['name_ar'] = trim($row['name_ar']);
                if (!empty($row['name_en'])) $data['name_en'] = trim($row['name_en']);
                if (!empty($row['phone'])) $data['phone'] = trim($row['phone']);
                if (!empty($row['mobile'])) $data['mobile'] = trim($row['mobile']);
                if (!empty($row['email'])) $data['email'] = trim($row['email']);
                if (!empty($row['code'])) $data['code'] = trim($row['code']);
                if (!empty($row['tax_number'])) $data['tax_number'] = trim($row['tax_number']);
                if (!empty($row['national_id'])) $data['national_id'] = trim($row['national_id']);
                if (!empty($row['address_line'])) $data['address_line'] = trim($row['address_line']);
                if (!empty($row['pos_code'])) $data['pos_code'] = trim($row['pos_code']);
                if (!empty($row['responsible_person'])) $data['responsible_person'] = trim($row['responsible_person']);
                if (!empty($row['credit_limit'])) $data['credit_limit'] = (float) $row['credit_limit'];
                if (!empty($row['payment_term_days'])) $data['payment_term_days'] = (int) $row['payment_term_days'];
                if (!empty($row['account_type'])) $data['account_type'] = trim($row['account_type']);
                if (!empty($row['notes'])) $data['notes'] = trim($row['notes']);
                if (isset($row['average_withdrawals']) && $row['average_withdrawals'] !== '') {
                    $data['average_withdrawals'] = (float) $row['average_withdrawals'];
                }
                if (!empty($row['governorate_id'])) $data['governorate_id'] = (int) $row['governorate_id'];
                if (!empty($row['city_id'])) $data['city_id'] = (int) $row['city_id'];
                if (!empty($row['area_id'])) $data['area_id'] = (int) $row['area_id'];

                if (empty($data['name_ar']) && empty($data['name_en'])) {
                    $errors[] = "الصف $rowNum: اسم العميل مطلوب";
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
            return response()->json(['message' => 'خطأ في الاستيراد: ' . $e->getMessage(), 'errors' => $errors], 500);
        }

        return response()->json([
            'message' => "تم استيراد $success عميل بنجاح",
            'success' => $success,
            'errors' => $errors,
            'total_errors' => count($errors),
        ]);
    }

    public function importTemplate(Request $request)
    {
        $header = [
            'name_ar', 'name_en', 'phone', 'mobile', 'email', 'code',
            'tax_number', 'national_id', 'address_line', 'pos_code',
            'responsible_person', 'credit_limit', 'payment_term_days',
            'account_type', 'notes', 'average_withdrawals',
            'governorate_id', 'city_id', 'area_id'
        ];

        $headerAr = [
            'الاسم بالعربي', 'الاسم بالانجليزي', 'التليفون', 'الموبايل', 'البريد',
            'الكود', 'الرقم الضريبي', 'الرقم القومي', 'العنوان', 'كود النقطة',
            'الشخص المسؤول', 'حد الائتمان', 'مدة السداد (يوم)',
            'نوع الحساب', 'ملاحظات', 'متوسط المسحوبات',
            'كود المحافظة', 'كود المدينة', 'كود الحي'
        ];

        $csv = implode(',', $headerAr) . "\n";
        $csv .= implode(',', $header) . "\n";
        $csv .= 'أحمد محمد,Ahmed,01012345678,022123456,ahmed@example.com,CU-00001,123456789,29001011234567,القاهرة,,,,30,تجزئة,ملاحظة عميل,15000,2,151,188' . "\n";
        $csv .= 'محمد علي,Mohamed,01098765432,022654321,ali@example.com,CU-00002,987654321,29002021234568,الإسكندرية,,,,60,جملة,,25000,3,201,250' . "\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        file_put_contents($tempFile, "\xEF\xBB\xBF" . $csv);

        return response()->download($tempFile, 'customer_import_template.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="customer_import_template.csv"',
        ])->deleteFileAfterSend(true);
    }

    public function export(Request $request)
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        $query = Customer::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%")
                    ->orWhere('mobile', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%");
            });
        }

        $customers = $query->orderBy('id', 'asc')->get();

        $headerAr = [
            'كود العميل', 'الاسم بالعربي', 'الاسم بالانجليزي', 'التليفون',
            'الموبايل', 'البريد', 'الرقم الضريبي', 'الرقم القومي',
            'العنوان', 'كود النقطة', 'الشخص المسؤول', 'حد الائتمان',
            'مدة السداد (يوم)', 'نوع الحساب', 'ملاحظات', 'متوسط المسحوبات',
            'الحالة', 'المحافظة', 'المدينة', 'الحي'
        ];

        $rows = [];
        foreach ($customers as $c) {
            $govName = $c->governorate ? $c->governorate->name_ar : '';
            $cityName = $c->city ? $c->city->name_ar : '';
            $districtName = $c->area ? $c->area->name_ar : '';
            $rows[] = [
                $c->code ?? '',
                $c->name_ar ?? '',
                $c->name_en ?? '',
                $c->phone ?? '',
                $c->mobile ?? '',
                $c->email ?? '',
                $c->tax_number ?? '',
                $c->national_id ?? '',
                $c->address_line ?? '',
                $c->pos_code ?? '',
                $c->responsible_person ?? '',
                $c->credit_limit ?? '',
                $c->payment_term_days ?? '',
                $c->account_type ?? '',
                $c->notes ?? '',
                $c->average_withdrawals ?? 0,
                $c->is_active ? 'نشط' : 'غير نشط',
                $govName,
                $cityName,
                $districtName,
            ];
        }

        $csv = '';
        $csv .= implode(',', array_map(function ($h) {
            return '"' . str_replace('"', '""', $h) . '"';
        }, $headerAr)) . "\n";

        foreach ($rows as $row) {
            $csv .= implode(',', array_map(function ($v) {
                return '"' . str_replace('"', '""', $v) . '"';
            }, $row)) . "\n";
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        file_put_contents($tempFile, "\xEF\xBB\xBF" . $csv);

        $filename = 'customers_export_' . date('Y-m-d_His') . '.csv';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    public function lastInvoices(Request $request)
    {
        $customerIds = $request->input('customer_ids', []);
        if (empty($customerIds)) {
            return response()->json([]);
        }

        $companyId = $request->user()->company_id;

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

        $result = [];
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

        return response()->json($result);
    }
}
