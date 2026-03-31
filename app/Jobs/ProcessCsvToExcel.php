<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProcessCsvToExcel implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Only try once

    protected $csvPath;
    protected $userId;
    protected $jobId;

    /**
     * Mapping golongan ID ke nama golongan
     */
    private $golonganMap = [
        '11' => 'I/A', '12' => 'I/B', '13' => 'I/C', '14' => 'I/D',
        '21' => 'II/A', '22' => 'II/B', '23' => 'II/C', '24' => 'II/D',
        '31' => 'III/A', '32' => 'III/B', '33' => 'III/C', '34' => 'III/D',
        '41' => 'IV/A', '42' => 'IV/B', '43' => 'IV/C', '44' => 'IV/D', '45' => 'IV/E',
    ];

    /**
     * Mapping pendidikan ID ke nama pendidikan
     */
    private $pendidikanMap = [
        '05' => 'SD',
        '10' => 'SLTP',
        '12' => 'SLTP Kejuruan',
        '15' => 'SLTA',
        '17' => 'SLTA Kejuruan',
        '18' => 'SLTA Keguruan',
        '20' => 'Diploma I',
        '25' => 'Diploma II',
        '30' => 'Diploma III',
        '35' => 'Diploma IV',
        '40' => 'S-1/Sarjana',
        '42' => 'Profesi',
        '45' => 'S-2',
        '47' => 'Spesialis',
        '49' => 'Subspesialis',
        '50' => 'S-3/Doktor',
    ];

    /**
     * Create a new job instance.
     */
    public function __construct($csvPath, $userId, $jobId)
    {
        $this->csvPath = $csvPath;
        $this->userId = $userId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set unlimited execution time and increase memory
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        try {
            // Update status to processing
            $this->updateJobStatus('processing', 'Memproses file CSV...');

            // Read and parse CSV
            $data = $this->parseCsv();

            $this->updateJobStatus('processing', 'Menggenerate Excel... (' . count($data) . ' baris)');

            // Generate Excel
            $excelFilename = $this->generateExcel($data);

            // Update status to completed
            $this->updateJobStatus('completed', 'Selesai!', $excelFilename);

        } catch (\Exception $e) {
            // Update status to failed
            $this->updateJobStatus('failed', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsv()
    {
        $fullPath = storage_path('app/' . $this->csvPath);

        // Check if file exists
        if (!file_exists($fullPath)) {
            throw new \Exception("File CSV tidak ditemukan: {$fullPath}. Path yang dicari: " . $this->csvPath);
        }

        // Check if file is readable
        if (!is_readable($fullPath)) {
            throw new \Exception("File CSV tidak dapat dibaca: {$fullPath}. Check file permissions.");
        }

        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            throw new \Exception("Gagal membuka file CSV: {$fullPath}");
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);

        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $delimiter = ($semicolonCount > $commaCount) ? ';' : ',';

        // Skip header
        $header = fgetcsv($handle, 0, $delimiter);

        // Find column indices
        $statusArsipIndex = array_search('status_arsip', $header);
        if ($statusArsipIndex === false) {
            throw new \Exception('Kolom "status_arsip" tidak ditemukan di CSV');
        }

        $statusCpnsPnsIndex = array_search('status_cpns_pns', $header);
        $skorArsipIndex = array_search('Nilai Arsip 2026', $header);

        $data = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < $statusArsipIndex + 1) continue;

            $nip = $row[0] ?? '';
            $nama = $row[1] ?? '';
            $statusArsip = $row[$statusArsipIndex] ?? '';
            $statusCpnsPns = $statusCpnsPnsIndex !== false ? ($row[$statusCpnsPnsIndex] ?? '') : '';
            $skorArsip = $skorArsipIndex !== false ? ($row[$skorArsipIndex] ?? '') : '';

            if (empty($nip) || empty($statusArsip)) continue;

            // Parse JSON
            $statusData = json_decode($statusArsip, true);
            if (!$statusData) continue;

            // Tentukan kategori kelengkapan berdasarkan skor
            $kategoriKelengkapan = $this->getKategoriKelengkapan($skorArsip);

            $data[] = [
                'nip' => $nip,
                'nama' => $nama,
                'status_cpns_pns' => $statusCpnsPns,
                'skor_arsip_2026' => $skorArsip,
                'kategori_kelengkapan' => $kategoriKelengkapan,
                'status_arsip' => $statusData,
            ];

            $rowCount++;

            // Update progress every 1000 rows
            if ($rowCount % 1000 == 0) {
                $this->updateJobStatus('processing', "Membaca CSV... ($rowCount baris)");
            }
        }

        fclose($handle);

        if (empty($data)) {
            throw new \Exception('Tidak ada data yang valid untuk diproses');
        }

        return $data;
    }

    /**
     * Generate Excel with multiple sheets
     */
    private function generateExcel($data)
    {
        $spreadsheet = new Spreadsheet();

        // Remove default sheet
        $spreadsheet->removeSheetByIndex(0);

        // Create sheets
        $this->createRingkasanSheet($spreadsheet, $data);
        $this->createDetailedSheets($spreadsheet, $data);

        // Save to storage
        $filename = 'Kelengkapan_Arsip_' . date('YmdHis') . '.xlsx';
        $tempPath = storage_path('app/temp_excel/' . $filename);

        if (!file_exists(storage_path('app/temp_excel'))) {
            mkdir(storage_path('app/temp_excel'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // Copy to public folder
        $publicPath = public_path('temp/' . $filename);
        if (!file_exists(public_path('temp'))) {
            mkdir(public_path('temp'), 0755, true);
        }
        copy($tempPath, $publicPath);
        unlink($tempPath);

        return $filename;
    }

    /**
     * Create Ringkasan sheet
     */
    private function createRingkasanSheet($spreadsheet, $data)
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Ringkasan');
        $spreadsheet->addSheet($sheet, 0);

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'NIP');
        $sheet->setCellValue('C1', 'Nama');
        $sheet->setCellValue('D1', 'Status CPNS/PNS');
        $sheet->setCellValue('E1', 'Nilai Arsip 2026');
        $sheet->setCellValue('F1', 'Kategori Kelengkapan');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Data
        $row = 2;
        foreach ($data as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValueExplicit('B' . $row, $item['nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $item['nama']);
            $sheet->setCellValue('D' . $row, $item['status_cpns_pns']);
            $sheet->setCellValue('E' . $row, $item['skor_arsip_2026']);
            $sheet->setCellValue('F' . $row, $item['kategori_kelengkapan']);
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Create detailed sheets (SK CPNS, SK PNS, etc.)
     */
    private function createDetailedSheets($spreadsheet, $data)
    {
        $dokumenTypes = [
            'SK CPNS',
            'SK PNS',
            'SK Jabatan Terakhir',
            'SK Pangkat Terakhir',
            'SK Pendidikan Terakhir',
            'SK Kenaikan Gaji Berkala Terakhir',
        ];

        foreach ($dokumenTypes as $dokType) {
            $this->createDokumenSheet($spreadsheet, $data, $dokType);
        }
    }

    /**
     * Create sheet for specific document type
     */
    private function createDokumenSheet($spreadsheet, $data, $dokType)
    {
        $sheetName = str_replace('/', '-', $dokType); // Replace / to avoid sheet name errors
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
        $spreadsheet->addSheet($sheet);

        // Headers
        $headers = ['No', 'NIP', 'Nama', 'Status'];

        // Add dynamic columns based on document type
        if ($dokType === 'SK Jabatan Terakhir') {
            $headers = array_merge($headers, ['Nama Jabatan', 'TMT Jabatan', 'Nomor SK', 'Tanggal SK']);
        } elseif ($dokType === 'SK Pangkat Terakhir') {
            $headers = array_merge($headers, ['Golongan', 'TMT', 'Nomor SK', 'Tanggal SK']);
        } elseif ($dokType === 'SK Pendidikan Terakhir') {
            $headers = array_merge($headers, ['Tingkat Pendidikan', 'Tahun Lulus', 'Nomor SK', 'Tanggal SK']);
        } else {
            $headers = array_merge($headers, ['Nomor SK', 'Tanggal SK']);
        }

        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style headers
        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Fill data
        $row = 2;
        foreach ($data as $index => $item) {
            $statusArsip = $item['status_arsip'];
            $docData = $this->getDocumentData($statusArsip, $dokType);

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValueExplicit('B' . $row, $item['nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $item['nama']);
            $sheet->setCellValue('D' . $row, $docData['status']);

            // Fill additional columns based on document type
            $col = 'E';
            foreach ($docData['additional'] as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }

            $row++;
        }

        // Auto size
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Get document data for specific type
     */
    private function getDocumentData($statusArsip, $dokType)
    {
        $status = 'Tidak Ada';
        $additional = [];

        $key = match($dokType) {
            'SK CPNS' => 'sk_cpns',
            'SK PNS' => 'sk_pns',
            'SK Jabatan Terakhir' => 'jabatan_terakhir',
            'SK Pangkat Terakhir' => 'pangkat_terakhir',
            'SK Pendidikan Terakhir' => 'pendidikan_terakhir',
            'SK Kenaikan Gaji Berkala Terakhir' => 'kgb_terakhir',
            default => ''
        };

        if (isset($statusArsip[$key])) {
            $doc = $statusArsip[$key];
            $status = $doc['status'] ?? 'Tidak Ada';

            if ($dokType === 'SK Jabatan Terakhir') {
                $additional = [
                    $doc['nama_jabatan'] ?? '',
                    $doc['tmt_jabatan'] ?? '',
                    $doc['nomor_sk'] ?? '',
                    $doc['tanggal_sk'] ?? '',
                ];
            } elseif ($dokType === 'SK Pangkat Terakhir') {
                $golonganId = $doc['golongan_id'] ?? '';
                $additional = [
                    $this->golonganMap[$golonganId] ?? $golonganId,
                    $doc['tmt'] ?? '',
                    $doc['nomor_sk'] ?? '',
                    $doc['tanggal_sk'] ?? '',
                ];
            } elseif ($dokType === 'SK Pendidikan Terakhir') {
                $pendidikanId = $doc['tingkat_pendidikan_id'] ?? '';
                $additional = [
                    $this->pendidikanMap[$pendidikanId] ?? $pendidikanId,
                    $doc['tahun_lulus'] ?? '',
                    $doc['nomor_sk'] ?? '',
                    $doc['tanggal_sk'] ?? '',
                ];
            } else {
                $additional = [
                    $doc['nomor_sk'] ?? '',
                    $doc['tanggal_sk'] ?? '',
                ];
            }
        } else {
            // Fill with empty values based on document type column count
            $columnCount = match($dokType) {
                'SK Jabatan Terakhir', 'SK Pangkat Terakhir', 'SK Pendidikan Terakhir' => 4,
                default => 2
            };
            $additional = array_fill(0, $columnCount, '');
        }

        return [
            'status' => $status,
            'additional' => $additional
        ];
    }

    /**
     * Get kategori kelengkapan based on score
     */
    private function getKategoriKelengkapan($skor)
    {
        $skor = floatval($skor);

        if ($skor >= 95) return 'Sangat Lengkap';
        if ($skor >= 85) return 'Lengkap';
        if ($skor >= 70) return 'Cukup Lengkap';
        return 'Kurang Lengkap';
    }

    /**
     * Update job status in database
     */
    private function updateJobStatus($status, $message, $outputFile = null)
    {
        DB::table('csv_processing_jobs')
            ->where('id', $this->jobId)
            ->update([
                'status' => $status,
                'message' => $message,
                'output_file' => $outputFile,
                'updated_at' => now(),
            ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        $this->updateJobStatus('failed', 'Error: ' . $exception->getMessage());
    }
}
