# Panduan Deploy ke Server

## Langkah-langkah Deploy:

### 1. Pull Perubahan dari Git
```bash
cd /path/to/monev_dit_ak
git pull origin main
```

### 2. Install/Update Dependencies (jika ada perubahan)
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Jalankan Migration
```bash
php artisan migrate
```

Migration akan menambahkan:
- Kolom `day_name` dan `work_category` ke tabel `log_aktivitas` dan `log_aktivitas_staging`
- Tabel `dms_nasional` untuk statistik nasional DMS

### 4. Clear Cache Laravel
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5. Jalankan Seeder untuk Isi Data
```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=InstansiSeeder
php artisan db:seed --class=PegawaiSeeder
```

**PENTING**: File CSV untuk seeder harus ada di root folder:
- `Instansi 190226.csv` (674 instansi)
- `List Pegawai Dit.AK - Sheet1 (1).csv` (36 pegawai)

File ini sudah ada di git karena sudah di-push.

### 6. Pastikan Queue Worker Berjalan
```bash
# Cek apakah queue worker sudah berjalan
ps aux | grep "queue:work"

# Jika belum, jalankan queue worker
php artisan queue:work --verbose --tries=3 --timeout=3600 &
```

Atau jika pakai supervisor, restart supervisor:
```bash
sudo supervisorctl restart all
```

### 7. Upload Data Log Aktivitas
- Buka browser ke: `http://your-server/statistik/aktivitas-pegawai`
- Upload file: `Log Activity 190226.csv` (73MB)
- Tunggu proses selesai (akan berjalan di background via queue)

### 8. Verifikasi Data
Cek apakah data sudah masuk:
```bash
php artisan db:table log_aktivitas
php artisan db:table log_aktivitas_staging
php artisan db:table pegawai_aktivitas_summary
```

## Troubleshooting:

### Jika Queue Worker Error:
```bash
# Lihat failed jobs
php artisan queue:failed

# Lihat log
tail -100 storage/logs/laravel.log

# Flush failed jobs jika sudah diperbaiki
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

### Jika Upload File Terlalu Besar:
Edit `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 600
```

Restart PHP-FPM atau Apache/Nginx.

## Yang Sudah Berubah:

1. **Work Category Logic**:
   - Sabtu, Minggu = "Libur"
   - Senin, Rabu = "WFA"
   - Selasa, Kamis, Jumat = "WFO"

2. **Day Name**: Sekarang dalam bahasa Indonesia (Senin-Minggu)

3. **Inject Mapping**: Tetap di-load ke database tapi tidak dihitung di summary aktivitas

4. **DMS Nasional**: Otomatis menghitung statistik nasional setelah semua instansi selesai dihitung
