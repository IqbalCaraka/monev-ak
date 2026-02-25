# Quick Deployment Guide - Server Production

## 🚀 Langkah Cepat (5 Menit)

### 1️⃣ Backup Database
```bash
mysqldump -u root -p nama_database > backup_$(date +%Y%m%d).sql
```

### 2️⃣ Pull Code
```bash
cd /path/to/monev_dit_ak
git pull origin main
```

### 3️⃣ Run Migration
```bash
php artisan migrate
```
⚠️ **Migration ini akan TRUNCATE `pegawai_aktivitas_summary` dan re-populate (1-3 menit)**

### 4️⃣ Clear Cache
```bash
php artisan optimize:clear
```

### 5️⃣ Test di Browser
- ✅ `/statistik/aktivitas-pegawai` - Cek "Inject - Mapping Dokumen" ada di Top 5
- ✅ `/pengaturan/pic/1` - Cek Total = Mapping + Inject

---

## ✅ Yang Berubah:

1. **Query lebih cepat** - Pakai `is_inject` (indexed) bukan `LIKE '%inject%'`
2. **Summary table lengkap** - Ada kategori "Inject - Mapping Dokumen"
3. **PIC stats akurat** - Total = Mapping + Inject saja
4. **Jenis Aktivitas fix** - Hitung kategori, bukan unique PNS

---

## 🔄 Rollback (Jika Bermasalah):

```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Restore database
mysql -u root -p nama_database < backup_YYYYMMDD.sql

# Rollback code
git reset --hard HEAD~1
```

---

## 📞 Jika Ada Error:

```bash
# Cek log
tail -f storage/logs/laravel.log

# Re-run migration
php artisan migrate:refresh --path=database/migrations/2026_02_25_163945_restore_inject_mapping_dokumen_to_summary.php
```
