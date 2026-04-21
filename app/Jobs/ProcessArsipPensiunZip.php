<?php

namespace App\Jobs;

use App\Models\ArsipPensiunUpload;
use App\Models\ArsipPensiunFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class ProcessArsipPensiunZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    protected $uploadId;
    protected $zipPath;

    private $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif'];

    public function __construct($uploadId, $zipPath)
    {
        $this->uploadId = $uploadId;
        $this->zipPath = $zipPath;
    }

    public function handle(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $upload = ArsipPensiunUpload::find($this->uploadId);
        if (!$upload) {
            Log::error("Arsip Pensiun: Upload ID {$this->uploadId} not found");
            return;
        }

        $upload->update([
            'status' => 'extracting',
            'message' => 'Sedang mengekstrak file...',
        ]);

        try {
            $extractDir = storage_path('app/arsip_pensiun/' . $this->uploadId);
            if (!file_exists($extractDir)) {
                mkdir($extractDir, 0755, true);
            }

            $ext = strtolower(pathinfo($this->zipPath, PATHINFO_EXTENSION));

            if ($ext === 'rar') {
                $processedFiles = $this->extractRar($upload, $extractDir);
            } else {
                $processedFiles = $this->extractZip($upload, $extractDir);
            }

            $upload->update([
                'status' => 'completed',
                'message' => "Selesai! {$processedFiles} dokumen berhasil diekstrak.",
                'jumlah_dokumen' => $processedFiles,
            ]);

            Log::info("Arsip Pensiun: Upload {$this->uploadId} completed. {$processedFiles} files extracted.");

        } catch (\Exception $e) {
            Log::error("Arsip Pensiun: Upload {$this->uploadId} failed: " . $e->getMessage());

            $upload->update([
                'status' => 'failed',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function extractZip($upload, $extractDir): int
    {
        $zip = new ZipArchive();
        $result = $zip->open($this->zipPath);

        if ($result !== true) {
            throw new \Exception('Gagal membuka file ZIP (error code: ' . $result . ')');
        }

        $totalFiles = $zip->numFiles;
        $processedFiles = 0;
        $batch = [];

        for ($i = 0; $i < $totalFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            if (substr($filename, -1) === '/' || strpos(basename($filename), '.') === 0) {
                continue;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedExtensions)) {
                continue;
            }

            $storedFilename = Str::uuid() . '.' . $ext;
            $filePath = 'arsip_pensiun/' . $this->uploadId . '/' . $storedFilename;
            $fullPath = $extractDir . '/' . $storedFilename;

            $content = $zip->getFromIndex($i);
            if ($content === false) {
                continue;
            }

            file_put_contents($fullPath, $content);
            unset($content);

            $batch[] = [
                'upload_id' => $this->uploadId,
                'original_filename' => basename($filename),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'file_size' => filesize($fullPath),
                'mime_type' => $this->getMimeType($ext),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $processedFiles++;

            if (count($batch) >= 500) {
                ArsipPensiunFile::insert($batch);
                $batch = [];

                $upload->update([
                    'message' => "Mengekstrak file... ({$processedFiles}/{$totalFiles})",
                    'jumlah_dokumen' => $processedFiles,
                ]);

                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {
            ArsipPensiunFile::insert($batch);
        }

        $zip->close();

        return $processedFiles;
    }

    private function extractRar($upload, $extractDir): int
    {
        // Extract RAR to temp directory using command line
        $tempExtractDir = $extractDir . '/_rar_temp';
        if (!file_exists($tempExtractDir)) {
            mkdir($tempExtractDir, 0755, true);
        }

        // Try common RAR extraction commands
        $extracted = false;
        $zipPath = str_replace('/', DIRECTORY_SEPARATOR, $this->zipPath);
        $tempDir = str_replace('/', DIRECTORY_SEPARATOR, $tempExtractDir);

        // Try UnRAR (WinRAR)
        $commands = [
            "unrar x -o+ \"{$zipPath}\" \"{$tempDir}\\\"",
            "\"C:\\Program Files\\WinRAR\\UnRAR.exe\" x -o+ \"{$zipPath}\" \"{$tempDir}\\\"",
            "\"C:\\Program Files (x86)\\WinRAR\\UnRAR.exe\" x -o+ \"{$zipPath}\" \"{$tempDir}\\\"",
            "7z x \"{$zipPath}\" -o\"{$tempDir}\" -y",
            "\"C:\\Program Files\\7-Zip\\7z.exe\" x \"{$zipPath}\" -o\"{$tempDir}\" -y",
        ];

        foreach ($commands as $cmd) {
            exec($cmd . ' 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                $extracted = true;
                break;
            }
        }

        if (!$extracted) {
            // Try PHP RarArchive if available
            if (class_exists('RarArchive')) {
                $rar = \RarArchive::open($this->zipPath);
                if ($rar) {
                    $entries = $rar->getEntries();
                    foreach ($entries as $entry) {
                        if (!$entry->isDirectory()) {
                            $entry->extract($tempExtractDir);
                        }
                    }
                    $rar->close();
                    $extracted = true;
                }
            }
        }

        if (!$extracted) {
            throw new \Exception('Gagal mengekstrak file RAR. Pastikan UnRAR atau 7-Zip terinstall di server.');
        }

        // Process extracted files
        $processedFiles = 0;
        $batch = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempExtractDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) continue;

            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, $this->allowedExtensions)) continue;

            $storedFilename = Str::uuid() . '.' . $ext;
            $filePath = 'arsip_pensiun/' . $this->uploadId . '/' . $storedFilename;
            $destPath = $extractDir . '/' . $storedFilename;

            // Move file from temp to final location
            copy($fileInfo->getPathname(), $destPath);
            unlink($fileInfo->getPathname());

            $batch[] = [
                'upload_id' => $this->uploadId,
                'original_filename' => $fileInfo->getFilename(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'file_size' => filesize($destPath),
                'mime_type' => $this->getMimeType($ext),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $processedFiles++;

            if (count($batch) >= 500) {
                ArsipPensiunFile::insert($batch);
                $batch = [];

                $upload->update([
                    'message' => "Mengekstrak file RAR... ({$processedFiles} file)",
                    'jumlah_dokumen' => $processedFiles,
                ]);

                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {
            ArsipPensiunFile::insert($batch);
        }

        // Cleanup temp directory
        $this->deleteDirectory($tempExtractDir);

        return $processedFiles;
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function failed(\Throwable $exception): void
    {
        ArsipPensiunUpload::where('id', $this->uploadId)->update([
            'status' => 'failed',
            'message' => 'Error: ' . $exception->getMessage(),
        ]);
    }

    private function getMimeType($ext): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
        ];

        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
}
