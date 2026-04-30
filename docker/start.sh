#!/bin/bash
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force 2>/dev/null || true
apache2-foreground