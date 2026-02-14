# Cara Update Data Existing di Server Production

## Prerequisite
Pastikan migration sudah dijalankan terlebih dahulu:
```bash
php artisan migrate
```

Migration akan menambahkan kolom `day_name` dan `work_category` beserta index-nya.

---

## Metode 1: Menggunakan Artisan Command (RECOMMENDED)

### Step 1: Jalankan Command
```bash
php artisan log:fill-day-work-category
```

### Step 2: Monitor Progress
Command akan menampilkan:
- Total records yang akan di-update
- Progress bar
- Summary hasil update berdasarkan kategori (WFA/WFO/Libur)

### Output yang Diharapkan:
```
Starting to fill day_name and work_category...
Found XXXXX records to update.
Processing in batches of 1000...
[Progress bar]

Successfully updated XXXXX records!

Summary by work category:
+---------------+--------+
| Work Category | Total  |
+---------------+--------+
| WFA           | XX,XXX |
| WFO           | XX,XXX |
| Libur         | XXX    |
+---------------+--------+
```

### Custom Batch Size (Optional):
Jika server memiliki keterbatasan memory, gunakan batch size lebih kecil:
```bash
php artisan log:fill-day-work-category --batch=500
```

---

## Metode 2: Menggunakan Tinker (Untuk Server Tanpa Artisan Command)

### Step 1: Masuk ke Tinker
```bash
php artisan tinker
```

### Step 2: Jalankan Update Script
```php
use Carbon\Carbon;

// Mapping hari
$dayMapping = [
    0 => ['name' => 'Minggu', 'type' => 'Libur'],
    1 => ['name' => 'Senin', 'type' => 'WFA'],
    2 => ['name' => 'Selasa', 'type' => 'WFO'],
    3 => ['name' => 'Rabu', 'type' => 'WFA'],
    4 => ['name' => 'Kamis', 'type' => 'WFO'],
    5 => ['name' => 'Jumat', 'type' => 'WFO'],
    6 => ['name' => 'Sabtu', 'type' => 'Libur']
];

// Update dalam batch
DB::table('log_aktivitas')
    ->whereNull('day_name')
    ->orderBy('id')
    ->chunk(1000, function ($records) use ($dayMapping) {
        foreach ($records as $record) {
            try {
                $dateField = $record->created_at_log ?? $record->created_at;
                $date = Carbon::parse($dateField);
                $dayInfo = $dayMapping[$date->dayOfWeek];

                DB::table('log_aktivitas')
                    ->where('id', $record->id)
                    ->update([
                        'day_name' => $dayInfo['name'],
                        'work_category' => $dayInfo['type']
                    ]);
            } catch (\Exception $e) {
                echo "Error processing ID: {$record->id}\n";
            }
        }
        echo "Processed 1000 records...\n";
    });

echo "Done!\n";
```

### Step 3: Verifikasi
```php
DB::table('log_aktivitas')
    ->select('work_category', DB::raw('COUNT(*) as total'))
    ->whereNotNull('work_category')
    ->groupBy('work_category')
    ->get();
```

---

## Metode 3: Direct SQL Query (Untuk MySQL CLI)

### Step 1: Koneksi ke Database
```bash
mysql -u username -p database_name
```

### Step 2: Jalankan Update Query
```sql
-- Update day_name dan work_category berdasarkan created_at_log
UPDATE log_aktivitas
SET
    day_name = CASE DAYOFWEEK(COALESCE(created_at_log, created_at))
        WHEN 1 THEN 'Minggu'
        WHEN 2 THEN 'Senin'
        WHEN 3 THEN 'Selasa'
        WHEN 4 THEN 'Rabu'
        WHEN 5 THEN 'Kamis'
        WHEN 6 THEN 'Jumat'
        WHEN 7 THEN 'Sabtu'
    END,
    work_category = CASE DAYOFWEEK(COALESCE(created_at_log, created_at))
        WHEN 1 THEN 'Libur'   -- Minggu
        WHEN 2 THEN 'WFA'     -- Senin
        WHEN 3 THEN 'WFO'     -- Selasa
        WHEN 4 THEN 'WFA'     -- Rabu
        WHEN 5 THEN 'WFO'     -- Kamis
        WHEN 6 THEN 'WFO'     -- Jumat
        WHEN 7 THEN 'Libur'   -- Sabtu
    END
WHERE day_name IS NULL;
```

### Step 3: Verifikasi Hasil
```sql
SELECT work_category, COUNT(*) as total
FROM log_aktivitas
WHERE work_category IS NOT NULL
GROUP BY work_category;

SELECT day_name, work_category, COUNT(*) as total
FROM log_aktivitas
WHERE day_name IS NOT NULL
GROUP BY day_name, work_category
ORDER BY day_name;
```

---

## Metode 4: Menggunakan PHP Script Standalone

Jika tidak bisa akses artisan command, gunakan file PHP:

