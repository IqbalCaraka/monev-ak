# IMPLEMENTASI SISTEM DMS SCORING - STEP BY STEP

## ✅ SUDAH SELESAI
1. ✅ Migrations (3 tables: dms_uploads, dms_pns_scores, dms_instansi_scores)
2. ✅ Migrations sudah di-run

---

## 📋 LANGKAH SELANJUTNYA

### **STEP 2: CREATE MODELS**

#### 2.1. Create Model DmsUpload
```bash
php artisan make:model DmsUpload
```

**File: `app/Models/DmsUpload.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsUpload extends Model
{
    protected $fillable = [
        'filename',
        'upload_date',
        'total_records',
        'processed_records',
        'status',
        'error_message',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
    ];

    // Relationship
    public function pnsScores()
    {
        return $this->hasMany(DmsPnsScore::class, 'upload_id');
    }

    public function instansiScores()
    {
        return $this->hasMany(DmsInstansiScore::class, 'upload_id');
    }

    // Helper method
    public function getProgressPercentage()
    {
        return $this->total_records > 0
            ? round(($this->processed_records / $this->total_records) * 100, 2)
            : 0;
    }
}
```

#### 2.2. Create Model DmsPnsScore
```bash
php artisan make:model DmsPnsScore
```

**File: `app/Models/DmsPnsScore.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsPnsScore extends Model
{
    protected $fillable = [
        'upload_id',
        'pns_id',
        'nip',
        'nama',
        'status_cpns_pns',
        'status_arsip',
        'skor_system',
        'instansi_id',
        'instansi_nama',
        'upload_date',
    ];

    protected $casts = [
        'status_arsip' => 'array', // Auto decode JSON
        'upload_date' => 'datetime',
        'skor_system' => 'decimal:2',
    ];

    // Relationship
    public function upload()
    {
        return $this->belongsTo(DmsUpload::class, 'upload_id');
    }
}
```

#### 2.3. Create Model DmsInstansiScore
```bash
php artisan make:model DmsInstansiScore
```

**File: `app/Models/DmsInstansiScore.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsInstansiScore extends Model
{
    protected $fillable = [
        'upload_id',
        'instansi_id',
        'instansi_nama',
        'upload_date',
        'total_pns',
        'avg_skor_calculated',
        'min_skor_calculated',
        'max_skor_calculated',
        'avg_skor_system',
        'calculation_status',
        'calculated_at',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
        'calculated_at' => 'datetime',
        'avg_skor_calculated' => 'decimal:2',
        'min_skor_calculated' => 'decimal:2',
        'max_skor_calculated' => 'decimal:2',
        'avg_skor_system' => 'decimal:2',
    ];

    // Relationship
    public function upload()
    {
        return $this->belongsTo(DmsUpload::class, 'upload_id');
    }
}
```

---

### **STEP 3: CREATE QUEUE JOB UNTUK IMPORT**

#### 3.1. Create Job
```bash
php artisan make:job ImportDmsCsvJob
```

**File: `app/Jobs/ImportDmsCsvJob.php`**
```php
<?php

namespace App\Jobs;

use App\Models\DmsUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Exception;

class ImportDmsCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 3;

    protected $uploadId;
    protected $filePath;
    protected $uploadDate;

    public function __construct($uploadId, $filePath, $uploadDate)
    {
        $this->uploadId = $uploadId;
        $this->filePath = $filePath;
        $this->uploadDate = $uploadDate;
    }

    public function handle()
    {
        try {
            $upload = DmsUpload::find($this->uploadId);
            $upload->update(['status' => 'processing']);

            $file = fopen($this->filePath, 'r');

            // Read header
            $header = fgetcsv($file);

            $chunk = [];
            $chunkSize = 10000;
            $totalProcessed = 0;

            while (($row = fgetcsv($file)) !== false) {
                // Combine header with row data
                $data = array_combine($header, $row);

                $chunk[] = [
                    'upload_id' => $this->uploadId,
                    'pns_id' => $data['id'],
                    'nip' => $data['nip'],
                    'nama' => $data['nama'],
                    'status_cpns_pns' => $data['status_cpns_pns'],
                    'status_arsip' => $data['status_arsip'], // Keep as JSON string
                    'skor_system' => !empty($data['skor_arsip_2026']) ? $data['skor_arsip_2026'] : null,
                    'instansi_id' => $data['instansi_induk_id'],
                    'instansi_nama' => $data['instansi_nama'],
                    'upload_date' => $this->uploadDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Insert when chunk is full
                if (count($chunk) >= $chunkSize) {
                    DB::table('dms_pns_scores')->insert($chunk);
                    $totalProcessed += count($chunk);

                    // Update progress
                    $upload->increment('processed_records', count($chunk));

                    $chunk = [];
                }
            }

            // Insert remaining rows
            if (!empty($chunk)) {
                DB::table('dms_pns_scores')->insert($chunk);
                $totalProcessed += count($chunk);
                $upload->increment('processed_records', count($chunk));
            }

            fclose($file);

            // Update upload status
            $upload->update([
                'status' => 'completed',
                'total_records' => $totalProcessed,
            ]);

        } catch (Exception $e) {
            $upload = DmsUpload::find($this->uploadId);
            $upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

### **STEP 4: CREATE QUEUE JOB UNTUK CALCULATE**

#### 4.1. Create Job
```bash
php artisan make:job CalculateInstansiScoreJob
```

**File: `app/Jobs/CalculateInstansiScoreJob.php`**
```php
<?php

