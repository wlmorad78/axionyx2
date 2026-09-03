<?php

namespace App\Events\CRM;

use App\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
    ) {}
}
