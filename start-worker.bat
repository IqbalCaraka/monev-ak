@echo off
title Laravel Queue Worker - Monev Dit AK
cd /d C:\laragon\www\monev_dit_ak
echo Starting Queue Worker...
echo Press Ctrl+C to stop
php artisan queue:work --verbose --tries=3 --timeout=3600
pause
