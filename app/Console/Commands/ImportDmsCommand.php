<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DmsUpload;
use App\Jobs\ImportDmsCsvJob;

class ImportDmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dms:import {file} {--description=Import via CLI}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import DMS CSV file from command line (for large files)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $description = $this->option('description');

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Get file info
        $fileSize = filesize($filePath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        $this->info("File: {$filePath}");
        $this->info("Size: {$fileSizeMB} MB");
        $this->line('');

        // Confirm import
        if (!$this->confirm('Do you want to proceed with the import?', true)) {
            $this->warn('Import cancelled.');
            return 0;
        }

        // Copy file to storage/app/dms-uploads
        $filename = 'dms_cli_' . date('Ymd_His') . '.csv';
        $targetPath = storage_path('app/dms-uploads');

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0775, true);
        }

        $targetFile = $targetPath . '/' . $filename;

        $this->info('Copying file to storage...');
        $bar = $this->output->createProgressBar(100);
        $bar->start();

        if (!copy($filePath, $targetFile)) {
            $this->error("\nFailed to copy file to storage.");
            return 1;
        }

        $bar->finish();
        $this->info("\nFile copied successfully.");
        $this->line('');

        // Create DmsUpload record
        $this->info('Creating upload record...');

        $upload = DmsUpload::create([
            'filename' => $filename,
            'upload_date' => now(),
            'total_records' => 0,
            'processed_records' => 0,
            'status' => 'pending',
        ]);

        $this->info("Upload ID: {$upload->id}");

        // Dispatch job
        $this->info('Dispatching import job...');

        ImportDmsCsvJob::dispatch($upload->id, $targetFile, now());

        $this->line('');
        $this->info('✓ Job dispatched successfully!');
        $this->line('');
        $this->warn('The import is running in the background.');
        $this->warn('Make sure queue worker is running: php artisan queue:work');
        $this->line('');
        $this->info("Monitor progress at: http://your-server/dashboard-dms");

        return 0;
    }
}
