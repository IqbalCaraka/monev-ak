# Konfigurasi Server untuk Upload File Besar (700MB+)

Panduan lengkap untuk mengkonfigurasi Windows Server agar bisa menerima upload file DMS hingga 1GB.

---

## 1. Konfigurasi PHP

### Lokasi php.ini:
- **Laragon**: `C:\laragon\bin\php\php-8.x.x\php.ini`
- **XAMPP**: `C:\xampp\php\php.ini`

### Edit Setting Berikut:

```ini
; Maximum size of POST data that PHP will accept
post_max_size = 1100M

; Maximum allowed size for uploaded files
upload_max_filesize = 1100M

; Maximum execution time of each script, in seconds (10 minutes)
max_execution_time = 600

; Maximum amount of time each script may spend parsing request data
max_input_time = 600

; Maximum amount of memory a script may consume
memory_limit = 2048M

; Maximum number of files that can be uploaded via a single request
max_file_uploads = 20
```

**Note**: Gunakan 1100M (sedikit lebih besar dari 1GB) untuk buffer.

### Cara Cek Apakah Berhasil:

Buat file `phpinfo.php` di folder `public/`:
```php
<?php
phpinfo();
?>
```

Akses via browser: `http://192.168.205.63/phpinfo.php`

Cari:
- `upload_max_filesize` → harus `1100M`
- `post_max_size` → harus `1100M`
- `max_execution_time` → harus `600`
- `memory_limit` → harus `2048M`

**PENTING**: Setelah edit, **restart web server** (Apache/Nginx).

---

## 2. Konfigurasi Web Server

### A. Jika Pakai **Apache** (Laragon/XAMPP default)

Edit file `.htaccess` di folder `public/` (sudah otomatis di Laravel).

Jika belum ada, tambahkan:
```apache
<IfModule mod_php.c>
    php_value upload_max_filesize 1100M
    php_value post_max_size 1100M
    php_value max_execution_time 600
    php_value max_input_time 600
    php_value memory_limit 2048M
</IfModule>
```

**Restart Apache** setelah edit.

### B. Jika Pakai **Nginx**

Edit file konfigurasi nginx (biasanya di `C:\nginx\conf\nginx.conf` atau site-specific config):

```nginx
http {
    # ... existing config ...

    client_max_body_size 1100M;
    client_body_timeout 600s;
    client_header_timeout 600s;

    # FastCGI settings (untuk PHP-FPM)
    fastcgi_read_timeout 600s;
    fastcgi_send_timeout 600s;
    fastcgi_connect_timeout 600s;
}

server {
    # ... existing config ...

    location ~ \.php$ {
        # ... existing php config ...

        fastcgi_read_timeout 600;
        fastcgi_send_timeout 600;
    }
}
```

**Restart Nginx** setelah edit:
```bash
nginx -s reload
```

---

## 3. Konfigurasi Laravel

### A. Update DMS Controller

File sudah diupdate di: `app/Http/Controllers/DmsController.php`

```php
$request->validate([
    'csv_file' => 'required|file|mimes:csv,txt|max:1024000', // Max 1GB
]);
```

### B. Update Job Timeout

Edit `config/queue.php` (jika belum):

```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 7200, // 2 hours
        'after_commit' => false,
    ],
],
```

### C. Queue Worker Timeout

Saat menjalankan queue worker, gunakan timeout yang besar:

```bash
php artisan queue:work --verbose --tries=3 --timeout=7200
```

Atau di Task Scheduler batch file (`start-queue-worker.bat`):
```batch
@echo off
cd /d C:\laragon\www\monev_dit_ak
:loop
php artisan queue:work --sleep=3 --tries=3 --max-time=7200 --timeout=7200
timeout /t 5 /nobreak
goto loop
```

---

## 4. Setup Queue Worker (Task Scheduler)

### File Batch Sudah Ada: `start-queue-worker.bat`

### Cara Setup Task Scheduler:

1. Tekan `Win + R`, ketik `taskschd.msc`, Enter
2. Klik **"Create Task"**
3. **General Tab:**
   - Name: `Laravel Queue Worker - Monev DMS`
   - ✅ Run whether user is logged on or not
   - ✅ Run with highest privileges
   - Configure for: Windows Server 2016

4. **Triggers Tab:**
   - New → Begin: `At startup`
   - ✅ Enabled

5. **Actions Tab:**
   - New → Action: `Start a program`
   - Program: `C:\laragon\www\monev_dit_ak\start-queue-worker.bat`
   - Start in: `C:\laragon\www\monev_dit_ak`

