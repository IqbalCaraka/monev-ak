# Deployment Checklist - Optimisasi Query & Fix Summary Table

## Perubahan yang Dilakukan (Development)

### 1. Query Optimization
- Ganti `LIKE '%inject%'` dengan `is_inject = 1` di 8 lokasi
- File: `app/Http/Controllers/AktivitasPegawaiController.php`
- File: `app/Http/Controllers/Api/MonevDmsApiController.php`

### 2. Restore Inject - Mapping Dokumen
- Migration: `2026_02_25_163945_restore_inject_mapping_dokumen_to_summary.php`
- TRUNCATE & re-populate tabel `pegawai_aktivitas_summary`
- Menggunakan kolom `is_inject` untuk deteksi inject

### 3. Fix Jenis Aktivitas
- Ganti hitungan dari `COUNT(DISTINCT object_pns_id)` ke `COUNT(DISTINCT kategori_aktivitas)`
- File: `app/Http/Controllers/AktivitasPegawaiController.php`
- File: `app/Http/Controllers/Api/MonevDmsApiController.php`

### 4. Fix Detail Kategori
- Tambah support untuk "Inject - Mapping Dokumen" dan "Inject - Unggah Dokumen"
- File: `app/Http/Controllers/AktivitasPegawaiController.php`

### 5. Fix PIC Stats
- Mapping = non-inject only
- Inject = both inject-mapping + inject-unggah
- Total = Mapping + Inject saja
- File: `app/Http/Controllers/PicController.php`
- File: `resources/views/pic/show.blade.php`

---

## Langkah Deployment di Server Production

### **STEP 1: Backup Database (PENTING!)**

```bash
# Backup database dulu sebelum migration
mysqldump -u root -p nama_database > backup_before_migration_$(date +%Y%m%d).sql

# Atau backup hanya tabel yang terpengaruh
mysqldump -u root -p nama_database pegawai_aktivitas_summary > backup_summary_table.sql
```

### **STEP 2: Pull Code dari Git**

```bash
cd /path/to/monev_dit_ak

# Stash perubahan lokal (jika ada)
git stash

# Pull update terbaru
git pull origin main

# Atau jika pakai branch lain
git pull origin nama-branch
```

### **STEP 3: Jalankan Migration**

```bash
# Jalankan migration baru
php artisan migrate

# Migration yang akan dijalankan:
# - 2026_02_25_163945_restore_inject_mapping_dokumen_to_summary.php
#   (TRUNCATE dan re-populate tabel pegawai_aktivitas_summary)
```

**⚠️ PENTING:** Migration ini akan **TRUNCATE tabel `pegawai_aktivitas_summary`** dan re-populate dengan data baru. Proses ini bisa memakan waktu **1-3 menit** tergantung ukuran data!

### **STEP 4: Clear Cache**

```bash
# Clear semua cache Laravel
php artisan optimize:clear

# Atau clear satu-satu
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### **STEP 5: Test di Browser**

#### Test 1: Halaman Aktivitas Pegawai
URL: `http://your-domain.com/statistik/aktivitas-pegawai`

**Cek:**
- ✅ Top 5 Kategori harus ada "Inject - Mapping Dokumen"
- ✅ Kolom "Jenis Aktivitas" menunjukkan angka kecil (5-12), bukan ratusan
- ✅ Search AJAX berfungsi tanpa scroll ke atas

#### Test 2: Detail Aktivitas per Kategori
URL: `http://your-domain.com/statistik/aktivitas-pegawai/{nip}/Inject - Mapping Dokumen`

**Cek:**
- ✅ Data muncul (tidak kosong)
- ✅ Hanya ada `event_name = 'mapping_dokumen'`

URL: `http://your-domain.com/statistik/aktivitas-pegawai/{nip}/Inject - Unggah Dokumen`

**Cek:**
- ✅ Data muncul (tidak kosong)
- ✅ Hanya ada `event_name = 'unggah_dokumen'`

#### Test 3: Halaman PIC Detail
URL: `http://your-domain.com/pengaturan/pic/{id}`

**Cek:**
- ✅ Label: "Total Aktivitas Mapping dan Inject"
- ✅ Total = Mapping + Inject (konsisten)
- ✅ Inject termasuk inject-mapping + inject-unggah

