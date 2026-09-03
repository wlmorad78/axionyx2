<?php

namespace App\Http\Controllers\Api\CRM\V2;

use App\Http\Controllers\Api\V2\BaseV2Controller;
use App\Services\CRM\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerLedgerController extends BaseV2Controller
{
    public function __construct(
        protected CustomerService $service,
    ) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = $request->filled('per_page')
            ? min(max((int) $request->input('per_page'), 1), 200)
            : null;

        $ledger = $this->service->ledger($id, $from, $to);

        $transactions = collect($ledger['transactions']);
        $totalRows = $transactions->count();

        if ($perPage) {
            $pageData = $transactions->forPage($page, $perPage)->values();
            $lastPage = max(1, (int) ceil($totalRows / $perPage));
        } else {
            $pageData = $transactions->values();
            $page = 1;
            $perPage = max($totalRows, 1);
            $lastPage = 1;
        }

        return $this->successResponse([
            'customer' => $ledger['customer'],
            'opening_balance' => $ledger['opening_balance'],
            'total_debit' => $ledger['total_debit'],
            'total_credit' => $ledger['total_credit'],
            'final_balance' => $ledger['final_balance'],
            'data' => $pageData->all(),
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $totalRows,
            'has_more' => $page < $lastPage,
        ], 'Customer ledger retrieved successfully.');
    }
}
