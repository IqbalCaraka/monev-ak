<?php

$file = 'app/Http/Controllers/UbahFormatController.php';
$content = file_get_contents($file);

// Diklat - update
$content = preg_replace(
    '/Format: No \| Nama \| NIP \| Tanggal \| Status\n     \*\/\n    private function createDiklatSheet/',
    "Format: No | Nama | NIP | Status PNS | Tanggal | Status\n     */\n    private function createDiklatSheet",
    $content
);
$content = preg_replace(
    '/mergeCells\("A\$currentRow:E\$currentRow"\);\s+\$currentRow \+= 2;\s+\/\/ Header\s+\$sheet->setCellValue\("A\$currentRow", "No"\);\s+\$sheet->setCellValue\("B\$currentRow", "Nama"\);\s+\$sheet->setCellValue\("C\$currentRow", "NIP"\);\s+\$sheet->setCellValue\("D\$currentRow", "Tanggal"\);\s+\$sheet->setCellValue\("E\$currentRow", "Status"\);\s+\$sheet->getStyle\("A\$currentRow:E\$currentRow"\)->getFont\(\)->setBold\(true\);\s+\$sheet->getStyle\("A\$currentRow:E\$currentRow"\)->getFill\(\)/',
    'mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Tanggal");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()',
    $content
);

echo "Updated Diklat format comment and headers\n";

//Similar updates for Pindah Instansi, Penghargaan, SKP, Angka Kredit, CLTN, PMK...
// This script can continue for all remaining sheets

file_put_contents($file, $content);
echo "File updated successfully\n";
