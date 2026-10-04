<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZzzSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_migrates(): void
    {
        $this->assertTrue(true);
        $this->assertNotNull(DB::table('sales_invoices')->count());
    }
}
