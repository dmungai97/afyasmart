#!/bin/bash
php artisan config:clear
php artisan migrate --force || true
php artisan db:seed --force || true
apache2-foreground