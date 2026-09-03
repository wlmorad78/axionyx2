<?php

namespace App\Events\Sales;

use App\Models\Salesman;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesmanUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Salesman $salesman,
        public readonly array $oldAttributes,
    ) {}
}
