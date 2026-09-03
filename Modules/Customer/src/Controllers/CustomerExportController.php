<?php

namespace App\Modules\Customer\src\Controllers;

use App\Http\Controllers\Api\V2\BaseV2Controller;
use App\Modules\Customer\src\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerExportController extends BaseV2Controller
{
    public function __construct(
        protected CustomerService $service,
    ) {}

    public function csv(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        if (!$companyId) {
            return $this->errorResponse('company_id is required.', 422);
        }

        $customers = $this->service->export((int) $companyId, $request->input('search'));

        $headerAr = [
            'كود العميل', 'الاسم بالعربي', 'الاسم بالانجليزي', 'التليفون',
            'الموبايل', 'البريد', 'الرقم الضريبي', 'الرقم القومي',
            'العنوان', 'كود النقطة', 'الشخص المسؤول', 'حد الائتمان',
            'مدة السداد (يوم)', 'نوع الحساب', 'ملاحظات', 'متوسط المسحوبات',
            'الحالة', 'المحافظة', 'المدينة', 'الحي',
        ];

        $rows = [];
        foreach ($customers as $c) {
            $govName = $c['governorate']['name_ar'] ?? '';
            $cityName = $c['city']['name_ar'] ?? '';
            $districtName = $c['area']['name_ar'] ?? '';
            $rows[] = [
                $c['code'] ?? '',
                $c['name_ar'] ?? '',
                $c['name_en'] ?? '',
                $c['phone'] ?? '',
                $c['mobile'] ?? '',
                $c['email'] ?? '',
                $c['tax_number'] ?? '',
                $c['national_id'] ?? '',
                $c['address_line'] ?? '',
                $c['pos_code'] ?? '',
                $c['responsible_person'] ?? '',
                $c['credit_limit'] ?? '',
                $c['payment_term_days'] ?? '',
                $c['account_type'] ?? '',
                $c['notes'] ?? '',
                $c['average_withdrawals'] ?? 0,
                ($c['is_active'] ?? false) ? 'نشط' : 'غير نشط',
                $govName,
                $cityName,
                $districtName,
            ];
        }

        $csv = '';
        $csv .= implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headerAr)) . "\n";

        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        file_put_contents($tempFile, "\xEF\xBB\xBF" . $csv);

        $filename = 'customers_export_' . date('Y-m-d_His') . '.csv';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }
}
