<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonevDmsInstansiScore;
use App\Models\MonevDmsNasional;
use App\Models\MonevDmsSkorRata2Nasional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MonevDmsController extends Controller
{
    /**
     * Upload CSV Monev Skor Instansi
     */
    public function uploadMonevCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'monev_csv_file' => 'required|file|mimes:csv,txt|max:102400',
            'upload_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $uploadDate = Carbon::parse($request->upload_date)->format('Y-m-d');

            // Check if data for this date already exists
            $existingData = MonevDmsInstansiScore::where('upload_date', $uploadDate)->exists();
            if ($existingData) {
                return back()->with('error', 'Data untuk tanggal ' . $uploadDate . ' sudah ada. Silakan pilih tanggal lain atau hapus data yang ada terlebih dahulu.');
            }

            $file = $request->file('monev_csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));

            // Remove header row
            $header = array_shift($csvData);

            if (empty($csvData)) {
                return back()->with('error', 'File CSV kosong atau tidak valid.');
            }

            $instansiData = [];
            $totalInstansi = 0;
            $totalSkor = 0;
            $countSangatLengkap = 0;
            $countLengkap = 0;
            $countCukupLengkap = 0;
            $countKurangLengkap = 0;

            foreach ($csvData as $row) {
                if (count($row) < 9) continue; // Skip invalid rows (need 9 columns: id, nama, skor, kanreg, jumlah_asn, kurang, cukup, lengkap, sangat)

                $idInstansi = trim($row[0]);
                $namaInstansi = trim($row[1]);
                $skorInstansi = floatval($row[2]);
                $kantorRegionalId = trim($row[3]);
                $jumlahAsn = intval($row[4]);
                $kurangLengkap = intval($row[5]);
                $cukupLengkap = intval($row[6]);
                $lengkap = intval($row[7]);
                $sangatLengkap = intval($row[8]);

                // Determine status kelengkapan based on skor
                if ($skorInstansi > 90) {
                    $statusKelengkapan = 'Sangat Lengkap';
                    $countSangatLengkap++;
                } elseif ($skorInstansi >= 55.6) {
                    $statusKelengkapan = 'Lengkap';
                    $countLengkap++;
                } elseif ($skorInstansi >= 30) {
                    $statusKelengkapan = 'Cukup Lengkap';
                    $countCukupLengkap++;
                } else {
                    $statusKelengkapan = 'Kurang Lengkap';
                    $countKurangLengkap++;
                }

                $instansiData[] = [
                    'id_instansi' => $idInstansi,
                    'nama_instansi' => $namaInstansi,
                    'upload_date' => $uploadDate,
                    'monev_skor_instansi' => $skorInstansi,
                    'jumlah_asn' => $jumlahAsn,
                    'sangat_lengkap' => $sangatLengkap,
                    'lengkap' => $lengkap,
                    'cukup_lengkap' => $cukupLengkap,
                    'kurang_lengkap' => $kurangLengkap,
                    'kantor_regional_id' => $kantorRegionalId,
                    'monev_status_kelengkapan' => $statusKelengkapan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $totalInstansi++;
                $totalSkor += $skorInstansi;
            }

            // Bulk insert instansi data
            if (!empty($instansiData)) {
                MonevDmsInstansiScore::insert($instansiData);
            }

            // Calculate and save national score
            $avgSkorNasional = $totalInstansi > 0 ? $totalSkor / $totalInstansi : 0;

            MonevDmsNasional::create([
                'upload_date' => $uploadDate,
                'total_instansi' => $totalInstansi,
                'monev_skor_nasional' => round($avgSkorNasional, 2),
                'count_sangat_lengkap' => $countSangatLengkap,
                'count_lengkap' => $countLengkap,
                'count_cukup_lengkap' => $countCukupLengkap,
                'count_kurang_lengkap' => $countKurangLengkap,
            ]);

            DB::commit();

            return back()->with('success', "Data Monev berhasil diupload! Total {$totalInstansi} instansi dengan rata-rata skor nasional: " . round($avgSkorNasional, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat mengupload data: ' . $e->getMessage());
        }
    }

    /**
     * Get monev data for dashboard
     */
    public function getMonevData()
    {
        // Get latest national score
        $latestNasional = MonevDmsNasional::orderBy('upload_date', 'desc')->first();

        // Get latest instansi scores
        $latestInstansiScores = [];
        if ($latestNasional) {
            $latestInstansiScores = MonevDmsInstansiScore::where('upload_date', $latestNasional->upload_date)
                ->orderBy('monev_skor_instansi', 'desc')
                ->get();
        }

        return [
            'nasional' => $latestNasional,
            'instansi' => $latestInstansiScores,
        ];
    }

    /**
     * Get top 5 instansi
     */
    public function getTopInstansi()
    {
        $latestNasional = MonevDmsNasional::orderBy('upload_date', 'desc')->first();

        if (!$latestNasional) {
            return collect();
        }

        return MonevDmsInstansiScore::where('upload_date', $latestNasional->upload_date)
            ->orderBy('monev_skor_instansi', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get bottom 5 instansi
     */
    public function getBottomInstansi()
    {
        $latestNasional = MonevDmsNasional::orderBy('upload_date', 'desc')->first();

        if (!$latestNasional) {
            return collect();
        }

        return MonevDmsInstansiScore::where('upload_date', $latestNasional->upload_date)
            ->orderBy('monev_skor_instansi', 'asc')
            ->limit(5)
            ->get();
    }

    /**
     * Delete monev data by upload_date
     */
    public function deleteMonevData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $uploadDate = Carbon::parse($request->upload_date)->format('Y-m-d');

            // Delete instansi scores
            MonevDmsInstansiScore::where('upload_date', $uploadDate)->delete();

            // Delete national score
            MonevDmsNasional::where('upload_date', $uploadDate)->delete();

            DB::commit();

            return back()->with('success', 'Data Monev untuk tanggal ' . $uploadDate . ' berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Upload CSV Skor Rata-rata Nasional
     */
    public function uploadSkorRata2Nasional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nasional_csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'upload_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $uploadDate = Carbon::parse($request->upload_date)->format('Y-m-d');

            // Check if data for this date already exists
            $existingData = MonevDmsSkorRata2Nasional::where('upload_date', $uploadDate)->exists();
            if ($existingData) {
                return back()->with('error', 'Data untuk tanggal ' . $uploadDate . ' sudah ada. Silakan pilih tanggal lain atau hapus data yang ada terlebih dahulu.');
            }

            $file = $request->file('nasional_csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));

            // Remove header row
            $header = array_shift($csvData);

            if (empty($csvData)) {
                return back()->with('error', 'File CSV kosong atau tidak valid.');
            }

            // Parse CSV (hanya 1 baris data)
            $row = $csvData[0];

            if (count($row) < 6) {
                return back()->with('error', 'Format CSV tidak valid. Harus ada 6 kolom: rata_rata_skor, jumlah_asn, kurang_lengkap, cukup_lengkap, lengkap, sangat_lengkap');
            }

            $skorRata2 = floatval($row[0]);
            $jumlahAsn = intval($row[1]);
            $kurangLengkap = intval($row[2]);
            $cukupLengkap = intval($row[3]);
            $lengkap = intval($row[4]);
            $sangatLengkap = intval($row[5]);

            // Determine status kelengkapan based on score
            if ($skorRata2 > 90) {
                $statusKelengkapan = 'Sangat Lengkap';
            } elseif ($skorRata2 >= 55.6) {
                $statusKelengkapan = 'Lengkap';
            } elseif ($skorRata2 >= 30) {
                $statusKelengkapan = 'Cukup Lengkap';
            } else {
                $statusKelengkapan = 'Kurang Lengkap';
            }

            // Save to database
            MonevDmsSkorRata2Nasional::create([
                'upload_date' => $uploadDate,
                'jumlah_asn' => $jumlahAsn,
                'skor_rata2_nasional' => round($skorRata2, 2),
                'status_kelengkapan' => $statusKelengkapan,
                'kurang_lengkap' => $kurangLengkap,
                'cukup_lengkap' => $cukupLengkap,
                'lengkap' => $lengkap,
                'sangat_lengkap' => $sangatLengkap,
            ]);

            DB::commit();

            return back()->with('success', "Data Skor Rata-rata Nasional berhasil diupload! Skor: " . round($skorRata2, 2) . " dengan status: {$statusKelengkapan}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat mengupload data: ' . $e->getMessage());
        }
    }

    /**
     * Delete skor rata2 nasional by upload_date
     */
    public function deleteSkorRata2Nasional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $uploadDate = Carbon::parse($request->upload_date)->format('Y-m-d');

            // Delete nasional score
            MonevDmsSkorRata2Nasional::where('upload_date', $uploadDate)->delete();

            DB::commit();

            return back()->with('success', 'Data Skor Rata-rata Nasional untuk tanggal ' . $uploadDate . ' berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
}
