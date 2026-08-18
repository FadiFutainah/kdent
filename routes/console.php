<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// تشغيل جوب فحص الصلاحية يومياً عند منتصف الليل
Schedule::command('inventory:check-expired')->daily();
Schedule::command('reminders:send-payment')->dailyAt('09:00');
// نسخة احتياطية كاملة لقاعدة البيانات شهرياً في اليوم الأول الساعة 3 فجراً
Schedule::command('backup:run')->monthly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
