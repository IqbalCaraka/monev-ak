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
     * Process uploaded CSV and generate Excel
     */
    public function processUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('csv_file');
            $handle = fopen($file->getPathname(), 'r');

            if ($handle === false) {
                return back()->with('error', 'Gagal membuka file CSV');
            }

            // Skip header
            $header = fgetcsv($handle);

            // Find column index for status_arsip and status_cpns_pns
            $statusArsipIndex = array_search('status_arsip', $header);
            if ($statusArsipIndex === false) {
                return back()->with('error', 'Kolom "status_arsip" tidak ditemukan di CSV');
            }

            $statusCpnsPnsIndex = array_search('status_cpns_pns', $header);

            $data = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < $statusArsipIndex + 1) continue;

                $nip = $row[0] ?? '';
                $nama = $row[1] ?? '';
                $statusArsip = $row[$statusArsipIndex] ?? '';
                $statusCpnsPns = $statusCpnsPnsIndex !== false ? ($row[$statusCpnsPnsIndex] ?? '') : '';

                if (empty($nip) || empty($statusArsip)) continue;

                // Parse JSON
                $statusData = json_decode($statusArsip, true);
                if (!$statusData) continue;

                $data[] = [
                    'nip' => $nip,
                    'nama' => $nama,
                    'status_cpns_pns' => $statusCpnsPns,
                    'status_arsip' => $statusData,
                ];
            }

            fclose($handle);

            if (empty($data)) {
                return back()->with('error', 'Tidak ada data yang valid untuk diproses');
            }

            // Generate Excel
            $excelPath = $this->generateExcel($data);

            // Store path in session for download
            $filename = basename($excelPath);
            session(['excel_download' => $filename]);

            // Copy to public folder for download
            $publicPath = public_path('temp/' . $filename);
            if (!file_exists(public_path('temp'))) {
                mkdir(public_path('temp'), 0755, true);
            }
            copy($excelPath, $publicPath);
            unlink($excelPath);

            return back()->with('success', 'Excel berhasil di-generate! Download akan dimulai otomatis...');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Generate Excel with multiple sheets
     */
    private function generateExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet

        // Generate sheets for Data Utama (5 sheets)
        $this->createDataUtamaSheet($spreadsheet, $data, 'd2np', 'D2NP');
        $this->createDataUtamaSheet($spreadsheet, $data, 'drh', 'DRH');
        $this->createDataUtamaSheet($spreadsheet, $data, 'spmt_cpns', 'SPMT CPNS');
        $this->createDataUtamaSheet($spreadsheet, $data, 'cpns', 'CPNS');
        $this->createDataUtamaSheet($spreadsheet, $data, 'pns', 'PNS');

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
     * Create Data Utama sheet (D2NP, DRH, SPMT CPNS, CPNS, PNS)
     * Format: No | Nama | NIP | Status PNS | Status
     */
    private function createDataUtamaSheet($spreadsheet, $data, $key, $sheetName)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetName);

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", $sheetName);
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Status PNS");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $currentRow++;

        $no = 1;
        foreach ($data as $row) {
            $status = $row['status_arsip']['data_utama'][$key] ?? null;

            if ($status !== null) {
                $sheet->setCellValue("A$currentRow", $no);
                $sheet->setCellValue("B$currentRow", $row['nama']);
                $sheet->setCellValue("C$currentRow", $row['nip']);
                $sheet->setCellValue("D$currentRow", $this->getStatusPnsLabel($row['status_cpns_pns'] ?? ''));
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
     * Format: No | Nama | NIP | Tahun | Status
     */
    private function createJabatanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Jabatan');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Jabatan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Tahun");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $tahun);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
    }

    /**
     * Create Diklat sheet
     * Format: No | Nama | NIP | Tanggal | Status
     */
    private function createDiklatSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Diklat');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Diklat");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Tanggal");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $tanggalFormatted);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);

                $currentRow++;
                $no++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
    }

    /**
     * Create Pindah Instansi sheet
     * Format: No | Nama | NIP | Tahun | Status
     */
    private function createPindahInstansiSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pindah Instansi');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Pindah Instansi");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Tahun");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $tahun);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
    }

    /**
     * Create Penghargaan sheet
     * Format: No | Nama | NIP | Tahun | Status
     */
    private function createPenghargaanSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Penghargaan');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Penghargaan");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Tahun");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $tahun);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
    }

    /**
     * Create SKP sheet
     * Format: No | Nama | NIP | Tahun | Status
     */
    private function createSkpSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('SKP');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat SKP");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Tahun");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $tahun);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
    }

    /**
     * Create Angka Kredit sheet
     * Format: No | Nama | NIP | Data Ke- | Status
     */
    private function createAngkaKreditSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Angka Kredit');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Angka Kredit");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Data Ke-");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $dataKe);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
        $sheet->getColumnDimension('E')->setWidth(18);
    }

    /**
     * Create CLTN sheet
     * Format: No | Nama | NIP | Data Ke- | Status
     */
    private function createCutiLnSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('CLTN');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Cuti LN");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Data Ke-");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $dataKe);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
        $sheet->getColumnDimension('E')->setWidth(18);
    }

    /**
     * Create PMK sheet
     * Format: No | Nama | NIP | Data Ke- | Status
     */
    private function createPmkSheet($spreadsheet, $data)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('PMK');

        $currentRow = 1;

        // Title
        $sheet->setCellValue("A$currentRow", "Riwayat Masa Kerja");
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A$currentRow:E$currentRow");
        $currentRow += 2;

        // Header
        $sheet->setCellValue("A$currentRow", "No");
        $sheet->setCellValue("B$currentRow", "Nama");
        $sheet->setCellValue("C$currentRow", "NIP");
        $sheet->setCellValue("D$currentRow", "Data Ke-");
        $sheet->setCellValue("E$currentRow", "Status");
        $sheet->getStyle("A$currentRow:E$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:E$currentRow")->getFill()
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
                $sheet->setCellValue("D$currentRow", $dataKe);
                $sheet->setCellValue("E$currentRow", $status == 1 ? 'LENGKAP' : 'TIDAK LENGKAP');

                // Color status cell
                $statusColor = $status == 1 ? 'FF92D050' : 'FFFF6666';
                $sheet->getStyle("E$currentRow")->getFill()
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
        $sheet->getColumnDimension('E')->setWidth(18);
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
}