### Step 1: Upload File `update_existing_data.php`
```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "Starting update process...\n";

$dayMapping = [
    0 => ['name' => 'Minggu', 'type' => 'Libur'],
    1 => ['name' => 'Senin', 'type' => 'WFA'],
    2 => ['name' => 'Selasa', 'type' => 'WFO'],
    3 => ['name' => 'Rabu', 'type' => 'WFA'],
    4 => ['name' => 'Kamis', 'type' => 'WFO'],
    5 => ['name' => 'Jumat', 'type' => 'WFO'],
    6 => ['name' => 'Sabtu', 'type' => 'Libur']
];

$totalRecords = DB::table('log_aktivitas')->whereNull('day_name')->count();
echo "Found {$totalRecords} records to update.\n";

$processed = 0;
DB::table('log_aktivitas')
    ->whereNull('day_name')
    ->orderBy('id')
    ->chunk(1000, function ($records) use ($dayMapping, &$processed, $totalRecords) {
        $updates = [];

        foreach ($records as $record) {
            $dateField = $record->created_at_log ?? $record->created_at;

            if ($dateField) {
                try {
                    $date = Carbon::parse($dateField);
                    $dayInfo = $dayMapping[$date->dayOfWeek];

                    $updates[] = [
                        'id' => $record->id,
                        'day_name' => $dayInfo['name'],
                        'work_category' => $dayInfo['type']
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Batch update using CASE WHEN
        if (!empty($updates)) {
            $caseDay = 'CASE id ';
            $caseWork = 'CASE id ';
            $ids = [];

            foreach ($updates as $update) {
                $id = DB::connection()->getPdo()->quote($update['id']);
                $dayName = DB::connection()->getPdo()->quote($update['day_name']);
                $workCategory = DB::connection()->getPdo()->quote($update['work_category']);

                $caseDay .= "WHEN {$id} THEN {$dayName} ";
                $caseWork .= "WHEN {$id} THEN {$workCategory} ";
                $ids[] = $id;
            }

            $caseDay .= 'END';
            $caseWork .= 'END';
            $idList = implode(',', $ids);

            $sql = "UPDATE log_aktivitas SET day_name = {$caseDay}, work_category = {$caseWork} WHERE id IN ({$idList})";
            DB::statement($sql);
        }

        $processed += count($records);
        $percentage = round(($processed / $totalRecords) * 100, 2);
        echo "Processed: {$processed}/{$totalRecords} ({$percentage}%)\n";
    });

echo "\nUpdate completed!\n\n";

// Show summary
$summary = DB::table('log_aktivitas')
    ->select('work_category', DB::raw('COUNT(*) as total'))
    ->whereNotNull('work_category')
    ->groupBy('work_category')
    ->get();

echo "Summary by work category:\n";
foreach ($summary as $cat) {
    echo "  {$cat->work_category}: " . number_format($cat->total) . "\n";
}
```

### Step 2: Jalankan via Browser atau CLI
**Via Browser:**
```
http://your-server.com/update_existing_data.php
```

**Via CLI:**
```bash
php update_existing_data.php
```

### Step 3: Hapus File Setelah Selesai (PENTING!)
```bash
rm update_existing_data.php
```

---

## Verifikasi Hasil Update

### Cek Total per Kategori:
```bash
php artisan tinker
```
```php
DB::table('log_aktivitas')
    ->select('work_category', DB::raw('COUNT(*) as total'))
    ->groupBy('work_category')
    ->get();
```

### Expected Output:
```
Illuminate\Support\Collection {
  all: [
    {
      work_category: "WFA",
      total: "XXXXX",
    },
    {
      work_category: "WFO",
      total: "XXXXX",
    },
    {
      work_category: "Libur",
      total: "XXX",
    },
  ],
}
```

### Cek Sample Data:
```php
DB::table('log_aktivitas')
    ->select('created_at_log', 'day_name', 'work_category')
    ->whereNotNull('day_name')
    ->limit(10)
    ->get();
```

---

## Troubleshooting

### Jika Command Tidak Ditemukan:
```bash
# Daftar semua command yang tersedia
php artisan list

# Jika log:fill-day-work-category tidak ada, gunakan Metode 3 atau 4
```

### Jika Memory Habis:
```bash
# Kurangi batch size
php artisan log:fill-day-work-category --batch=500

# Atau batch=100 untuk server dengan memory sangat terbatas
php artisan log:fill-day-work-category --batch=100
```

### Jika Timeout di Browser (Metode 4):
Edit `php.ini` atau tambahkan di script:
```php
set_time_limit(0); // No timeout
ini_set('memory_limit', '512M'); // Increase memory
```

---

## Kategori Work Type

**WFA (Work From Anywhere):**
- Senin
- Rabu

**WFO (Work From Office):**
- Selasa
- Kamis
- Jumat

**Libur:**
- Sabtu
- Minggu

---

## Catatan Penting

1. **Backup Database** sebelum menjalankan update di production!
2. Command ini **idempotent** - aman dijalankan berulang kali karena hanya update record dengan `day_name IS NULL`
3. Proses update menggunakan **created_at_log** (tanggal aktivitas sebenarnya), bukan `created_at` (Laravel timestamp)
4. Data yang sudah di-update tidak akan di-update ulang kecuali di-reset manual
5. Untuk **data import CSV selanjutnya**, otomatis sudah ter-handle di `AktivitasPegawaiController::uploadCsv()`

---

## Estimasi Waktu

| Jumlah Records | Batch 1000 | Batch 500 | Batch 100 |
|----------------|------------|-----------|-----------|
| 10,000         | ~30 detik  | ~45 detik | ~2 menit  |
| 50,000         | ~2 menit   | ~4 menit  | ~10 menit |
| 100,000        | ~5 menit   | ~8 menit  | ~20 menit |
| 500,000        | ~25 menit  | ~40 menit | ~2 jam    |

*Estimasi di server dengan spesifikasi standar (2 CPU, 4GB RAM)*
