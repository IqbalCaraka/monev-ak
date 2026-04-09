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

    /**
     * Get PIC Stats - AJAX Lazy Load
     */
    public function getPicStats(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $picStats = $this->getPicStatsSummaryApi($dateFrom, $dateTo);

        $formattedData = $picStats->map(function($pic, $index) use ($picStats) {
            return [
                'no' => $picStats->firstItem() + $index,
                'ketua_nama' => $pic->ketua_nama ?: 'Tidak ada ketua',
                'ketua_nip' => $pic->ketua_nip,
                'total_anggota' => number_format($pic->total_anggota),
                'total_aktivitas' => number_format($pic->total_aktivitas),
                'total_mapping' => number_format($pic->total_mapping),
                'total_inject' => number_format($pic->total_inject),
                'detail_url' => route('pic.show', $pic->pic_id),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $picStats->currentPage(),
                'last_page' => $picStats->lastPage(),
                'per_page' => $picStats->perPage(),
                'total' => $picStats->total(),
                'from' => $picStats->firstItem(),
                'to' => $picStats->lastItem(),
            ]
        ]);
    }

    /**
     * Get Mapping Dokumen Stats - AJAX Lazy Load
     */
    public function getMappingDokumen(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        // Use summary table when no date filter for fast query
        if (!$dateFrom && !$dateTo) {
            $mappingDokumen = $this->getMappingDokumenFromSummary($search);
        } else {
            $mappingDokumen = $this->getMappingDokumenSummaryApi($dateFrom, $dateTo, $search);
        }

        $formattedData = $mappingDokumen->map(function($item, $index) use ($mappingDokumen) {
            return [
                'no' => $mappingDokumen->firstItem() + $index,
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_per_dokumen' => number_format($item->total_per_dokumen),
                'total_per_object_pns' => number_format($item->total_per_object_pns),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $mappingDokumen->currentPage(),
                'last_page' => $mappingDokumen->lastPage(),
                'per_page' => $mappingDokumen->perPage(),
                'total' => $mappingDokumen->total(),
                'from' => $mappingDokumen->firstItem(),
                'to' => $mappingDokumen->lastItem(),
            ]
        ]);
    }

    /**
     * Get Inject Dokumen Stats - AJAX Lazy Load
     */
    public function getInjectDokumen(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        if (!$dateFrom && !$dateTo) {
            $injectDokumen = $this->getInjectDokumenFromSummary($search);
        } else {
            $injectDokumen = $this->getInjectDokumenSummaryApi($dateFrom, $dateTo, $search);
        }

        $formattedData = $injectDokumen->map(function($item, $index) use ($injectDokumen) {
            return [
                'no' => $injectDokumen->firstItem() + $index,
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_per_dokumen' => number_format($item->total_per_dokumen),
                'total_per_object_pns' => number_format($item->total_per_object_pns),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $injectDokumen->currentPage(),
                'last_page' => $injectDokumen->lastPage(),
                'per_page' => $injectDokumen->perPage(),
                'total' => $injectDokumen->total(),
                'from' => $injectDokumen->firstItem(),
                'to' => $injectDokumen->lastItem(),
            ]
        ]);
    }

    private function getPicStatsSummaryApi($dateFrom = null, $dateTo = null)
    {
        $query = DB::table('pic_dms')
            ->leftJoin('pegawai as ketua', 'pic_dms.ketua_nip', '=', 'ketua.nip')
            ->leftJoin('pic_dms_pegawai', 'pic_dms.id', '=', 'pic_dms_pegawai.pic_dms_id')
            ->leftJoin('log_aktivitas as la', function($join) use ($dateFrom, $dateTo) {
                $join->on('pic_dms_pegawai.pegawai_nip', '=', 'la.created_by_nip');
                if ($dateFrom) {
                    $join->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
                }
                if ($dateTo) {
                    $join->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->select(
                'pic_dms.id as pic_id',
                DB::raw('MAX(ketua.nama) as ketua_nama'),
                DB::raw('MAX(ketua.nip) as ketua_nip'),
                DB::raw('COUNT(DISTINCT pic_dms_pegawai.pegawai_nip) as total_anggota'),
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.is_inject = 1 THEN 1 END) as total_inject'),
                DB::raw('(COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) + COUNT(CASE WHEN la.is_inject = 1 THEN 1 END)) as total_aktivitas')
            )
            ->where('pic_dms.is_active', 1)
            ->groupBy('pic_dms.id')
            ->orderByDesc('total_aktivitas');

        return $query->paginate(10);
    }

    private function getMappingDokumenFromSummary($search = null)
    {
        $query = DB::table('pegawai_aktivitas_summary as pas')
            ->leftJoin('pegawai as p', 'pas.nip', '=', 'p.nip')
            ->select(
                'pas.nip',
                DB::raw('COALESCE(p.nama, pas.nip) as nama'),
                'pas.total_aktivitas as total_per_dokumen',
                'pas.total_per_object_pns'
            )
            ->where('pas.kategori_aktivitas', 'Mapping Dokumen');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('pas.nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('total_per_dokumen')->paginate(10);
    }

    private function getMappingDokumenSummaryApi($dateFrom = null, $dateTo = null, $search = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(MAX(p.nama), MAX(la.created_by_nama)) as nama'),
                DB::raw('COUNT(*) as total_per_dokumen'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as total_per_object_pns')
            )
            ->where('la.event_name', 'mapping_dokumen')
            ->where(function($q) {
                $q->where('la.is_inject', 0)->orWhereNull('la.is_inject');
            })
            ->whereNotNull('la.created_by_nip');

        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query
            ->groupBy('la.created_by_nip')
            ->orderByDesc('total_per_dokumen')
            ->paginate(10);
    }

    private function getInjectDokumenFromSummary($search = null)
    {
        // Inject has multiple sub-categories: "Inject - Unggah Dokumen", "Inject - Mapping Dokumen", "Inject Dokumen"
        $query = DB::table('pegawai_aktivitas_summary as pas')
            ->leftJoin('pegawai as p', 'pas.nip', '=', 'p.nip')
            ->select(
                'pas.nip',
                DB::raw('COALESCE(p.nama, pas.nip) as nama'),
                DB::raw('SUM(pas.total_aktivitas) as total_per_dokumen'),
                DB::raw('SUM(pas.total_per_object_pns) as total_per_object_pns')
            )
            ->where('pas.kategori_aktivitas', 'like', 'Inject%');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('pas.nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query->groupBy('pas.nip', 'p.nama')
            ->orderByDesc('total_per_dokumen')
            ->paginate(10);
    }

    private function getInjectDokumenSummaryApi($dateFrom = null, $dateTo = null, $search = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(MAX(p.nama), MAX(la.created_by_nama)) as nama'),
                DB::raw('COUNT(*) as total_per_dokumen'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as total_per_object_pns')
            )
            ->where('la.is_inject', 1)
            ->whereNotNull('la.created_by_nip');

        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query
            ->groupBy('la.created_by_nip')
            ->orderByDesc('total_per_dokumen')
            ->paginate(10);
    }

    /**
     * Get Approval Dokumen Stats - AJAX Lazy Load
     */
    public function getApprovalDokumen(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        if (!$dateFrom && !$dateTo) {
            $approvalDokumen = $this->getApprovalDokumenFromSummary($search);
        } else {
            $approvalDokumen = $this->getApprovalDokumenFiltered($dateFrom, $dateTo, $search);
        }

        $formattedData = $approvalDokumen->map(function($item, $index) use ($approvalDokumen) {
            return [
                'no' => $approvalDokumen->firstItem() + $index,
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_approval' => number_format($item->total_approval),
                'total_per_object_pns' => number_format($item->total_per_object_pns),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $approvalDokumen->currentPage(),
                'last_page' => $approvalDokumen->lastPage(),
                'per_page' => $approvalDokumen->perPage(),
                'total' => $approvalDokumen->total(),
                'from' => $approvalDokumen->firstItem(),
                'to' => $approvalDokumen->lastItem(),
            ]
        ]);
    }

    private function getApprovalDokumenFromSummary($search = null)
    {
        $query = DB::table('pegawai_aktivitas_summary as pas')
            ->leftJoin('pegawai as p', 'pas.nip', '=', 'p.nip')
            ->select(
                'pas.nip',
                DB::raw('COALESCE(p.nama, pas.nip) as nama'),
                'pas.total_aktivitas as total_approval',
                'pas.total_per_object_pns'
            )
            ->where('pas.kategori_aktivitas', 'Approval Dokumen MyASN');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('pas.nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('total_approval')->paginate(10);
    }

    private function getApprovalDokumenFiltered($dateFrom, $dateTo, $search = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(MAX(p.nama), MAX(la.created_by_nama)) as nama'),
                DB::raw('COUNT(*) as total_approval'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as total_per_object_pns')
            )
            ->where('la.event_name', 'approve_upload_dok_myasn')
            ->where(function($q) {
                $q->where('la.is_inject', 0)->orWhereNull('la.is_inject');
            })
            ->whereNotNull('la.created_by_nip');

        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query
            ->groupBy('la.created_by_nip')
            ->orderByDesc('total_approval')
            ->paginate(10);
    }
}
