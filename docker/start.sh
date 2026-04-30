#!/bin/bash
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
apache2-foreground