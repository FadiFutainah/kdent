#!/bin/bash

echo "Starting app..."

cd /home/site/wwwroot

php artisan storage:link

php-fpm