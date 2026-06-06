<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// تشغيل جوب فحص الصلاحية يومياً عند منتصف الليل
Schedule::command('inventory:check-expired')->daily();
Schedule::command('reminders:send-payment')->dailyAt('09:00');
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
