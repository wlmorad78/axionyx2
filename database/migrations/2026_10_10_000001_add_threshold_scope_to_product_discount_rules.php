<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نوع الحد الأدنى لقاعدة الخصم:
     *  - per_item      : الحد يُفحص على كرتونات كل صنف على حدة (السلوك الافتراضي).
     *  - invoice_total : الحد يُفحص على مجموع كراتين كل أصناف الفاتورة،
     *                    ويُطبَّق الخصم على كل أصناف الفاتورة.
     */
    public function up(): void
    {
        Schema::table('product_discount_rules', function (Blueprint $table) {
            $table->string('threshold_scope', 20)
                ->default('per_item')
                ->after('discount_per_carton');
            $table->index(['company_id', 'threshold_scope']);
        });
    }

    public function down(): void
    {
        Schema::table('product_discount_rules', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'threshold_scope']);
            $table->dropColumn('threshold_scope');
        });
    }
};
