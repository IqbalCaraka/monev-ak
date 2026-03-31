# Deployment Guide - Queue Worker untuk Server Production

## Prerequisites

Server harus memiliki:
- PHP 8.1+
- MySQL/MariaDB
- Composer
- Supervisor (untuk manage queue worker)

## Step-by-Step Deployment

### 1. Pull Code ke Server

```bash
cd /path/to/monev_dit_ak
git pull origin main
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Update .env Configuration

Edit file `.env` dan pastikan:

```env
# Queue Configuration
QUEUE_CONNECTION=database

# Increase PHP limits if needed
# Edit php.ini:
# memory_limit = 2048M
# max_execution_time = 3600
# upload_max_filesize = 512M
# post_max_size = 512M
```

### 4. Run Migrations

```bash
php artisan migrate
```

Migrations yang akan dijalankan:
- `2026_03_03_082204_create_csv_processing_jobs_table` - Create table
- `2026_03_31_031549_update_csv_processing_jobs_table_structure` - Update structure

### 5. Create Required Directories

```bash
mkdir -p storage/app/csv_uploads
mkdir -p storage/app/temp_excel
mkdir -p public/temp

chmod -R 775 storage
chmod -R 775 public/temp

chown -R www-data:www-data storage
chown -R www-data:www-data public/temp
```

### 6. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 7. Setup Supervisor untuk Queue Worker

#### A. Install Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor -y
```

#### B. Create Supervisor Config

```bash
sudo nano /etc/supervisor/conf.d/monev-queue-worker.conf
```

Isi dengan (sesuaikan path):

```ini
[program:monev-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/monev_dit_ak/artisan queue:work --tries=1 --timeout=3600 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/monev_dit_ak/storage/logs/queue-worker.log
stopwaitsecs=3600
startsecs=0
```

**Parameter Explanation:**
- `--tries=1` - Hanya coba 1x (tidak retry jika fail)
- `--timeout=3600` - Timeout 1 jam per job
- `--sleep=3` - Sleep 3 detik jika tidak ada job
- `--max-jobs=1000` - Restart worker setelah 1000 jobs (prevent memory leak)
- `numprocs=2` - Jalankan 2 worker parallel

#### C. Start Supervisor

```bash
# Reload config
sudo supervisorctl reread
sudo supervisorctl update

# Start worker
sudo supervisorctl start monev-queue-worker:*

# Check status
sudo supervisorctl status monev-queue-worker:*
```

### 8. Verify Queue Worker Running

```bash
# Check process
ps aux | grep "queue:work"

# Check logs
tail -f /path/to/monev_dit_ak/storage/logs/queue-worker.log

# Check supervisor status
sudo supervisorctl status
```

Output yang diharapkan:
```
monev-queue-worker:monev-queue-worker_00   RUNNING   pid 1234, uptime 0:00:05
monev-queue-worker:monev-queue-worker_01   RUNNING   pid 1235, uptime 0:00:05
```

## Testing After Deployment

### 1. Test Upload CSV

```bash
# Access via browser
https://your-domain.com/ubah-format

# Upload file CSV
# Should redirect to status page immediately
```

### 2. Monitor Job Processing

```bash
# Watch queue worker log
tail -f storage/logs/queue-worker.log

# Check database
mysql -u username -p database_name
SELECT * FROM csv_processing_jobs ORDER BY created_at DESC LIMIT 5;
SELECT * FROM jobs LIMIT 5;
```

### 3. Test dengan File Besar

Upload file CSV 36 MB:
- Upload time: < 5 detik (hanya upload, tidak processing)
- Processing time: Tergantung jumlah rows (akan berjalan di background)
- Status page: Auto-refresh setiap 3 detik
- Download: Muncul otomatis setelah selesai

## Monitoring & Maintenance

### Check Queue Status

```bash
# Lihat jobs yang sedang berjalan
php artisan queue:monitor

# Lihat failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Monitor Worker Health

```bash
# Check if workers are running
sudo supervisorctl status monev-queue-worker:*

