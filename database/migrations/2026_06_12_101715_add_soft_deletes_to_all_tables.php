<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('governorates', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('cities', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('districts', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('streets', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('currencies', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('subscription_plans', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('payment_methods', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('company_subscriptions', fn(Blueprint $t) => $t->softDeletes());
        Schema::table('company_subscription_limits', fn(Blueprint $t) => $t->softDeletes());
    }

    public function down(): void
    {
        Schema::table('countries', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('governorates', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('cities', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('districts', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('streets', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('currencies', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('subscription_plans', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('payment_methods', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('company_subscriptions', fn(Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('company_subscription_limits', fn(Blueprint $t) => $t->dropSoftDeletes());
    }
};
