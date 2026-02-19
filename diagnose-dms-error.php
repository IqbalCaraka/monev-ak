<?php
/**
 * DMS Import Diagnostic Script
 * Run this on the server to diagnose import issues
 *
 * Usage: php diagnose-dms-error.php
 */

echo "=== DMS Import Diagnostics ===\n\n";

// 1. Check if DmsScoreCalculator exists
echo "1. Checking DmsScoreCalculator service...\n";
$calculatorPath = __DIR__ . '/app/Services/DmsScoreCalculator.php';
if (file_exists($calculatorPath)) {
    echo "   ✓ DmsScoreCalculator.php exists\n";
    echo "   Path: $calculatorPath\n";
} else {
    echo "   ✗ DmsScoreCalculator.php NOT FOUND!\n";
    echo "   Expected path: $calculatorPath\n";
    echo "   ACTION: Run 'git pull' to get latest code\n";
}
echo "\n";

// 2. Check if storage/app/dms-uploads directory exists
echo "2. Checking dms-uploads directory...\n";
$uploadsPath = __DIR__ . '/storage/app/dms-uploads';
if (is_dir($uploadsPath)) {
    echo "   ✓ Directory exists: $uploadsPath\n";

    // List files in directory
    $files = scandir($uploadsPath);
    $csvFiles = array_filter($files, fn($f) => str_ends_with($f, '.csv'));

    echo "   CSV files found: " . count($csvFiles) . "\n";
    foreach ($csvFiles as $file) {
        $filePath = $uploadsPath . '/' . $file;
        $fileSize = filesize($filePath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        echo "   - $file ($fileSizeMB MB)\n";
    }
} else {
    echo "   ✗ Directory NOT FOUND!\n";
    echo "   ACTION: Create directory with: mkdir -p $uploadsPath\n";
}
echo "\n";

// 3. Check PHP memory limit
echo "3. Checking PHP configuration...\n";
$memoryLimit = ini_get('memory_limit');
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$maxExecTime = ini_get('max_execution_time');

echo "   Memory limit: $memoryLimit\n";
echo "   Upload max: $uploadMax\n";
echo "   Post max: $postMax\n";
echo "   Max execution time: $maxExecTime seconds\n";

if (preg_match('/(\d+)/', $memoryLimit, $matches)) {
    $memMB = (int)$matches[1];
    if ($memMB < 2048) {
        echo "   ⚠ WARNING: Memory limit ($memoryLimit) may be too low for large files!\n";
        echo "   RECOMMENDED: Set to 2048M or higher in php.ini\n";
    } else {
        echo "   ✓ Memory limit is adequate\n";
    }
}
echo "\n";

// 4. Check database connection
echo "4. Checking database connection...\n";
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $pdo = DB::connection()->getPdo();
    echo "   ✓ Database connection successful\n";
    echo "   Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";

    // Check failed jobs
    echo "\n5. Checking failed jobs...\n";
    $failedJobs = DB::table('failed_jobs')
        ->orderBy('id', 'desc')
        ->limit(1)
        ->get();

    if ($failedJobs->count() > 0) {
        $job = $failedJobs->first();
        echo "   ⚠ Found failed job!\n";
        echo "   UUID: {$job->uuid}\n";
        echo "   Queue: {$job->queue}\n";
        echo "   Failed at: {$job->failed_at}\n";
        echo "\n   === ERROR MESSAGE (First 500 chars) ===\n";
        echo "   " . substr($job->exception, 0, 500) . "\n";
        echo "   =====================================\n";

        // Save full error to file
        $errorFile = __DIR__ . '/dms-error-full.log';
        file_put_contents($errorFile, $job->exception);
        echo "\n   Full error saved to: $errorFile\n";
        echo "   View with: cat $errorFile\n";
    } else {
        echo "   ✓ No failed jobs found\n";
    }

} catch (Exception $e) {
    echo "   ✗ Database connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "\nNext steps:\n";
echo "1. If DmsScoreCalculator is missing: Run 'git pull'\n";
echo "2. If directory is missing: Create it with proper permissions\n";
echo "3. If memory is low: Edit php.ini and restart web server\n";
echo "4. Check the error message above for specific issue\n";
echo "5. If queue worker is not running: Start it with 'php artisan queue:work'\n";
