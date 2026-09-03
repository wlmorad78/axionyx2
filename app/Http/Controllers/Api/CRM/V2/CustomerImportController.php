<?php

namespace App\Http\Controllers\Api\CRM\V2;

use App\Http\Controllers\Api\V2\BaseV2Controller;
use App\Http\Requests\CRM\V2\ImportCustomersRequest;
use App\Services\CRM\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerImportController extends BaseV2Controller
{
    public function __construct(
        protected CustomerService $service,
    ) {}

    public function csv(ImportCustomersRequest $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        if (!$companyId) {
            return $this->errorResponse('company_id is required.', 422);
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
            return $this->errorResponse('Cannot read file.', 422);
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return $this->errorResponse('File is empty.', 422);
        }

        if (!empty($header[0]) && substr($header[0], 0, 3) === "\xEF\xBB\xBF") {
            $header[0] = substr($header[0], 3);
        }

        $header = array_map(fn($h) => mb_strtolower(trim($h)), $header);

        $columnMap = [
            'name_ar' => 'name_ar', 'الاسم بالعربي' => 'name_ar', 'اسم العميل' => 'name_ar',
            'name_en' => 'name_en', 'الاسم بالانجليزي' => 'name_en',
            'phone' => 'phone', 'التليفون' => 'phone', 'الهاتف' => 'phone',
            'mobile' => 'mobile', 'الموبايل' => 'mobile', 'الجوال' => 'mobile',
            'code' => 'code', 'الكود' => 'code', 'رقم العميل' => 'code',
            'tax_number' => 'tax_number', 'الرقم الضريبي' => 'tax_number',
            'national_id' => 'national_id', 'الرقم القومي' => 'national_id',
            'email' => 'email', 'الايميل' => 'email', 'البريد' => 'email',
            'address_line' => 'address_line', 'address' => 'address_line', 'العنوان' => 'address_line',
            'credit_limit' => 'credit_limit', 'حد الائتمان' => 'credit_limit',
            'payment_term_days' => 'payment_term_days',
            'pos_code' => 'pos_code',
            'responsible_person' => 'responsible_person', 'الشخص المسؤول' => 'responsible_person',
            'notes' => 'notes', 'ملاحظات' => 'notes',
            'average_withdrawals' => 'average_withdrawals', 'متوسط المسحوبات' => 'average_withdrawals',
            'governorate_id' => 'governorate_id', 'city_id' => 'city_id', 'area_id' => 'area_id',
            'customer_group_id' => 'customer_group_id', 'customer_class_id' => 'customer_class_id',
            'customer_type_id' => 'customer_type_id', 'customer_account_type_id' => 'customer_account_type_id',
            'trade_program_type_id' => 'trade_program_type_id',
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

        $rows = [];
        $rowNum = 1;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (count($row) < 2) continue;

            $data = [];
            foreach ($fieldMap as $i => $field) {
                if (isset($row[$i]) && trim($row[$i]) !== '' && $field !== '_skip_company') {
                    $data[$field] = trim($row[$i]);
                }
            }
            if (!empty($data)) {
                $rows[$rowNum] = $data;
            }
        }
        fclose($handle);

        $result = $this->service->importCsv($rows, (int) $companyId);

        return $this->successResponse([
            'message' => "تم استيراد {$result['success']} عميل بنجاح",
            'success' => $result['success'],
            'errors' => $result['errors'],
            'total_errors' => count($result['errors']),
        ], 'Import completed.', 201);
    }

    public function json(ImportCustomersRequest $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        if (!$companyId) {
            return $this->errorResponse('company_id is required.', 422);
        }

        $result = $this->service->importJson($request->input('rows'), (int) $companyId);

        return $this->successResponse([
            'message' => "تم استيراد {$result['success']} عميل بنجاح",
            'success' => $result['success'],
            'errors' => $result['errors'],
            'total_errors' => count($result['errors']),
        ], 'Import completed.', 201);
    }

    public function template(): \Symfony\Component\HttpFoundation\Response
    {
        $headerAr = [
            'الاسم بالعربي', 'الاسم بالانجليزي', 'التليفون', 'الموبايل', 'البريد',
            'الكود', 'الرقم الضريبي', 'الرقم القومي', 'العنوان', 'كود النقطة',
            'الشخص المسؤول', 'حد الائتمان', 'مدة السداد (يوم)',
            'نوع الحساب', 'ملاحظات', 'متوسط المسحوبات',
            'كود المحافظة', 'كود المدينة', 'كود الحي',
        ];

        $header = [
            'name_ar', 'name_en', 'phone', 'mobile', 'email', 'code',
            'tax_number', 'national_id', 'address_line', 'pos_code',
            'responsible_person', 'credit_limit', 'payment_term_days',
            'account_type', 'notes', 'average_withdrawals',
            'governorate_id', 'city_id', 'area_id',
        ];

        $csv = implode(',', $headerAr) . "\n";
        $csv .= implode(',', $header) . "\n";
        $csv .= 'أحمد محمد,Ahmed,01012345678,022123456,ahmed@example.com,CU-00001,123456789,29001011234567,القاهرة,,,,30,تجزئة,ملاحظة عميل,15000,2,151,188' . "\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        file_put_contents($tempFile, "\xEF\xBB\xBF" . $csv);

        return response()->download($tempFile, 'customer_import_template.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="customer_import_template.csv"',
        ])->deleteFileAfterSend(true);
    }
}
