<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_marketing_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_agreement_id')->constrained('customer_agreements');
            $table->foreignId('marketing_support_type_id')->constrained('marketing_support_types');
            $table->decimal('support_value', 12, 2);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_marketing_supports');
    }
};
