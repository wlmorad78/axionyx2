<?php
namespace App\Services\Handlers;
class UpdateInvoiceOnPayment {
    public function handle(array $payload, ?int $companyId, ?int $userId): void {
        // Update invoice paid_amount and remaining_amount
    }
}