<?php
/**
 * Script untuk split CSV file besar jadi beberapa file kecil
 * Usage: php split-csv.php input.csv 50
 * (50 = ukuran max dalam MB per file)
 */

if ($argc < 3) {
    echo "Usage: php split-csv.php <input-file> <max-size-mb>\n";
    echo "Example: php split-csv.php DataPNSKeseluruhan170226.csv 50\n";
    exit(1);
}

$inputFile = $argv[1];
$maxSizeMB = (int)$argv[2];
$maxSizeBytes = $maxSizeMB * 1024 * 1024;

if (!file_exists($inputFile)) {
    echo "Error: File not found: $inputFile\n";
    exit(1);
}

echo "Splitting $inputFile into chunks of max {$maxSizeMB}MB each...\n\n";

$handle = fopen($inputFile, 'r');
$header = fgets($handle); // Read header

$partNum = 1;
$currentSize = 0;
$outputHandle = null;
$rowCount = 0;
$totalRows = 0;

while (!feof($handle)) {
    // Create new file if needed
    if ($outputHandle === null || $currentSize >= $maxSizeBytes) {
        if ($outputHandle !== null) {
            fclose($outputHandle);
            echo "Part $partNum: $rowCount rows (" . round($currentSize / 1024 / 1024, 2) . " MB)\n";
        }

        $outputFile = pathinfo($inputFile, PATHINFO_FILENAME) . "_part{$partNum}.csv";
        $outputHandle = fopen($outputFile, 'w');

        // Write header
        fwrite($outputHandle, $header);
        $currentSize = strlen($header);
        $rowCount = 0;
        $partNum++;
    }

    // Read and write line
    $line = fgets($handle);
    if ($line === false) break;

    fwrite($outputHandle, $line);
    $currentSize += strlen($line);
    $rowCount++;
    $totalRows++;

    // Progress indicator
    if ($totalRows % 10000 == 0) {
        echo "Processed: " . number_format($totalRows) . " rows\r";
    }
}

if ($outputHandle !== null) {
    fclose($outputHandle);
    echo "Part " . ($partNum - 1) . ": $rowCount rows (" . round($currentSize / 1024 / 1024, 2) . " MB)\n";
}

fclose($handle);

echo "\n";
echo "Done! Split into " . ($partNum - 1) . " files.\n";
echo "Total rows: " . number_format($totalRows) . "\n";
