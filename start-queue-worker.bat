@echo off
echo ========================================
echo   Laravel Queue Worker - Monev DIT AK
echo ========================================
echo.
echo Starting queue worker...
echo Press Ctrl+C to stop
echo.
echo IMPORTANT: Restart this worker after code changes!
echo.

php artisan queue:work --tries=1 --timeout=3600 --verbose

pause
