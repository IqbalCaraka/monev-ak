<?php

namespace App\Jobs;

use App\Models\CsvProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;

class ProcessUbahFormatCsv implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1;

    protected $jobRecord;
    protected $filePath;

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

    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $filePath)
    {
        $this->jobId = $jobId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '3072M'); // Increase to 3GB

        try {
            // Update status to processing
            $this->updateStatus('processing', 'Memproses file CSV...');

            // Read and parse CSV in batches
            $allData = $this->parseCSV();

            if (empty($allData)) {
                throw new \Exception('Tidak ada data yang valid untuk diproses');
            }

            $totalPegawai = count($allData);
            $batchSize = 2000; // Reduce to 2000 pegawai per batch to avoid memory issues
            $batches = array_chunk($allData, $batchSize);
            $totalBatches = count($batches);

            $this->updateStatus('processing', "Menggenerate $totalBatches file Excel... (Total: $totalPegawai pegawai)");

            $generatedFiles = [];

            // Generate Excel per batch
            $failedBatches = [];
            foreach ($batches as $batchIndex => $batchData) {
                $batchNumber = $batchIndex + 1;

                try {
                    $this->updateStatus('processing', "Menggenerate Excel batch $batchNumber dari $totalBatches...");

                    $excelPath = $this->generateExcel($batchData, $batchNumber, $totalBatches);

                    // Move to public folder
                    $filename = basename($excelPath);
                    $publicPath = public_path('temp/' . $filename);
                    if (!file_exists(public_path('temp'))) {
                        mkdir(public_path('temp'), 0755, true);
                    }
                    copy($excelPath, $publicPath);
                    unlink($excelPath);

                    $generatedFiles[] = $filename;

                    // Clear memory after each batch
                    unset($batchData);
                    gc_collect_cycles();

                } catch (\Exception $e) {
                    Log::error("Batch $batchNumber failed: " . $e->getMessage());
                    $failedBatches[] = $batchNumber;
                    // Continue to next batch instead of failing entire job
                }
            }

            // Mark as completed with list of files
            $successCount = count($generatedFiles);
            $failCount = count($failedBatches);
            $filesList = implode(', ', $generatedFiles);

            if ($failCount > 0) {
                $failedList = implode(', ', $failedBatches);
                $message = "Selesai dengan warning! Berhasil: $successCount dari $totalBatches batches. Gagal: batch $failedList. Files: $filesList";
                $status = $successCount > 0 ? 'completed' : 'failed';
            } else {
                $message = "Selesai! Generated $totalBatches files: $filesList";
                $status = 'completed';
            }

            \DB::table('csv_processing_jobs')->where('id', $this->jobId)->update([
                'status' => $status,
                'message' => $message,
                'output_file' => !empty($generatedFiles) ? $generatedFiles[0] : null, // First file for download
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessUbahFormatCsv failed: ' . $e->getMessage());
            \DB::table('csv_processing_jobs')->where('id', $this->jobId)->update([
                'status' => 'failed',
                'message' => 'Error: ' . $e->getMessage(),
                'updated_at' => now(),
            ]);
            throw $e;
        }
    }

    private function updateStatus($status, $message)
    {
        \DB::table('csv_processing_jobs')->where('id', $this->jobId)->update([
            'status' => $status,
            'message' => $message,
            'updated_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception)
    {
        \DB::table('csv_processing_jobs')->where('id', $this->jobId)->update([
            'status' => 'failed',
            'message' => 'Error: ' . $exception->getMessage(),
            'updated_at' => now(),
        ]);
    }

    private function parseCSV()
    {
        $handle = fopen($this->filePath, 'r');

        if ($handle === false) {
            throw new \Exception('Gagal membuka file CSV');
        }

        // Skip header
        $header = fgetcsv($handle);

        // Find column index
        $statusArsipIndex = array_search('status_arsip', $header);
        if ($statusArsipIndex === false) {
            throw new \Exception('Kolom "status_arsip" tidak ditemukan di CSV');
        }

        $statusCpnsPnsIndex = array_search('status_cpns_pns', $header);
        $skorArsipIndex = array_search('Nilai Arsip 2026', $header);

        $data = [];

        while (($row = fgetcsv($handle)) !== false) {
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

            // Tentukan kategori kelengkapan
            $kategoriKelengkapan = $this->getKategoriKelengkapan($skorArsip);

            $data[] = [
                'nip' => $nip,
                'nama' => $nama,
                'status_cpns_pns' => $statusCpnsPns,
                'skor_arsip_2026' => $skorArsip,
                'kategori_kelengkapan' => $kategoriKelengkapan,
                'status_arsip' => $statusData,
            ];
        }

        fclose($handle);
        return $data;
    }

    private function generateExcel($data, $batchNumber = 1, $totalBatches = 1)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->createDataUtamaSheet($spreadsheet, $data);
        $this->createGolonganSheet($spreadsheet, $data);
        $this->createPendidikanSheet($spreadsheet, $data);
        $this->createJabatanSheet($spreadsheet, $data);
        $this->createDiklatSheet($spreadsheet, $data);
        $this->createPindahInstansiSheet($spreadsheet, $data);
        $this->createPenghargaanSheet($spreadsheet, $data);
        $this->createSkpSheet($spreadsheet, $data);
        $this->createAngkaKreditSheet($spreadsheet, $data);
        $this->createCutiLnSheet($spreadsheet, $data);
        $this->createPmkSheet($spreadsheet, $data);

        $spreadsheet->setActiveSheetIndex(0);

        $tempDir = storage_path('app/temp/');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Add batch number to filename if multiple batches
        $batchSuffix = $totalBatches > 1 ? "_Part{$batchNumber}of{$totalBatches}" : '';
        $filename = 'Status_Arsip_' . date('YmdHis') . $batchSuffix . '.xlsx';
        $filepath = $tempDir . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    private function createDataUtamaSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Utama');

        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Data Utama Dokumen");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:K$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Nilai Arsip 2026");
        $sheet->setCellValue("E$currentRow", "Kategori Kelengkapan");
        $sheet->setCellValue("F$currentRow", "Status PNS");
        $sheet->setCellValue("G$currentRow", "D2NP");
        $sheet->setCellValue("H$currentRow", "DRH");
        $sheet->setCellValue("I$currentRow", "SPMT CPNS");
        $sheet->setCellValue("J$currentRow", "CPNS");
        $sheet->setCellValue("K$currentRow", "PNS");
        $sheet->getStyle("A$currentRow:K$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:K$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $dataUtama = $row['status_arsip']['data_utama'] ?? [];

            $d2np = $dataUtama['d2np'] ?? null;
            $drh = $dataUtama['drh'] ?? null;
            $spmtCpns = $dataUtama['spmt_cpns'] ?? null;
            $cpns = $dataUtama['cpns'] ?? null;
            $pns = $dataUtama['pns'] ?? null;

            $sheet->setCellValue("A$currentRow", $no);
            $sheet->setCellValue("B$currentRow", $row['nama']);
            $sheet->setCellValue("C$currentRow", $row['nip']);
            $sheet->setCellValue("D$currentRow", $row['skor_arsip_2026']);
            $sheet->setCellValue("E$currentRow", $row['kategori_kelengkapan']);
            $sheet->setCellValue("F$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
            $sheet->setCellValue("G$currentRow", $d2np == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');
            $sheet->setCellValue("H$currentRow", $drh == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');
            $sheet->setCellValue("I$currentRow", $spmtCpns == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');
            $sheet->setCellValue("J$currentRow", $cpns == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');
            $sheet->setCellValue("K$currentRow", $pns == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

            foreach (['G', 'H', 'I', 'J', 'K'] as $col) {
                $value = $sheet->getCell("$col$currentRow")->getValue();
                $statusColor = $value === 'LENGKAP' ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("$col$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);
            }

            $currentRow++;
            $no++;
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(18);
    }

    private function createGolonganSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Golongan');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Golongan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Golongan");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $golonganData = $row['status_arsip']['golongan'] ?? [];
            foreach ($golonganData as $golId => $status) {
                $golonganNama = $this->golonganMap[$golId] ?? $golId;
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $golonganNama);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createPendidikanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pendidikan');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Pendidikan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Pendidikan");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $pendidikanData = $row['status_arsip']['pendidikan'] ?? [];
            foreach ($pendidikanData as $pendId => $status) {
                $pendidikanNama = $this->pendidikanMap[$pendId] ?? $pendId;
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $pendidikanNama);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createJabatanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Jabatan');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Jabatan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Tahun");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $jabatanData = $row['status_arsip']['jabatan'] ?? [];
            foreach ($jabatanData as $tanggal => $status) {
                $tahun = date('Y', strtotime($tanggal));
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $tahun);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createDiklatSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Diklat');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Diklat");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Tanggal");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $diklatData = $row['status_arsip']['diklat'] ?? [];
            foreach ($diklatData as $tanggal => $status) {
                $tanggalFormatted = date('d-m-Y', strtotime($tanggal));
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $tanggalFormatted);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createPindahInstansiSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pindah Instansi');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Pindah Instansi");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Tahun");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $pindahData = $row['status_arsip']['pindah_instansi'] ?? [];
            foreach ($pindahData as $tahun => $status) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $tahun);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createPenghargaanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Penghargaan');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Penghargaan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Tahun");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $penghargaanData = $row['status_arsip']['penghargaan'] ?? [];
            foreach ($penghargaanData as $tahun => $status) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $tahun);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createSkpSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('SKP');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat SKP");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Tahun");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $skpData = $row['status_arsip']['skp22'] ?? [];
            foreach ($skpData as $tahun => $status) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $tahun);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createAngkaKreditSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Angka Kredit');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Angka Kredit");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Data Ke-");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $akData = $row['status_arsip']['angka_kredit'] ?? [];
            $dataKe = 1;
            foreach ($akData as $uuid => $status) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $dataKe);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
                $dataKe++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createCutiLnSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('CLTN');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Cuti LN");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Data Ke-");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $cutiData = $row['status_arsip']['cuti_ln'] ?? [];
            $dataKe = 1;
            foreach ($cutiData as $uuid => $status) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $dataKe);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
                $dataKe++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function createPmkSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('PMK');
        $currentRow = 1;

        $sheet->setCellValue("A$currentRow", "Riwayat Masa Kerja");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Data Ke-");
        $sheet->setCellValue("F$currentRow", "Status");
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $pmkData = $row['status_arsip']['pmk'] ?? [];
            $dataKe = 1;
            foreach ($pmkData as $uuid => $status) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $dataKe);
                $sheet->setCellValue("F$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("F$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
                $dataKe++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    private function getStatusPnsLabel($statusCpnsPns)
    {
        if ($statusCpnsPns === 'P') {
            return 'PNS';
        } elseif ($statusCpnsPns === 'C') {
            return 'CPNS';
        }
        return '-';
    }

    private function getKategoriKelengkapan($skor)
    {
        if (empty($skor) || $skor === 'null' || $skor === null) {
            return 'Kurang Lengkap';
        }

        $skorFloat = floatval($skor);

        if ($skorFloat > 90) {
            return 'Sangat Lengkap';
        } elseif ($skorFloat >= 55.6) {
            return 'Lengkap';
        } elseif ($skorFloat >= 30) {
            return 'Cukup Lengkap';
        } else {
            return 'Kurang Lengkap';
        }
    }
}
