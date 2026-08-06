<?php
namespace App\Services\Handlers;
class UpdateStockOnInvoice {
    public function handle(array $payload, ?int $companyId, ?int $userId): void {
        // Decrement stock for each item in the invoice
    }
}