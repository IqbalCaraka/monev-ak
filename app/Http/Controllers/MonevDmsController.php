<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonevDmsInstansiScore;
use App\Models\MonevDmsNasional;
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
            'monev_csv_file' => 'required|file|mimes:csv,txt|max:51200',
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
                if (count($row) < 4) continue; // Skip invalid rows (now need 4 columns)

                $idInstansi = trim($row[0]);
                $namaInstansi = trim($row[1]);
                $skorInstansi = floatval($row[2]);
                $kantorRegionalId = trim($row[3]);

                // Determine status kelengkapan
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
}
