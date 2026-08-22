<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تحديث الأرصدة اليومية كل يوم بعد منتصف الليل (لليوم السابق)
Schedule::command('inventory:snapshot')->dailyAt('00:15');
