<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UbahFormatController extends Controller
{
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
     * Process uploaded CSV and generate Excel (using Queue Job)
     */
    public function processUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:512000', // Max 500MB
        ]);

        try {
            $file = $request->file('csv_file');

            if (!$file) {
                throw new \Exception('File tidak ditemukan dalam request');
            }

            // Store CSV file temporarily
            $originalFilename = $file->getClientOriginalName();

            // Use storeAs to specify exact path without disk confusion
            $filename = uniqid() . '_' . time() . '.csv';
            $csvPath = 'csv_uploads/' . $filename;

            // Move file directly to storage/app/csv_uploads
            $fullPath = storage_path('app/' . $csvPath);
            $file->move(storage_path('app/csv_uploads'), $filename);

            // Verify file was actually saved
            if (!file_exists($fullPath)) {
                throw new \Exception('File gagal disimpan ke: ' . $fullPath);
            }

            // Create job record in database
            $jobId = \DB::table('csv_processing_jobs')->insertGetId([
                'user_id' => auth()->id() ?? null,
                'csv_filename' => $originalFilename,
                'csv_path' => $csvPath,
                'status' => 'pending',
                'message' => 'Menunggu diproses...',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Dispatch job to queue (using the correct Job class)
            // Note: ProcessUbahFormatCsv expects ($jobId, $filePath) not ($csvPath, $userId, $jobId)
            $fullPath = storage_path('app/' . $csvPath);
            \App\Jobs\ProcessUbahFormatCsv::dispatch($jobId, $fullPath);

            // Redirect to status page
            return redirect()->route('ubah-format.status', ['jobId' => $jobId])
                ->with('success', 'File berhasil diunggah! Proses konversi dimulai di background...');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show processing status page
     */
    public function showStatus($jobId)
    {
        $job = \DB::table('csv_processing_jobs')->where('id', $jobId)->first();

        if (!$job) {
            return redirect()->route('ubah-format.index')->with('error', 'Job tidak ditemukan');
        }

        return view('ubah-format.status', compact('job'));
    }

    /**
     * Check job status (AJAX)
     */
    public function checkStatus($jobId)
    {
        $job = \DB::table('csv_processing_jobs')->where('id', $jobId)->first();

        if (!$job) {
            return response()->json(['error' => 'Job tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => $job->status,
            'message' => $job->message,
            'output_file' => $job->output_file,
        ]);
    }

    /**
     * Download generated Excel file
     */
    public function download($filename)
    {
        $filePath = public_path('temp/' . $filename);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File tidak ditemukan');
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Generate Excel with multiple sheets
     */
    private function generateExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet

        // Generate 1 sheet for Data Utama (gabungan D2NP, DRH, SPMT CPNS, CPNS, PNS)
        $this->createDataUtamaSheet($spreadsheet, $data);

        // Generate sheets for Riwayat (10 sheets)
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

        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        // Save to file
        $tempDir = storage_path('app/temp/');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = 'Status_Arsip_' . date('YmdHis') . '.xlsx';
        $filepath = $tempDir . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * Create Data Utama sheet (D2NP, DRH, SPMT CPNS, CPNS, PNS gabungan dalam 1 sheet)
     * Format: No | Nama | NIP | Nilai Arsip 2026 | Kategori Kelengkapan | Status PNS | D2NP | DRH | SPMT CPNS | CPNS | PNS
     */
    private function createDataUtamaSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Utama');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Data Utama Dokumen");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:K$currentRow");
        $currentRow += 2;

        // Header
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

            // Ambil status untuk setiap dokumen
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

            // Color status cells
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

    /**
     * Create Golongan sheet
     * Format: No | Nama | NIP | Status PNS | Golongan | Status
     */
    private function createGolonganSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Golongan');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Golongan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create Pendidikan sheet
     * Format: No | Nama | NIP | Status PNS | Pendidikan | Status
     */
    private function createPendidikanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pendidikan');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Pendidikan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create Jabatan sheet
     * Format: No | Nama | NIP | Status PNS | Tahun | Status
     */
    private function createJabatanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Jabatan');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Jabatan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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
        $sheet->getColumnDimension('C')->setWidth(20);        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(18);
    }

    /**
     * Create Diklat sheet
     * Format: No | Nama | NIP | Status PNS | Tanggal | Status
     */
    private function createDiklatSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Diklat');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Diklat");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create Pindah Instansi sheet
     * Format: No | Nama | NIP | Status PNS | Tahun | Status
     */
    private function createPindahInstansiSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pindah Instansi');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Pindah Instansi");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create Penghargaan sheet
     * Format: No | Nama | NIP | Status PNS | Tahun | Status
     */
    private function createPenghargaanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Penghargaan');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Penghargaan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create SKP sheet
     * Format: No | Nama | NIP | Status PNS | Tahun | Status
     */
    private function createSkpSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('SKP');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat SKP");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create Angka Kredit sheet
     * Format: No | Nama | NIP | Status PNS | Data Ke- | Status
     */
    private function createAngkaKreditSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Angka Kredit');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Angka Kredit");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create CLTN sheet
     * Format: No | Nama | NIP | Status PNS | Data Ke- | Status
     */
    private function createCutiLnSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('CLTN');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Cuti LN");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Create PMK sheet
     * Format: No | Nama | NIP | Status PNS | Data Ke- | Status
     */
    private function createPmkSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('PMK');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Masa Kerja");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:F$currentRow");
        $currentRow += 2;

        // Header
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

                // Color status cell
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

    /**
     * Helper: Get Status PNS label
     */
    private function getStatusPnsLabel($statusCpnsPns)
    {
        if ($statusCpnsPns === 'P') {
            return 'PNS';
        } elseif ($statusCpnsPns === 'C') {
            return 'CPNS';
        }
        return '-';
    }

    /**
     * Helper: Get Kategori Kelengkapan based on skor
     */
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
