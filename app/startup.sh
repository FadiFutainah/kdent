#!/bin/bash

cd /home/site/wwwroot
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan migrate --force

# Set public as web root
cp -r /home/site/wwwroot/public/* /home/site/wwwroot/