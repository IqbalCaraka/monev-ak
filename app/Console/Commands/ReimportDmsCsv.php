<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DmsUpload;
use App\Jobs\ImportDmsCsvJob;

class ReimportDmsCsv extends Command
{
    protected $signature = 'dms:reimport {filename?}';
    protected $description = 'Re-import existing CSV file from storage/app/dms-uploads';

    public function handle()
    {
        $filename = $this->argument('filename');

        // Jika tidak ada argument, tampilkan list file yang tersedia
        if (!$filename) {
            $this->info('Available CSV files in storage/app/dms-uploads:');
            $files = glob(storage_path('app/dms-uploads/*.csv'));

            if (empty($files)) {
                $this->error('No CSV files found in storage/app/dms-uploads/');
                return 1;
            }

            foreach ($files as $file) {
                $basename = basename($file);
                $size = filesize($file);
                $this->line("  - {$basename} (" . number_format($size / 1024 / 1024, 2) . " MB)");
            }

            $this->info("\nUsage: php artisan dms:reimport <filename>");
            return 0;
        }

        // Cek apakah file ada
        $filePath = storage_path('app/dms-uploads/' . $filename);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Re-importing: {$filename}");

        // Buat record di dms_uploads
        $upload = DmsUpload::create([
            'filename' => $filename,
            'filepath' => 'dms-uploads/' . $filename,
            'upload_date' => now(),
            'status' => 'pending',
            'total_records' => 0,
            'processed_records' => 0,
            'uploaded_by' => 'system',
        ]);

        $this->info("Created upload record with ID: {$upload->id}");

        // Dispatch job
        ImportDmsCsvJob::dispatch($upload->id, $filePath, now());

        $this->info("Import job dispatched!");
        $this->info("Monitor progress at: http://127.0.0.1:8000/dms/{$upload->id}");
        $this->warn("Make sure queue worker is running: php artisan queue:work");

        return 0;
    }
}
