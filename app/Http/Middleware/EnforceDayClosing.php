<?php

namespace App\Http\Middleware;

use App\Services\ClosingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDayClosing
{
    // القطاع حسب بادئة المسار
    private array $sectorMap = [
        'inventory-transactions' => ClosingService::SECTOR_INVENTORY,
        'stock-adjustments' => ClosingService::SECTOR_INVENTORY,
        'stock-counts' => ClosingService::SECTOR_INVENTORY,
        'warehouse-transfers' => ClosingService::SECTOR_INVENTORY,
        'issue-orders' => ClosingService::SECTOR_INVENTORY,
        'load-requests' => ClosingService::SECTOR_INVENTORY,
        'inventory-opening-balances' => ClosingService::SECTOR_INVENTORY,
        'inventory-revaluations' => ClosingService::SECTOR_INVENTORY,
        'inventory-transaction-items' => ClosingService::SECTOR_INVENTORY,
        'inventory-transaction-types' => ClosingService::SECTOR_INVENTORY,

        'treasury' => ClosingService::SECTOR_FINANCE,
        'bank' => ClosingService::SECTOR_FINANCE,
        'sales-invoices' => ClosingService::SECTOR_FINANCE,
        'sales-invoice' => ClosingService::SECTOR_FINANCE,
        'return-orders' => ClosingService::SECTOR_FINANCE,
        'customer-returns' => ClosingService::SECTOR_FINANCE,
        'collections' => ClosingService::SECTOR_FINANCE,
        'supplier' => ClosingService::SECTOR_FINANCE,
        'customer' => ClosingService::SECTOR_FINANCE,
        'purchase' => ClosingService::SECTOR_FINANCE,
        'receipt-vouchers' => ClosingService::SECTOR_FINANCE,
        'payment-vouchers' => ClosingService::SECTOR_FINANCE,
    ];

    private array $dateFields = [
        'transaction_date', 'date', 'invoice_date', 'receipt_date',
        'payment_date', 'transfer_date', 'entry_date', 'voucher_date', 'movement_date',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $uri = $request->route()?->uri() ?? $request->path();
        if (str_contains($uri, 'closings')) {
            return $next($request); // شاشة الإقفال نفسها مستثناة
        }

        $sector = $this->resolveSector($uri);
        if ($sector === null) {
            return $next($request);
        }

        $companyId = $request->user()?->company_id;
        if (!$companyId) {
            return $next($request);
        }

        $date = $this->resolveDate($request);
        ClosingService::ensureNotClosed($companyId, $sector, $date);

        return $next($request);
    }

    private function resolveSector(string $uri): ?string
    {
        foreach ($this->sectorMap as $prefix => $sector) {
            if (str_contains($uri, $prefix)) {
                return $sector;
            }
        }

        return null;
    }

    private function resolveDate(Request $request): string
    {
        foreach ($this->dateFields as $field) {
            $val = $request->input($field);
            if (!empty($val)) {
                return $val;
            }
        }

        // معظم الترحيلات بتحط التاريخ = اليوم؛ نفترض اليوم كتاريخ فعلي
        return now()->format('Y-m-d');
    }
}
