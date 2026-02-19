@echo off
cd /d C:\laragon\www\monev_dit_ak
:loop
php artisan queue:work --sleep=3 --tries=3 --max-time=7200 --timeout=7200 --memory=2048
timeout /t 5 /nobreak
goto loop