### **STEP 6: Verifikasi Summary Table**

```bash
# Masuk ke MySQL
mysql -u root -p nama_database

# Cek kategori di summary table
SELECT
    kategori_aktivitas,
    COUNT(DISTINCT nip) as jumlah_pegawai,
    SUM(total_aktivitas) as total
FROM pegawai_aktivitas_summary
GROUP BY kategori_aktivitas
ORDER BY total DESC;
```

**Expected Result:**
```
+-------------------------------+----------------+--------+
| kategori_aktivitas            | jumlah_pegawai | total  |
+-------------------------------+----------------+--------+
| Inject - Mapping Dokumen      | 13             | 58243  | ← HARUS ADA!
| Inject - Unggah Dokumen       | 13             | 58243  |
| Mapping Dokumen               | 26             | 42042  |
| Lock Arsip                    | 27             | 17528  |
| ...                           | ...            | ...    |
+-------------------------------+----------------+--------+
```

---

## Troubleshooting

### Problem 1: Migration Gagal
**Error:** `SQLSTATE[42S02]: Base table or view not found`

**Solusi:**
```bash
# Cek migration status
php artisan migrate:status

# Rollback dan coba lagi
php artisan migrate:rollback
php artisan migrate
```

### Problem 2: "Inject - Mapping Dokumen" Masih Tidak Ada
**Solusi:**
```bash
# Re-run migration yang restore summary table
php artisan migrate:refresh --path=database/migrations/2026_02_25_163945_restore_inject_mapping_dokumen_to_summary.php
```

### Problem 3: Performance Lambat Setelah Migration
**Kemungkinan:** Query belum pakai index

**Solusi:**
```sql
-- Cek index di log_aktivitas
SHOW INDEX FROM log_aktivitas WHERE Column_name = 'is_inject';

-- Jika tidak ada, tambahkan
ALTER TABLE log_aktivitas ADD INDEX idx_is_inject (is_inject);
```

**Tapi index harusnya sudah ada dari migration sebelumnya:**
- Migration: `2026_02_20_142516_add_is_inject_column_to_log_aktivitas.php`

### Problem 4: Cache Tidak Ter-clear
**Solusi:**
```bash
# Hapus manual folder cache
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Rollback Plan (Jika Ada Masalah)

### Rollback Migration
```bash
# Rollback migration terakhir
php artisan migrate:rollback --step=1

# Restore database dari backup
mysql -u root -p nama_database < backup_before_migration_YYYYMMDD.sql
```

### Rollback Code
```bash
# Kembali ke commit sebelumnya
git log --oneline  # Cari commit hash sebelum update
git reset --hard <commit-hash>

# Atau revert ke branch sebelumnya
git checkout main
git pull origin main
```

---

## Performance Impact

### Before Optimization:
- Query: `WHERE details LIKE '%inject%'` (SLOW - full table scan)
- Summary table: Tidak ada "Inject - Mapping Dokumen"

### After Optimization:
- Query: `WHERE is_inject = 1` (FAST - uses index)
- Summary table: Lengkap dengan semua kategori inject
- Estimated speedup: **5-10x faster** untuk query inject detection

---

## Files Changed

### Controllers:
1. `app/Http/Controllers/AktivitasPegawaiController.php`
2. `app/Http/Controllers/Api/MonevDmsApiController.php`
3. `app/Http/Controllers/PicController.php`

### Views:
1. `resources/views/pic/show.blade.php`

### Migrations:
1. `database/migrations/2026_02_25_163945_restore_inject_mapping_dokumen_to_summary.php`

---

## Contact

Jika ada masalah saat deployment, hubungi developer atau cek log:

```bash
# Cek Laravel log
tail -f storage/logs/laravel.log

# Cek PHP error log
tail -f /var/log/php-fpm/error.log  # atau /var/log/apache2/error.log
```

---

## Estimated Downtime

- **Migration runtime**: 1-3 menit (tergantung ukuran data)
- **Total downtime**: **Minimal** (bisa dilakukan tanpa downtime jika pakai queue)
- **Recommended**: Deploy saat traffic rendah (malam hari)