namespace App\Jobs;

use App\Models\DmsPnsScore;
use App\Models\DmsInstansiScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateInstansiScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    protected $uploadId;
    protected $instansiId;

    public function __construct($uploadId, $instansiId)
    {
        $this->uploadId = $uploadId;
        $this->instansiId = $instansiId;
    }

    public function handle()
    {
        // Mark as calculating
        DmsInstansiScore::updateOrCreate(
            [
                'upload_id' => $this->uploadId,
                'instansi_id' => $this->instansiId,
            ],
            ['calculation_status' => 'calculating']
        );

        // Get all PNS for this instansi
        $pnsRecords = DmsPnsScore::where('upload_id', $this->uploadId)
            ->where('instansi_id', $this->instansiId)
            ->get();

        if ($pnsRecords->isEmpty()) {
            return;
        }

        $scoresCalculated = [];
        $scoresSystem = [];

        foreach ($pnsRecords as $pns) {
            $skorCalculated = $this->calculateScore($pns->status_arsip);
            $scoresCalculated[] = $skorCalculated;
            $scoresSystem[] = $pns->skor_system ?? 0;
        }

        // Calculate aggregates
        $avgCalculated = round(array_sum($scoresCalculated) / count($scoresCalculated), 2);
        $minCalculated = round(min($scoresCalculated), 2);
        $maxCalculated = round(max($scoresCalculated), 2);
        $avgSystem = round(array_sum($scoresSystem) / count($scoresSystem), 2);

        // Save to database
        DmsInstansiScore::updateOrCreate(
            [
                'upload_id' => $this->uploadId,
                'instansi_id' => $this->instansiId,
            ],
            [
                'instansi_nama' => $pnsRecords->first()->instansi_nama,
                'upload_date' => $pnsRecords->first()->upload_date,
                'total_pns' => count($pnsRecords),
                'avg_skor_calculated' => $avgCalculated,
                'min_skor_calculated' => $minCalculated,
                'max_skor_calculated' => $maxCalculated,
                'avg_skor_system' => $avgSystem,
                'calculation_status' => 'completed',
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Calculate DMS score from status_arsip JSON
     * Same logic as PerhitunganSkorArsipController
     */
    private function calculateScore($statusArsip): float
    {
        if (is_string($statusArsip)) {
            $status = json_decode($statusArsip, true);
        } else {
            $status = $statusArsip;
        }

        if (!$status || !is_array($status)) {
            return 0.00;
        }

        $totalScore = 0;
        $totalItems = 0;

        // Iterate through all categories
        foreach ($status as $category => $items) {
            if (!is_array($items)) continue;

            foreach ($items as $key => $value) {
                $totalItems++;
                // 1 = complete, 0 = incomplete
                $totalScore += ($value == 1) ? 1 : 0;
            }
        }

        return $totalItems > 0
            ? round(($totalScore / $totalItems) * 100, 2)
            : 0.00;
    }
}
```

---

### **STEP 5: CREATE CONTROLLER**

#### 5.1. Create Controller
```bash
php artisan make:controller DmsController
```

**File: `app/Http/Controllers/DmsController.php`**
```php
<?php

namespace App\Http\Controllers;

use App\Models\DmsUpload;
use App\Models\DmsPnsScore;
use App\Models\DmsInstansiScore;
use App\Jobs\ImportDmsCsvJob;
use App\Jobs\CalculateInstansiScoreJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DmsController extends Controller
{
    /**
     * Show upload form and history
     */
    public function index()
    {
        $uploads = DmsUpload::orderBy('upload_date', 'desc')->paginate(10);
        return view('dms.index', compact('uploads'));
    }

    /**
     * Process CSV upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:512000', // Max 500MB
        ]);

        $file = $request->file('csv_file');
        $filename = 'dms_' . now()->format('Ymd_His') . '.csv';
        $path = $file->storeAs('dms-uploads', $filename);

        // Create upload record
        $upload = DmsUpload::create([
            'filename' => $filename,
            'upload_date' => now(),
            'total_records' => 0,
            'processed_records' => 0,
            'status' => 'pending',
        ]);

        // Dispatch import job
        ImportDmsCsvJob::dispatch($upload->id, storage_path('app/' . $path), now());

        return redirect()->route('dms.index')
            ->with('success', 'Upload started! File is being processed in background. Refresh to see progress.');
    }

    /**
     * View upload details
     */
    public function show($uploadId)
    {
        $upload = DmsUpload::findOrFail($uploadId);

        // Get distinct instansi from this upload with counts
        $instansiList = DB::table('dms_pns_scores')
            ->where('upload_id', $uploadId)
            ->select('instansi_id', 'instansi_nama', DB::raw('COUNT(*) as total_pns'))
            ->groupBy('instansi_id', 'instansi_nama')
            ->orderBy('total_pns', 'desc')
            ->paginate(20);

        // Get calculation status for each instansi
        $calculatedStatus = DmsInstansiScore::where('upload_id', $uploadId)
            ->get()
            ->keyBy('instansi_id');

        foreach ($instansiList as $instansi) {
            $status = $calculatedStatus->get($instansi->instansi_id);
            $instansi->calculation_status = $status ? $status->calculation_status : 'pending';
            $instansi->avg_skor_calculated = $status ? $status->avg_skor_calculated : null;
            $instansi->avg_skor_system = $status ? $status->avg_skor_system : null;
        }

        return view('dms.show', compact('upload', 'instansiList'));
    }

    /**
     * Get upload progress (for AJAX polling)
     */
    public function progress($uploadId)
    {
        $upload = DmsUpload::findOrFail($uploadId);

        return response()->json([
            'status' => $upload->status,
            'processed' => $upload->processed_records,
            'total' => $upload->total_records,
            'percentage' => $upload->getProgressPercentage(),
        ]);
    }

    /**
     * Calculate score for specific instansi
     */
    public function calculateInstansi(Request $request)
    {
        $uploadId = $request->upload_id;
        $instansiId = $request->instansi_id;

        // Dispatch calculation job
        CalculateInstansiScoreJob::dispatch($uploadId, $instansiId);

        return response()->json([
            'success' => true,
            'message' => 'Calculation started for this instansi!'
        ]);
    }

    /**
     * Calculate ALL instansi for this upload
     */
    public function calculateAll($uploadId)
    {
        $instansiList = DB::table('dms_pns_scores')
            ->where('upload_id', $uploadId)
            ->select('instansi_id')
            ->distinct()
            ->get();

        foreach ($instansiList as $instansi) {
            CalculateInstansiScoreJob::dispatch($uploadId, $instansi->instansi_id);
        }

        return redirect()->back()
            ->with('success', count($instansiList) . ' calculations have been queued!');
    }

    /**
     * View instansi detail with scores
     */
    public function instansiDetail($uploadId, $instansiId)
    {
        $upload = DmsUpload::findOrFail($uploadId);
        $instansiScore = DmsInstansiScore::where('upload_id', $uploadId)
            ->where('instansi_id', $instansiId)
            ->first();

        // Get sample PNS (top 100)
        $pnsList = DmsPnsScore::where('upload_id', $uploadId)
            ->where('instansi_id', $instansiId)
            ->orderBy('nama')
            ->paginate(100);

        return view('dms.instansi-detail', compact('upload', 'instansiScore', 'pnsList'));
    }
}
```

---

### **STEP 6: ADD ROUTES**

**File: `routes/web.php`**

Tambahkan di bagian bawah sebelum closing:

```php
// DMS Routes
Route::prefix('dms')->group(function () {
    Route::get('/', [DmsController::class, 'index'])->name('dms.index');
    Route::post('/upload', [DmsController::class, 'upload'])->name('dms.upload');
    Route::get('/{uploadId}', [DmsController::class, 'show'])->name('dms.show');
    Route::get('/{uploadId}/progress', [DmsController::class, 'progress'])->name('dms.progress');
    Route::post('/calculate-instansi', [DmsController::class, 'calculateInstansi'])->name('dms.calculate-instansi');
    Route::post('/{uploadId}/calculate-all', [DmsController::class, 'calculateAll'])->name('dms.calculate-all');
    Route::get('/{uploadId}/instansi/{instansiId}', [DmsController::class, 'instansiDetail'])->name('dms.instansi-detail');
});
```

Jangan lupa import controller di atas:
```php
use App\Http\Controllers\DmsController;
```

---

### **STEP 7: CREATE VIEWS**

#### 7.1. Create View Index (Upload Form)

**File: `resources/views/dms/index.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'DMS Scoring - Upload')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Upload Data DMS PNS</h4>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('dms.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        @error('csv_file')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Format: CSV dengan kolom id, nip, nama, status_cpns_pns, status_arsip, skor_arsip_2026, instansi_induk_id, instansi_nama</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-upload"></i> Upload CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Upload History</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Upload Date</th>
                                <th>Filename</th>
                                <th>Total Records</th>
                                <th>Processed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uploads as $upload)
                                <tr>
                                    <td>{{ $upload->upload_date->format('d M Y H:i') }}</td>
                                    <td>{{ $upload->filename }}</td>
                                    <td>{{ number_format($upload->total_records) }}</td>
                                    <td>
                                        {{ number_format($upload->processed_records) }}
                                        @if($upload->status === 'processing')
                                            <span class="badge bg-info">{{ $upload->getProgressPercentage() }}%</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($upload->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($upload->status === 'processing')
                                            <span class="badge bg-warning">Processing...</span>
                                        @elseif($upload->status === 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-secondary">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($upload->status === 'completed')
                                            <a href="{{ route('dms.show', $upload->id) }}" class="btn btn-sm btn-primary">
                                                <i class="mdi mdi-eye"></i> View Details
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No uploads yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $uploads->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 7.2. Create View Detail (Show Upload)

**File: `resources/views/dms/show.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'DMS Upload Detail')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Upload: {{ $upload->filename }}</h4>
                        <p class="text-muted mb-0">{{ $upload->upload_date->format('d F Y H:i:s') }}</p>
                    </div>
                    <div>
                        <form action="{{ route('dms.calculate-all', $upload->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Calculate all instansi? This may take several minutes.')">
                                <i class="mdi mdi-calculator"></i> Calculate All Instansi
                            </button>
                        </form>
                        <a href="{{ route('dms.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Total Records</h6>
                                <h3>{{ number_format($upload->total_records) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Total Instansi</h6>
                                <h3>{{ $instansiList->total() }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Instansi List</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Instansi</th>
                                <th>Total PNS</th>
                                <th>Avg Calculated</th>
                                <th>Avg System</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instansiList as $instansi)
                                <tr>
                                    <td>{{ $instansi->instansi_nama }}</td>
                                    <td>{{ number_format($instansi->total_pns) }}</td>
                                    <td>
                                        @if($instansi->avg_skor_calculated)
                                            <span class="badge bg-primary">{{ number_format($instansi->avg_skor_calculated, 2) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->avg_skor_system)
                                            <span class="badge bg-secondary">{{ number_format($instansi->avg_skor_system, 2) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->calculation_status === 'completed')
                                            <span class="badge bg-success">Calculated</span>
                                        @elseif($instansi->calculation_status === 'calculating')
                                            <span class="badge bg-warning">Calculating...</span>
                                        @else
                                            <span class="badge bg-secondary">Not Calculated</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->calculation_status !== 'calculating')
                                            <button class="btn btn-sm btn-primary btn-calculate"
                                                    data-upload-id="{{ $upload->id }}"
                                                    data-instansi-id="{{ $instansi->instansi_id }}">
                                                <i class="mdi mdi-calculator"></i> Calculate
                                            </button>
                                        @endif
                                        @if($instansi->calculation_status === 'completed')
                                            <a href="{{ route('dms.instansi-detail', [$upload->id, $instansi->instansi_id]) }}"
                                               class="btn btn-sm btn-info">
                                                <i class="mdi mdi-eye"></i> Detail
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $instansiList->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-calculate').forEach(btn => {
    btn.addEventListener('click', function() {
        const uploadId = this.dataset.uploadId;
        const instansiId = this.dataset.instansiId;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculating...';

        fetch('{{ route("dms.calculate-instansi") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                upload_id: uploadId,
                instansi_id: instansiId
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => {
            alert('Error: ' + error);
            this.disabled = false;
            this.innerHTML = '<i class="mdi mdi-calculator"></i> Calculate';
        });
    });
});
</script>
@endsection
```

---

### **STEP 8: UPDATE SIDEBAR MENU**

**File: `resources/views/layouts/sidebar.blade.php`**

Tambahkan menu DMS di bagian Dashboard:

```blade
<li class="nav-item">
    <a class="nav-link" href="{{ route('dms.index') }}">
        <i class="menu-icon mdi mdi-file-chart"></i>
        <span class="menu-title">DMS Scoring</span>
    </a>
</li>
```

---

### **STEP 9: SETUP QUEUE**

#### 9.1. Update .env
```env
QUEUE_CONNECTION=database
```

#### 9.2. Create jobs table
```bash
php artisan queue:table
php artisan migrate
```

#### 9.3. Run queue worker (di terminal terpisah)
```bash
php artisan queue:work --queue=default --timeout=3600
```

**PENTING**: Queue worker harus tetap running! Bisa gunakan supervisor atau screen di production.

---

## 🚀 CARA PENGGUNAAN

### 1. Upload CSV
- Buka menu "DMS Scoring"
- Pilih file CSV (DataPNSKeseluruhan170226.csv)
- Klik "Upload CSV"
- Akan muncul di Upload History dengan status "Processing"

### 2. Monitor Progress
- Refresh halaman untuk melihat progress
- Status akan berubah menjadi "Completed" setelah selesai

### 3. Calculate Scores
- Klik "View Details" pada upload yang completed
- Akan muncul list instansi
- Klik "Calculate" pada instansi yang ingin dihitung
- Atau klik "Calculate All Instansi" untuk hitung semua sekaligus

### 4. View Results
- Setelah calculation completed, klik "Detail" untuk melihat hasil
- Akan tampil avg score, min, max, dll

---

## ⚡ PERFORMANCE TIPS

### 1. Increase PHP Memory Limit
Di `php.ini`:
```ini
memory_limit = 512M
max_execution_time = 3600
```

### 2. Database Optimization
Indexes sudah ada di migration, tapi pastikan MySQL tuning optimal:
```sql
-- Check index usage
SHOW INDEX FROM dms_pns_scores;
```

### 3. Queue Monitoring
Monitor queue worker:
```bash
php artisan queue:monitor
```

---

## 🐛 TROUBLESHOOTING

### Problem: Upload stuck di "Processing"
**Solution:**
- Cek queue worker masih running
- Lihat log: `storage/logs/laravel.log`
- Restart queue worker: `php artisan queue:restart`

### Problem: Calculation tidak jalan
**Solution:**
- Cek queue worker
- Lihat failed jobs: `php artisan queue:failed`
- Retry failed: `php artisan queue:retry all`

### Problem: Out of memory
**Solution:**
- Increase PHP memory_limit
- Reduce chunk size di ImportDmsCsvJob (dari 10000 ke 5000)

---

## 📝 NEXT STEPS (OPTIONAL)

1. **Add Grafik** - Chart.js untuk visualisasi trend
2. **Export Results** - Export calculated scores to Excel
3. **Comparison** - Compare multiple uploads
4. **Notifications** - Email when processing complete
5. **API Endpoint** - REST API untuk mobile/external apps

---

## ✅ CHECKLIST

- [ ] Models created (3 files)
- [ ] Jobs created (2 files)
- [ ] Controller created
- [ ] Routes added
- [ ] Views created (2 files minimum)
- [ ] Sidebar menu updated
- [ ] Queue table migrated
- [ ] Queue worker running
- [ ] Test upload CSV
- [ ] Test calculation

---

**Estimated Time:**
- Models: 5 menit
- Jobs: 10 menit
- Controller: 10 menit
- Views: 15 menit
- Testing: 10 menit
**Total: ~50 menit**

**Good luck! 🚀**
