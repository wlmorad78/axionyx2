<?php

namespace App\Http\Controllers\Api\CRM\V2;

use App\Http\Controllers\Api\V2\BaseV2Controller;
use App\Services\CRM\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAccountController extends BaseV2Controller
{
    public function __construct(
        protected CustomerService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        if (!$companyId) {
            return $this->errorResponse('company_id is required.', 422);
        }

        $accounts = $this->service->accounts($companyId, $request->input('search'));

        $totalBalance = $accounts->sum('balance');
        $totalDebit = $accounts->sum('total_invoices');
        $totalCredit = $accounts->sum('total_paid');

        return $this->successResponse([
            'data' => $accounts,
            'summary' => [
                'total_customers' => $accounts->count(),
                'total_balance' => $totalBalance,
                'total_credit' => $totalCredit,
                'total_debit' => abs($totalDebit),
            ],
        ], 'Customer accounts retrieved successfully.');
    }
}