# Restart workers (after code update!)
sudo supervisorctl restart monev-queue-worker:*

# Stop workers
sudo supervisorctl stop monev-queue-worker:*

# Start workers
sudo supervisorctl start monev-queue-worker:*
```

### Monitor Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/queue-worker.log

# Supervisor logs
tail -f /var/log/supervisor/supervisord.log
```

### Clean Up Old Files (Cron Job)

Tambahkan ke crontab untuk auto cleanup:

```bash
crontab -e
```

Add:
```cron
# Cleanup temp files older than 7 days - every day at 2 AM
0 2 * * * find /path/to/monev_dit_ak/public/temp -type f -mtime +7 -delete
0 2 * * * find /path/to/monev_dit_ak/storage/app/csv_uploads -type f -mtime +7 -delete
```

## Troubleshooting

### Problem: Worker tidak running

**Check:**
```bash
sudo supervisorctl status
```

**Fix:**
```bash
sudo supervisorctl start monev-queue-worker:*
```

### Problem: Job stuck di "processing"

**Check database:**
```sql
SELECT * FROM csv_processing_jobs WHERE status = 'processing';
```

**Fix:**
```bash
# Restart workers
sudo supervisorctl restart monev-queue-worker:*
```

### Problem: Memory error

**Fix:**
Edit `/etc/php/8.1/cli/php.ini`:
```ini
memory_limit = 2048M
```

Restart worker:
```bash
sudo supervisorctl restart monev-queue-worker:*
```

### Problem: Timeout error

**Fix:**
Edit supervisor config untuk increase timeout:
```ini
command=php /path/to/monev_dit_ak/artisan queue:work --timeout=7200
stopwaitsecs=7200
```

Reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart monev-queue-worker:*
```

### Problem: Worker berhenti setelah deploy

**Cause:** Worker tidak auto-reload setelah code update

**Fix:**
```bash
# ALWAYS restart worker after code update!
sudo supervisorctl restart monev-queue-worker:*
```

## Performance Tuning

### 1. Increase Worker Count

Edit supervisor config:
```ini
numprocs=4  # Increase from 2 to 4
```

Reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### 2. Use Redis Instead of Database Queue

Install Redis:
```bash
sudo apt-get install redis-server
```

Update `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Restart workers:
```bash
sudo supervisorctl restart monev-queue-worker:*
```

### 3. Optimize PHP-FPM

Edit `/etc/php/8.1/fpm/pool.d/www.conf`:
```ini
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

## Security Checklist

- ✅ File permissions correct (775 untuk storage)
- ✅ Worker running as www-data (bukan root)
- ✅ Temp files auto-cleanup enabled
- ✅ File upload size validated
- ✅ File type validated (only CSV)
- ✅ Auth middleware enabled (optional)

## Rollback Plan

If something goes wrong:

```bash
# 1. Stop workers
sudo supervisorctl stop monev-queue-worker:*

# 2. Rollback code
git checkout HEAD~1

# 3. Rollback migrations
php artisan migrate:rollback --step=1

# 4. Clear cache
php artisan config:clear
php artisan cache:clear

# 5. Restart workers
sudo supervisorctl start monev-queue-worker:*
```

## Support & Contact

If issues persist:
1. Check logs: `storage/logs/laravel.log`
2. Check queue worker logs: `storage/logs/queue-worker.log`
3. Check database: `csv_processing_jobs` table
4. Check supervisor logs: `/var/log/supervisor/supervisord.log`

---

**IMPORTANT REMINDERS:**

1. ✅ **ALWAYS restart queue worker after code update!**
   ```bash
   sudo supervisorctl restart monev-queue-worker:*
   ```

2. ✅ **Monitor disk space** (temp files can accumulate)
   ```bash
   df -h
   du -sh public/temp
   du -sh storage/app/csv_uploads
   ```

3. ✅ **Monitor memory usage**
   ```bash
   free -m
   htop
   ```

4. ✅ **Check worker status regularly**
   ```bash
   sudo supervisorctl status
   ```
