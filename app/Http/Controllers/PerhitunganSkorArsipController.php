<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PerhitunganSkorArsipController extends Controller
{
    /**
     * Display form upload CSV
     */
    public function index()
    {
        return view('skor-arsip.index');
    }

    /**
     * Process uploaded CSV and display results
     */
    public function process(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('csv_file');
            $fileName = $file->getClientOriginalName();

            // Read CSV file
            $csvData = file_get_contents($file->getRealPath());
            $lines = explode(PHP_EOL, $csvData);

            // Get header
            $header = str_getcsv(array_shift($lines), ';');

            $results = [];
            $lineNumber = 1;
            $imported = 0;
            $skipped = 0;

            foreach ($lines as $line) {
                // Skip empty lines
                if (empty(trim($line))) {
                    continue;
                }

                $lineNumber++;

                // Parse CSV line with semicolon delimiter
                $data = str_getcsv($line, ';');

                // Skip if not enough columns
                if (count($data) < 64) {
                    continue;
                }

                // Extract required columns
                $nip = trim($data[1] ?? ''); // Column B (index 1)
                $status = strtoupper(trim($data[3] ?? '')); // Column D (index 3)

                // Skip if NIP empty
                if (empty($nip)) {
                    continue;
                }

                // FILTER: Hanya proses yang status PNS (P)
                if ($status != 'P') {
                    $skipped++;
                    continue;
                }

                // Extract jumlah data
                $jumlah_golongan = intval($data[22] ?? 0);
                $jumlah_jabatan = intval($data[23] ?? 0);
                $jumlah_pendidikan = intval($data[24] ?? 0);
                $jumlah_diklat = intval($data[25] ?? 0);
                $jumlah_penghargaan = intval($data[26] ?? 0);
                $jumlah_kinerja = intval($data[27] ?? 0);
                $jumlah_pindah_instansi = intval($data[28] ?? 0);

                // Extract dokumen data
                $dok_d2np = intval($data[29] ?? 0);
                $dok_drh = intval($data[30] ?? 0);
                $dok_spmt_cpns = intval($data[31] ?? 0);
                $dok_cpns = intval($data[32] ?? 0);
                $dok_pns = intval($data[33] ?? 0);
                $dok_golongan = intval($data[34] ?? 0);
                $dok_jabatan = intval($data[35] ?? 0);
                $dok_pendidikan = intval($data[36] ?? 0);
                $dok_diklat = intval($data[37] ?? 0);
                $dok_penghargaan = intval($data[38] ?? 0);
                $dok_kinerja = intval($data[39] ?? 0);
                $dok_pindah_instansi = intval($data[40] ?? 0);

                // Hitung skor berdasarkan ketentuan
                $skorCalculated = $this->hitungSkor([
                    'jumlah_golongan' => $jumlah_golongan,
                    'jumlah_jabatan' => $jumlah_jabatan,
                    'jumlah_pendidikan' => $jumlah_pendidikan,
                    'jumlah_diklat' => $jumlah_diklat,
                    'jumlah_penghargaan' => $jumlah_penghargaan,
                    'jumlah_kinerja' => $jumlah_kinerja,
                    'jumlah_pindah_instansi' => $jumlah_pindah_instansi,
                    'dok_d2np' => $dok_d2np,
                    'dok_drh' => $dok_drh,
                    'dok_spmt_cpns' => $dok_spmt_cpns,
                    'dok_cpns' => $dok_cpns,
                    'dok_pns' => $dok_pns,
                    'dok_golongan' => $dok_golongan,
                    'dok_jabatan' => $dok_jabatan,
                    'dok_pendidikan' => $dok_pendidikan,
                    'dok_diklat' => $dok_diklat,
                    'dok_penghargaan' => $dok_penghargaan,
                    'dok_kinerja' => $dok_kinerja,
                    'dok_pindah_instansi' => $dok_pindah_instansi,
                ]);

                $results[] = [
                    'nip' => $nip,
                    'nama' => trim($data[2] ?? ''), // Column C (index 2)
                    'status_cpns_pns' => strtoupper(trim($data[3] ?? '')), // Column D (index 3)

                    // Columns W-AO (index 22-40) - Data Jumlah
                    'jumlah' => [
                        'jumlah_golongan' => intval($data[22] ?? 0),
                        'jumlah_jabatan' => intval($data[23] ?? 0),
                        'jumlah_pendidikan' => intval($data[24] ?? 0),
                        'jumlah_diklat' => intval($data[25] ?? 0),
                        'jumlah_penghargaan' => $jumlah_penghargaan,
                        'jumlah_kinerja' => $jumlah_kinerja,
                        'jumlah_pindah_instansi' => $jumlah_pindah_instansi,
                    ],

                    // Columns W-AO (index 29-40) - Data Dokumen
                    'dokumen' => [
                        'dok_d2np' => $dok_d2np,
                        'dok_drh' => intval($data[30] ?? 0),
                        'dok_spmt_cpns' => $dok_spmt_cpns,
                        'dok_cpns' => intval($data[32] ?? 0),
                        'dok_pns' => $dok_pns,
                        'dok_golongan' => $dok_golongan,
                        'dok_jabatan' => $dok_jabatan,
                        'dok_pendidikan' => $dok_pendidikan,
                        'dok_diklat' => $dok_diklat,
                        'dok_penghargaan' => intval($data[38] ?? 0),
                        'dok_kinerja' => intval($data[39] ?? 0),
                        'dok_pindah_instansi' => intval($data[40] ?? 0),
                    ],

                    // Columns BH-BL (index 59-63) - Skor Arsip
                    'skor' => [
                        'skor_arsip_digital_30okt' => !empty($data[59]) ? floatval($data[59]) : 0,
                        'skor_arsip_digital' => !empty($data[60]) ? floatval($data[60]) : 0,
                        'is_terisi' => intval($data[61] ?? 0),
                        'skor_arsip_2026' => !empty($data[62]) ? floatval($data[62]) : 0,
                        'kategori_kelengkapan_2026' => trim($data[63] ?? ''),
                    ],

                    // Skor yang dihitung
                    'skor_calculated' => $skorCalculated,
                ];

                $imported++;
            }

            // Calculate statistics
            $stats = $this->calculateStats($results);

            // Prepare summary message
            $message = "Berhasil memproses {$imported} data PNS";
            if ($skipped > 0) {
                $message .= " ({$skipped} data dilewati - CPNS atau tidak valid)";
            }

            return view('skor-arsip.result', compact('results', 'stats', 'fileName', 'message'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memproses CSV: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Hitung skor arsip berdasarkan ketentuan
     *
     * Arsip ASN Utama (max 90):
     * - DRH = 7.5 (fixed, tidak perlu cek)
     * - D2NIP = 7.5 (jika ada dokumen)
     * - SK CPNS/SPMT CPNS = 7.5 (jika ada dokumen)
     * - SK PNS = 7.5 (jika ada dokumen)
     * - Riwayat Pendidikan = 15 → Jika jumlah=0 maka skor=15, jika jumlah>0 maka (dokumen/jumlah)*15
     * - Riwayat Kenaikan Pangkat (Golongan) = 15 → Jika jumlah=0 maka skor=15, jika jumlah>0 maka (dokumen/jumlah)*15
     * - Riwayat Jabatan = 15 → Jika jumlah=0 maka skor=15, jika jumlah>0 maka (dokumen/jumlah)*15
     * - Riwayat Diklat = 15 → Jika jumlah=0 maka skor=15, jika jumlah>0 maka (dokumen/jumlah)*15
     *
     * Jika tidak ada Arsip Kondisional → Skor otomatis 100
     */
    private function hitungSkor($data)
    {
        $skorUtama = 0;

        // DRH = 7.5 (fixed, tidak perlu dicek)
        $skorDRH = 7.5;

        // D2NIP = 7.5 (jika ada dokumen)
        $skorD2NIP = $data['dok_d2np'] > 0 ? 7.5 : 0;

        // SK CPNS/SPMT CPNS = 7.5 (jika ada dokumen CPNS atau SPMT CPNS)
        $skorCPNS = ($data['dok_cpns'] > 0 || $data['dok_spmt_cpns'] > 0) ? 7.5 : 0;

        // SK PNS = 7.5 (jika ada dokumen)
        $skorPNS = $data['dok_pns'] > 0 ? 7.5 : 0;

        // Riwayat Pendidikan = 15
        // Jika jumlah = 0 → tidak punya riwayat → skor maksimal (15)
        // Jika jumlah > 0 → punya riwayat → skor = (dokumen / jumlah) * 15
        if ($data['jumlah_pendidikan'] == 0) {
            $skorPendidikan = 15;
        } else {
            $skorPendidikan = ($data['dok_pendidikan'] / $data['jumlah_pendidikan']) * 15;
        }

        // Riwayat Kenaikan Pangkat (Golongan) = 15
        if ($data['jumlah_golongan'] == 0) {
            $skorGolongan = 15;
        } else {
            $skorGolongan = ($data['dok_golongan'] / $data['jumlah_golongan']) * 15;
        }

        // Riwayat Jabatan = 15
        if ($data['jumlah_jabatan'] == 0) {
            $skorJabatan = 15;
        } else {
            $skorJabatan = ($data['dok_jabatan'] / $data['jumlah_jabatan']) * 15;
        }

        // Riwayat Diklat = 15
        if ($data['jumlah_diklat'] == 0) {
            $skorDiklat = 15;
        } else {
            $skorDiklat = ($data['dok_diklat'] / $data['jumlah_diklat']) * 15;
        }

        // Total Skor Utama
        $skorUtama = $skorDRH + $skorD2NIP + $skorCPNS + $skorPNS +
                     $skorPendidikan + $skorGolongan + $skorJabatan + $skorDiklat;

        // ==========================================
        // PERHITUNGAN ARSIP KONDISIONAL (MAX 10)
        // ==========================================

        $totalJumlahKondisional = $data['jumlah_penghargaan'] + $data['jumlah_kinerja'] + $data['jumlah_pindah_instansi'];
        $totalDokumenKondisional = $data['dok_penghargaan'] + $data['dok_kinerja'] + $data['dok_pindah_instansi'];

        // Hitung jumlah jenis kondisional yang dimiliki (yang jumlahnya > 0)
        $jenisKondisionalDimiliki = 0;
        if ($data['jumlah_penghargaan'] > 0) $jenisKondisionalDimiliki++;
        if ($data['jumlah_kinerja'] > 0) $jenisKondisionalDimiliki++;
        if ($data['jumlah_pindah_instansi'] > 0) $jenisKondisionalDimiliki++;

        if ($totalJumlahKondisional == 0) {
            // Tidak ada arsip kondisional
            $skorKondisionalSim1 = 0;
            $skorKondisionalSim2 = 0;
            $adaArsipKondisional = false;
        } else {
            // Ada arsip kondisional
            $adaArsipKondisional = true;

            // SIMULASI 1: Proporsional Total
            // Rumus: (Total Dokumen Kondisional / Total Jumlah Kondisional) × 10
            $skorKondisionalSim1 = ($totalDokumenKondisional / $totalJumlahKondisional) * 10;

            // SIMULASI 2: Per Jenis Kondisional
            // Rumus: Σ [(Dokumen / Jumlah) × (10 / Jumlah Jenis Dimiliki)] untuk setiap jenis yang dimiliki
            // Contoh: Jika punya 3 jenis → bobot per jenis = 10/3 = 3.33
            //         Jika penghargaan: jumlah=5, dok=3 → skor = (3/5) × 3.33 = 2.00
            $skorKondisionalSim2 = 0;
            $bobotPerJenis = $jenisKondisionalDimiliki > 0 ? (10 / $jenisKondisionalDimiliki) : 0;

            // Hitung per jenis
            if ($data['jumlah_penghargaan'] > 0) {
                $skorKondisionalSim2 += ($data['dok_penghargaan'] / $data['jumlah_penghargaan']) * $bobotPerJenis;
            }
            if ($data['jumlah_kinerja'] > 0) {
                $skorKondisionalSim2 += ($data['dok_kinerja'] / $data['jumlah_kinerja']) * $bobotPerJenis;
            }
            if ($data['jumlah_pindah_instansi'] > 0) {
                $skorKondisionalSim2 += ($data['dok_pindah_instansi'] / $data['jumlah_pindah_instansi']) * $bobotPerJenis;
            }
        }

        // Skor Final menggunakan Simulasi 1 dan Simulasi 2
        // Jika tidak ada Arsip Kondisional, maka Arsip Utama + 10
        if (!$adaArsipKondisional) {
            $skorFinalSim1 = $skorUtama + 10;
            $skorFinalSim2 = $skorUtama + 10;
            $kategoriSim1 = $skorFinalSim1 >= 100 ? 'Lengkap' : 'Tidak Lengkap';
            $kategoriSim2 = $skorFinalSim2 >= 100 ? 'Lengkap' : 'Tidak Lengkap';
        } else {
            $skorFinalSim1 = $skorUtama + $skorKondisionalSim1;
            $skorFinalSim2 = $skorUtama + $skorKondisionalSim2;
            $kategoriSim1 = $skorFinalSim1 >= 100 ? 'Lengkap' : 'Tidak Lengkap';
            $kategoriSim2 = $skorFinalSim2 >= 100 ? 'Lengkap' : 'Tidak Lengkap';
        }

        return [
            'detail' => [
                'drh' => ['dokumen' => $data['dok_drh'], 'skor' => $skorDRH],
                'd2nip' => ['dokumen' => $data['dok_d2np'], 'skor' => $skorD2NIP],
                'cpns' => [
                    'dokumen' => $data['dok_cpns'] + $data['dok_spmt_cpns'],
                    'dok_cpns' => $data['dok_cpns'],
                    'dok_spmt_cpns' => $data['dok_spmt_cpns'],
                    'skor' => $skorCPNS
                ],
                'pns' => ['dokumen' => $data['dok_pns'], 'skor' => $skorPNS],
                'pendidikan' => ['jumlah' => $data['jumlah_pendidikan'], 'dokumen' => $data['dok_pendidikan'], 'skor' => $skorPendidikan],
                'golongan' => ['jumlah' => $data['jumlah_golongan'], 'dokumen' => $data['dok_golongan'], 'skor' => $skorGolongan],
                'jabatan' => ['jumlah' => $data['jumlah_jabatan'], 'dokumen' => $data['dok_jabatan'], 'skor' => $skorJabatan],
                'diklat' => ['jumlah' => $data['jumlah_diklat'], 'dokumen' => $data['dok_diklat'], 'skor' => $skorDiklat],
            ],
            'kondisional' => [
                'penghargaan' => ['jumlah' => $data['jumlah_penghargaan'], 'dokumen' => $data['dok_penghargaan']],
                'kinerja' => ['jumlah' => $data['jumlah_kinerja'], 'dokumen' => $data['dok_kinerja']],
                'pindah_instansi' => ['jumlah' => $data['jumlah_pindah_instansi'], 'dokumen' => $data['dok_pindah_instansi']],
                'total_jumlah' => $totalJumlahKondisional,
                'total_dokumen' => $totalDokumenKondisional,
                'jenis_dimiliki' => $jenisKondisionalDimiliki,
                'bobot_per_jenis' => $jenisKondisionalDimiliki > 0 ? (10 / $jenisKondisionalDimiliki) : 0,
            ],
            'skor_utama' => $skorUtama,
            'ada_arsip_kondisional' => $adaArsipKondisional,
            // Simulasi 1
            'simulasi1' => [
                'skor_kondisional' => $skorKondisionalSim1,
                'skor_final' => $skorFinalSim1,
                'kategori' => $kategoriSim1,
            ],
            // Simulasi 2
            'simulasi2' => [
                'skor_kondisional' => $skorKondisionalSim2,
                'skor_final' => $skorFinalSim2,
                'kategori' => $kategoriSim2,
            ],
        ];
    }

    /**
     * Calculate statistics from results
     */
    private function calculateStats($results)
    {
        $total = count($results);
        $pns = 0;
        $cpns = 0;
        $lengkap = 0;
        $totalJumlahAll = 0;
        $totalDokumenAll = 0;
        $totalSkorCalculated = 0;
        $totalSkorCSV = 0;

        foreach ($results as $result) {
            // Count status
            if ($result['status_cpns_pns'] == 'P') {
                $pns++;
            } elseif ($result['status_cpns_pns'] == 'C') {
                $cpns++;
            }

            // Count kategori berdasarkan skor yang dihitung (gunakan simulasi 1)
            if ($result['skor_calculated']['simulasi1']['kategori'] == 'Lengkap') {
                $lengkap++;
            }

            // Sum jumlah
            $totalJumlahAll += array_sum($result['jumlah']);

            // Sum dokumen
            $totalDokumenAll += array_sum($result['dokumen']);

            // Sum skor calculated (gunakan simulasi 1 untuk rata-rata)
            $totalSkorCalculated += $result['skor_calculated']['simulasi1']['skor_final'];

            // Sum skor from CSV
            $totalSkorCSV += $result['skor']['skor_arsip_2026'];
        }

        return [
            'total' => $total,
            'pns' => $pns,
            'cpns' => $cpns,
            'lengkap' => $lengkap,
            'avg_jumlah' => $total > 0 ? round($totalJumlahAll / $total, 2) : 0,
            'avg_dokumen' => $total > 0 ? round($totalDokumenAll / $total, 2) : 0,
            'avg_skor_calculated' => $total > 0 ? round($totalSkorCalculated / $total, 2) : 0,
            'avg_skor_csv' => $total > 0 ? round($totalSkorCSV / $total, 2) : 0,
            'persen_lengkap' => $total > 0 ? round(($lengkap / $total) * 100, 2) : 0,
        ];
    }
}
