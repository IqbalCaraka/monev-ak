# Quick Setup Guide - Upload File 700MB+

Panduan cepat untuk setup server agar bisa upload file DMS hingga 1GB.

---

## 📋 Checklist Setup di Server

### 1. Edit PHP Configuration

**File**: `C:\laragon\bin\php\php-8.x.x\php.ini` (sesuaikan path)

Cari dan edit baris berikut:
```ini
upload_max_filesize = 1100M
post_max_size = 1100M
max_execution_time = 600
max_input_time = 600
memory_limit = 2048M
```

**Restart Laragon** atau Apache/Nginx setelah edit.

---

### 2. Verify PHP Settings

Buat file: `public/check.php`
```php
<?php
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
```

Akses: `http://192.168.205.63/check.php`

Harus tampil:
```
upload_max_filesize: 1100M
post_max_size: 1100M
max_execution_time: 600
memory_limit: 2048M
```

✅ Jika sudah OK, hapus file `check.php`

---

### 3. Setup Queue Worker (Task Scheduler)

**A. File batch sudah ada**: `start-queue-worker.bat`

**B. Setup Task Scheduler**:
1. `Win + R` → ketik `taskschd.msc` → Enter
2. Create Task → Name: `Laravel Queue Worker - Monev DMS`
3. **General**: ✅ Run whether user is logged on or not, ✅ Highest privileges
4. **Triggers**: At startup
5. **Actions**:
   - Program: `C:\laragon\www\monev_dit_ak\start-queue-worker.bat`
   - Start in: `C:\laragon\www\monev_dit_ak`
6. **Settings**: Do not start new instance if already running
7. OK → masukkan password

**C. Test Run**:
- Klik kanan task → Run
- Buka Task Manager → harus ada `php.exe` running

---

### 4. Create Folders (jika belum ada)

```cmd
cd C:\laragon\www\monev_dit_ak
mkdir storage\app\dms-uploads
mkdir storage\app\imports
```

---

### 5. Git Pull & Migrate

```cmd
cd C:\laragon\www\monev_dit_ak
git pull origin main
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

---

## ✅ Testing

### Test 1: Small File (10MB)
Upload file kecil via browser untuk test sistem.

### Test 2: Medium File (100-200MB)
Upload file sedang untuk test.

### Test 3: Large File (700MB)
Upload file besar yang sebenarnya.

**Monitor**:
- Task Manager → `php.exe` harus running
- Browser → lihat progress bar
- `storage/logs/laravel.log` → cek jika ada error

---

## 🚨 Troubleshooting

### Upload Gagal
```cmd
# Cek PHP settings
php -r "echo ini_get('upload_max_filesize');"

# Cek log
type storage\logs\laravel.log

# Cek failed jobs
php artisan queue:failed
```

### Queue Worker Mati
```cmd
# Restart via Task Scheduler
schtasks /run /tn "Laravel Queue Worker - Monev DMS"

# Atau manual
cd C:\laragon\www\monev_dit_ak
start-queue-worker.bat
```

### Browser Timeout
Gunakan CLI import:
```cmd
php artisan dms:import path\to\file.csv
```

---

## 📝 Summary Perubahan

1. **DmsController.php**: Max upload 1GB (dari 500MB)
2. **start-queue-worker.bat**: Timeout 7200s, memory 2048MB
3. **ImportDmsCommand.php**: CLI import untuk file besar
4. **Dokumentasi**: `SERVER_CONFIG_LARGE_UPLOAD.md`

---

## 🎯 Next Steps

Setelah setup selesai:
1. Test dengan file 10MB dulu
2. Lalu 100MB
3. Baru 700MB

**Ingat**: Setiap deploy code baru, **restart queue worker**!

```cmd
# Stop & start task di Task Scheduler
# Atau kill php.exe, task akan auto-restart
```

---

## 📞 Need Help?

Cek file lengkap: `SERVER_CONFIG_LARGE_UPLOAD.md`
