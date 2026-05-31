#!/bin/bash

echo "Starting app..."
cd /home/site/wwwroot

# توليد الـ الكاش لزيادة السرعة وتجنب المشاكل في بيئة الإنتاج
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ربط التخزين
php artisan storage:link

# تشغيل السيرفر الافتراضي لـ Azure (خلف الكواليس)