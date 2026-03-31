# Setup Queue Worker untuk Ubah Format CSV ke Excel

## Deskripsi
Fitur konversi CSV ke Excel menggunakan Laravel Queue untuk memproses file besar (36 MB+) di background, sehingga tidak menyebabkan timeout pada web request.

## Persiapan

### 1. Konfigurasi Queue Driver

Edit file `.env` dan set queue driver ke `database`:

```env
QUEUE_CONNECTION=database
```

### 2. Jalankan Migration

Migration untuk table `jobs` dan `csv_processing_jobs` sudah dibuat. Pastikan sudah di-migrate:

```bash
php artisan migrate
```

## Cara Menjalankan Queue Worker

### Development (Windows/Laragon)

#### Opsi 1: Manual Command (Recommended untuk Development)

Buka terminal/command prompt di folder project dan jalankan:

```bash
php artisan queue:work --tries=1 --timeout=3600
```

**Parameter:**
- `--tries=1` : Job hanya dicoba 1 kali (jika gagal tidak retry)
- `--timeout=3600` : Timeout 1 jam untuk job yang berjalan lama

**Catatan:**
- Command ini akan terus berjalan dan memproses job yang masuk
- Untuk stop, tekan `Ctrl+C`
- Setiap kali update code, **HARUS restart worker** dengan `Ctrl+C` lalu jalankan lagi

#### Opsi 2: Menggunakan Supervisor (Production-like)

1. Install Supervisor di Windows (via Cygwin/WSL) atau gunakan alternatif seperti NSSM (Non-Sucking Service Manager)

2. Buat konfigurasi supervisor:

```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php c:\laragon\www\monev_dit_ak\artisan queue:work --tries=1 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=your_username
numprocs=1
redirect_stderr=true
stdout_logfile=c:\laragon\www\monev_dit_ak\storage\logs\queue-worker.log
stopwaitsecs=3600
```

### Production (Linux Server)

#### 1. Install Supervisor

```bash
sudo apt-get install supervisor
```

#### 2. Buat Config File

```bash
sudo nano /etc/supervisor/conf.d/laravel-queue-worker.conf
```

Isi dengan:

```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/monev_dit_ak/artisan queue:work --tries=1 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/monev_dit_ak/storage/logs/queue-worker.log
stopwaitsecs=3600
```

#### 3. Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*
```

#### 4. Monitor Worker

```bash
# Check status
sudo supervisorctl status laravel-queue-worker:*

# View logs
tail -f /path/to/monev_dit_ak/storage/logs/queue-worker.log

# Restart worker (after code update)
sudo supervisorctl restart laravel-queue-worker:*
```

## Cara Pakai Fitur

### 1. Upload CSV

1. Buka halaman Ubah Format
2. Upload file CSV (maksimal 500 MB)
3. Klik "Convert to Excel"

### 2. Monitor Progress

Setelah upload, Anda akan diarahkan ke halaman status yang menampilkan:
- Status: Pending → Processing → Completed/Failed
- Progress message (update setiap 3 detik otomatis)
- Tombol download muncul ketika selesai

### 3. Download Hasil

Klik tombol "Download Excel" yang muncul setelah processing selesai.

## Monitoring & Troubleshooting

### Cek Status Job di Database

```sql
SELECT * FROM csv_processing_jobs ORDER BY created_at DESC LIMIT 10;
```

### Cek Queue Jobs

```sql
SELECT * FROM jobs ORDER BY id DESC LIMIT 10;
```

### Cek Failed Jobs

```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

### Clear Failed Jobs

```bash
php artisan queue:flush
```

### Restart Queue Worker (Important!)

**PENTING:** Setiap kali update code, WAJIB restart queue worker!

Development:
```bash
# Stop dengan Ctrl+C, lalu jalankan lagi
php artisan queue:work --tries=1 --timeout=3600
```

Production:
```bash
sudo supervisorctl restart laravel-queue-worker:*
```

## Performance Tips

### 1. Increase PHP Memory Limit

Edit `php.ini`:
```ini
memory_limit = 2048M
max_execution_time = 3600
```

### 2. Monitor Memory Usage

```bash
# Linux
watch -n 1 free -m

# Windows Task Manager
Look for php.exe process
```

### 3. Clean Up Old Files

Buat cron job untuk hapus file temporary yang sudah lama:

```bash
# Hapus file temp yang lebih dari 7 hari
find /path/to/monev_dit_ak/public/temp -type f -mtime +7 -delete
find /path/to/monev_dit_ak/storage/app/csv_uploads -type f -mtime +7 -delete
```

## Error Handling

### Job Timeout
Jika file terlalu besar dan timeout, increase parameter `--timeout`:
```bash
php artisan queue:work --tries=1 --timeout=7200  # 2 hours
```

### Memory Error
Increase `memory_limit` di `php.ini` atau dalam Job class (sudah diset 2048M).

### Worker Hang
Restart worker:
```bash
# Development
Ctrl+C then run again

# Production
sudo supervisorctl restart laravel-queue-worker:*
```

## Logs

Check logs di:
- Laravel log: `storage/logs/laravel.log`
- Queue worker log: `storage/logs/queue-worker.log` (if using supervisor)
- Supervisor log: `/var/log/supervisor/supervisord.log`

## Keamanan

1. File temporary otomatis dihapus setelah download
2. Hanya user yang login yang bisa upload (jika ditambahkan middleware auth)
3. Validasi file type (hanya CSV)
4. Validasi ukuran file (max 500MB)

## Alternatif Queue Driver

Jika ingin performa lebih baik, bisa menggunakan Redis:

1. Install Redis
2. Update `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```
3. Restart queue worker
