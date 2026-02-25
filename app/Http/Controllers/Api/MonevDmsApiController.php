<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonevDmsInstansiScore;
use Illuminate\Support\Facades\DB;

class MonevDmsApiController extends Controller
{
    public function searchInstansi(Request $request)
    {
        $uploadDate = $request->input('upload_date');
        $search = $request->input('search');
        $page = $request->input('page', 1);

        if (!$uploadDate) {
            return response()->json(['error' => 'Upload date is required'], 400);
        }

        $query = MonevDmsInstansiScore::where('upload_date', $uploadDate);

        if ($search) {
            $query->where('nama_instansi', 'like', '%' . $search . '%');
        }

        $instansi = $query->orderBy('monev_skor_instansi', 'desc')
            ->paginate(10);

        // Format data for frontend
        $formattedData = $instansi->map(function($item, $index) use ($instansi) {
            $badgeClass = match($item->monev_status_kelengkapan) {
                'Sangat Lengkap' => 'badge-success',
                'Lengkap' => 'badge-primary',
                'Cukup Lengkap' => 'badge-warning',
                'Kurang Lengkap' => 'badge-danger',
                default => 'badge-secondary'
            };

            $skorBadgeClass = $item->monev_skor_instansi > 90 ? 'badge-success' :
                             ($item->monev_skor_instansi >= 55.6 ? 'badge-primary' :
                             ($item->monev_skor_instansi >= 30 ? 'badge-warning' : 'badge-danger'));

            return [
                'no' => $instansi->firstItem() + $index,
                'id_instansi' => $item->id_instansi,
                'nama_instansi' => $item->nama_instansi,
                'monev_skor_instansi' => number_format($item->monev_skor_instansi, 2),
                'monev_status_kelengkapan' => $item->monev_status_kelengkapan,
                'badge_class' => $badgeClass,
                'skor_badge_class' => $skorBadgeClass,
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $instansi->currentPage(),
                'last_page' => $instansi->lastPage(),
                'per_page' => $instansi->perPage(),
                'total' => $instansi->total(),
                'from' => $instansi->firstItem(),
                'to' => $instansi->lastItem(),
            ]
        ]);
    }

    public function searchAktivitasPegawai(Request $request)
    {
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $page = $request->input('page', 1);

        // Determine which query to use based on date filter
        if ($dateFrom || $dateTo) {
            $aktivitas = $this->getFilteredActivitiesApi($search, $dateFrom, $dateTo);
        } else {
            $aktivitas = $this->getActivitiesFromSummaryApi($search);
        }

        // Format data for frontend
        $formattedData = $aktivitas->map(function($item, $index) use ($aktivitas) {
            return [
                'no' => $aktivitas->firstItem() + $index,
                'nip' => $item->nip,
                'nama' => $item->nama,
                'jenis_aktivitas' => $item->jenis_aktivitas,
                'total_aktivitas' => number_format($item->total_aktivitas),
                'last_activity' => $item->last_activity ?? '-',
                'detail_url' => route('aktivitas-pegawai.show', $item->nip),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $aktivitas->currentPage(),
                'last_page' => $aktivitas->lastPage(),
                'per_page' => $aktivitas->perPage(),
                'total' => $aktivitas->total(),
                'from' => $aktivitas->firstItem(),
                'to' => $aktivitas->lastItem(),
            ]
        ]);
    }

    private function getActivitiesFromSummaryApi($search = null)
    {
        $query = DB::table('pegawai_aktivitas_summary as pas')
            ->leftJoin('pegawai as p', 'pas.nip', '=', 'p.nip')
            ->select(
                'pas.nip',
                DB::raw('COALESCE(p.nama, pas.nip) as nama'),
                DB::raw('SUM(pas.total_aktivitas) as total_aktivitas'),
                DB::raw('MAX(pas.last_activity_at) as last_activity'),
                DB::raw('COUNT(DISTINCT pas.kategori_aktivitas) as jenis_aktivitas')
            )
            ->groupBy('pas.nip', 'p.nama');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('pas.nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('total_aktivitas')->paginate(20);
    }

    private function getFilteredActivitiesApi($search = null, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(p.nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_aktivitas'),
                DB::raw('MAX(la.created_at_log) as last_activity'),
                DB::raw('COUNT(DISTINCT la.event_name) as jenis_aktivitas')
            );

        // Apply date filters
        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query
            ->groupBy('la.created_by_nip', 'p.nama')
            ->orderByDesc('total_aktivitas')
            ->paginate(20);
    }
}
