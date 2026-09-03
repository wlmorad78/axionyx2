<?php

namespace App\Modules\Distribution\src\Events;

use App\Modules\Distribution\src\Models\Salesman;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesmanCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Salesman $salesman,
        public readonly int $companyId,
    ) {}
}
