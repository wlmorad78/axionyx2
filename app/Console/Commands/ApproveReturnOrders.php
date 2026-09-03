<?php

namespace App\Console\Commands;

use App\Models\ReturnOrder;
use App\Services\ReturnOrderService;
use Illuminate\Console\Command;

class ApproveReturnOrders extends Command
{
    protected $signature = 'return-orders:approve {--company= : Filter by company_id}';
    protected $description = 'Approve all pending return orders';

    public function handle(ReturnOrderService $service): int
    {
        $companyId = $this->option('company');

        $query = ReturnOrder::where('status_id', 'pending');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No pending return orders found.');
            return 0;
        }

        $this->info("Found {$count} pending return orders. Approving...");

        $result = $service->approveAll($companyId);

        $this->newLine();
        $this->info("Approved: {$result['approved']}");
        $this->info("Failed:   {$result['failed']}");

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        return 0;
    }
}