6. **Settings Tab:**
   - ✅ Allow task to be run on demand
   - ✅ Run task as soon as possible after a scheduled start is missed
   - If already running: `Do not start a new instance`

7. Klik OK, masukkan password administrator

### Test Queue Worker:

```bash
# Klik kanan task → Run
# Atau via command:
schtasks /run /tn "Laravel Queue Worker - Monev DMS"

# Cek di Task Manager → harus ada php.exe running
```

---

## 5. Folder Permissions

Pastikan folder berikut writable:

```bash
cd C:\laragon\www\monev_dit_ak

# Create folders jika belum ada
mkdir storage\app\dms-uploads
mkdir storage\app\imports
mkdir storage\logs

# Set permissions (Windows)
icacls storage /grant Users:(OI)(CI)F /T
icacls bootstrap\cache /grant Users:(OI)(CI)F /T
```

---

## 6. Database Optimization (Opsional untuk File Besar)

Edit `config/database.php`:

```php
'mysql' => [
    // ... existing config ...

    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_LOCAL_INFILE => true, // Enable LOCAL INFILE for bulk import
        PDO::ATTR_TIMEOUT => 300, // 5 minutes timeout
    ]) : [],
],
```

---

## 7. Monitoring & Troubleshooting

### Cek Log Laravel:
```bash
type C:\laragon\www\monev_dit_ak\storage\logs\laravel.log
```

### Cek Failed Jobs:
```bash
php artisan queue:failed
```

### Retry Failed Jobs:
```bash
php artisan queue:retry all
```

### Clear Queue:
```bash
php artisan queue:flush
```

### Monitor Queue Worker:
```bash
# Run manual untuk debugging
php artisan queue:work --verbose
```

---

## 8. Testing

### Test 1: Cek PHP Settings
```bash
php -r "echo ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo ini_get('post_max_size') . PHP_EOL;"
php -r "echo ini_get('memory_limit') . PHP_EOL;"
```

Harus output:
```
1100M
1100M
2048M
```

### Test 2: Upload File Kecil (10-20MB)
- Test apakah sistem jalan dengan file kecil dulu
- Pastikan queue worker running
- Monitor log

### Test 3: Upload File Sedang (100-200MB)
- Jika berhasil, lanjut ke file lebih besar

### Test 4: Upload File Besar (700MB+)
- Monitor progress di browser
- Monitor queue worker di Task Manager
- Cek log jika ada error

---

## 9. Troubleshooting Common Issues

### Issue: "Upload failed, no error message"
**Solution**:
- Cek php.ini settings
- Restart web server
- Cek nginx/apache config

### Issue: "The file was uploaded but not processed"
**Solution**:
- Pastikan queue worker running
- Cek `php artisan queue:failed`
- Cek `storage/logs/laravel.log`

### Issue: "Queue worker keeps dying"
**Solution**:
- Increase timeout: `--timeout=7200`
- Increase memory: `memory_limit = 2048M`
- Use Task Scheduler with auto-restart loop

### Issue: "Browser timeout during upload"
**Solution**:
- Increase nginx/apache timeout
- Use chunked upload (advanced)
- Or use CLI command: `php artisan dms:import file.csv`

---

## 10. Alternative: CLI Import (Recommended untuk File 500MB+)

Untuk file sangat besar, lebih baik pakai command line:

```bash
cd C:\laragon\www\monev_dit_ak

# Copy file CSV ke server, lalu:
php artisan dms:import DataPNSKeseluruhan170226.csv

# Pastikan queue worker running!
```

Keuntungan:
- ✅ Tidak ada browser timeout
- ✅ Lebih cepat (tidak ada HTTP overhead)
- ✅ Bisa monitor progress di terminal

---

## Checklist Deployment:

- [ ] Edit `php.ini` → set 1100M untuk upload/post size
- [ ] Restart Apache/Nginx
- [ ] Verify dengan `phpinfo.php`
- [ ] Setup Task Scheduler untuk queue worker
- [ ] Test queue worker running
- [ ] Create folder `storage/app/dms-uploads`
- [ ] Test upload file kecil (10MB)
- [ ] Test upload file sedang (100MB)
- [ ] Test upload file besar (700MB)
- [ ] Monitor log jika ada error

---

## Support

Jika masih ada masalah:
1. Cek `storage/logs/laravel.log`
2. Cek `php artisan queue:failed`
3. Test dengan file lebih kecil dulu
4. Pastikan queue worker running di Task Manager

**Reminder**: Setelah deploy code baru, selalu restart queue worker!
```bash
# Stop task di Task Scheduler, lalu start lagi
# Atau kill php.exe di Task Manager, Task Scheduler akan auto-restart
```
