<?php

namespace App\Http\Controllers;

use App\Models\Pic;
use App\Models\Pegawai;
use App\Models\Instansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PicController extends Controller
{
    /**
     * Display a listing of PICs
     */
    public function index()
    {
        $pics = Pic::with(['ketua', 'anggota', 'instansi'])
            ->withCount(['anggota', 'instansi'])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('pic.index', compact('pics'));
    }

    /**
     * Show the form for creating a new PIC
     */
    public function create()
    {
        $pegawai = Pegawai::where('is_active', true)
            ->orderBy('nama')
            ->get();

        $instansi = Instansi::orderBy('nama')->get();

        return view('pic.create', compact('pegawai', 'instansi'));
    }

    /**
     * Store a newly created PIC DMS in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'ketua_nip' => 'required|exists:pegawai,nip',
            'anggota_nip' => 'nullable|array',
            'anggota_nip.*' => 'exists:pegawai,nip',
            'instansi_id' => 'nullable|array',
            'instansi_id.*' => 'exists:instansi,id',
        ]);

        DB::beginTransaction();
        try {
            // Create PIC DMS
            $pic = Pic::create([
                'ketua_nip' => $request->ketua_nip,
                'is_active' => true,
            ]);

            // Attach ketua sebagai anggota dengan role 'ketua'
            $pic->anggota()->attach($request->ketua_nip, [
                'role' => 'ketua',
                'assigned_at' => now(),
            ]);

            // Attach anggota tim (selain ketua)
            if ($request->has('anggota_nip')) {
                foreach ($request->anggota_nip as $nip) {
                    if ($nip != $request->ketua_nip) {
                        $pic->anggota()->attach($nip, [
                            'role' => 'anggota',
                            'assigned_at' => now(),
                        ]);
                    }
                }
            }

            // Attach instansi
            if ($request->has('instansi_id')) {
                $instansiData = [];
                foreach ($request->instansi_id as $instansiId) {
                    $instansiData[$instansiId] = ['assigned_at' => now()];
                }
                $pic->instansi()->attach($instansiData);
            }

            DB::commit();

            return redirect()->route('pic.index')
                ->with('success', 'PIC DMS berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan PIC DMS: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified PIC
     */
    public function show(Request $request, Pic $pic)
    {
        $pic->load(['ketua', 'anggota', 'instansi'])
            ->loadCount(['anggota', 'instansi']);

        // Get date filter
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get anggota NIPs
        $anggotaNips = $pic->anggota->pluck('nip')->toArray();

        if (empty($anggotaNips)) {
            return view('pic.show', [
                'pic' => $pic,
                'stats' => [
                    'total_aktivitas' => 0,
                    'total_mapping' => 0,
                    'total_inject' => 0,
                    'unique_pns' => 0,
                ],
                'performaAnggota' => [],
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        // Get overall team statistics
        $statsQuery = DB::table('log_aktivitas')
            ->whereIn('created_by_nip', $anggotaNips);

        // Apply date filter
        if ($dateFrom) {
            $statsQuery->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $statsQuery->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        // Clone query for different stats
        $stats = [
            'total_mapping' => (clone $statsQuery)
                ->where('event_name', 'mapping_dokumen')
                ->where(function($q) {
                    $q->where('is_inject', 0)
                      ->orWhereNull('is_inject');
                })
                ->count(),

            'total_inject' => (clone $statsQuery)
                ->where('is_inject', 1)
                ->count(),

            'unique_pns' => (clone $statsQuery)
                ->whereNotNull('object_pns_id')
                ->distinct('object_pns_id')
                ->count('object_pns_id'),
        ];

        // Calculate total_aktivitas
        $stats['total_aktivitas'] = $stats['total_mapping'] + $stats['total_inject'];

        // Get individual performance
        $performaAnggota = DB::table('pegawai as p')
            ->leftJoin('log_aktivitas as la', function($join) use ($dateFrom, $dateTo) {
                $join->on('p.nip', '=', 'la.created_by_nip');

                // Apply date filter in join
                if ($dateFrom) {
                    $join->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
                }
                if ($dateTo) {
                    $join->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->select(
                'p.nip',
                'p.nama',
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.is_inject = 1 THEN 1 END) as total_inject'),
                DB::raw('(COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) + COUNT(CASE WHEN la.is_inject = 1 THEN 1 END)) as total_aktivitas'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as unique_pns')
            )
            ->whereIn('p.nip', $anggotaNips)
            ->groupBy('p.nip', 'p.nama')
            ->orderByDesc('total_aktivitas')
            ->get();

        return view('pic.show', compact('pic', 'stats', 'performaAnggota', 'dateFrom', 'dateTo'));
    }

    /**
     * Export PIC report to PDF
     */
    public function exportPdf(Request $request, Pic $pic)
    {
        $pic->load(['ketua', 'anggota', 'instansi'])
            ->loadCount(['anggota', 'instansi']);

        // Get date filter
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get anggota NIPs
        $anggotaNips = $pic->anggota->pluck('nip')->toArray();

        if (empty($anggotaNips)) {
            return redirect()->back()->with('error', 'PIC tidak memiliki anggota');
        }

        // Get overall team statistics (sama seperti show method)
        $statsQuery = DB::table('log_aktivitas')
            ->whereIn('created_by_nip', $anggotaNips);

        if ($dateFrom) {
            $statsQuery->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $statsQuery->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $stats = [
            'total_mapping' => (clone $statsQuery)
                ->where('event_name', 'mapping_dokumen')
                ->where(function($q) {
                    $q->where('is_inject', 0)
                      ->orWhereNull('is_inject');
                })
                ->count(),

            'total_inject' => (clone $statsQuery)
                ->where('is_inject', 1)
                ->count(),

            'unique_pns' => (clone $statsQuery)
                ->whereNotNull('object_pns_id')
                ->distinct('object_pns_id')
                ->count('object_pns_id'),
        ];

        $stats['total_aktivitas'] = $stats['total_mapping'] + $stats['total_inject'];

        // Get individual performance
        $performaAnggota = DB::table('pegawai as p')
            ->leftJoin('log_aktivitas as la', function($join) use ($dateFrom, $dateTo) {
                $join->on('p.nip', '=', 'la.created_by_nip');

                if ($dateFrom) {
                    $join->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
                }
                if ($dateTo) {
                    $join->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->select(
                'p.nip',
                'p.nama',
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.is_inject = 1 THEN 1 END) as total_inject'),
                DB::raw('(COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) + COUNT(CASE WHEN la.is_inject = 1 THEN 1 END)) as total_aktivitas'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as unique_pns')
            )
            ->whereIn('p.nip', $anggotaNips)
            ->groupBy('p.nip', 'p.nama')
            ->orderByDesc('total_aktivitas')
            ->get();

        // Generate PDF
        $pdf = \PDF::loadView('pic.pdf-report', compact('pic', 'stats', 'performaAnggota', 'dateFrom', 'dateTo'));
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Laporan_PIC_' . str_replace(' ', '_', $pic->ketua->nama ?? 'PIC') . '_' . date('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Show the form for editing the specified PIC
     */
    public function edit(Pic $pic)
    {
        $pic->load(['anggota', 'instansi']);

        $pegawai = Pegawai::where('is_active', true)
            ->orderBy('nama')
            ->get();

        $instansi = Instansi::orderBy('nama')->get();

        return view('pic.edit', compact('pic', 'pegawai', 'instansi'));
    }

    /**
     * Update the specified PIC DMS in storage
     */
    public function update(Request $request, Pic $pic)
    {
        $request->validate([
            'ketua_nip' => 'required|exists:pegawai,nip',
            'anggota_nip' => 'nullable|array',
            'anggota_nip.*' => 'exists:pegawai,nip',
            'instansi_id' => 'nullable|array',
            'instansi_id.*' => 'exists:instansi,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update PIC DMS
            $pic->update([
                'ketua_nip' => $request->ketua_nip,
                'is_active' => $request->has('is_active'),
            ]);

            // Sync anggota
            $anggotaSync = [];

            // Add ketua
            $anggotaSync[$request->ketua_nip] = [
                'role' => 'ketua',
                'assigned_at' => now(),
            ];

            // Add other anggota
            if ($request->has('anggota_nip')) {
                foreach ($request->anggota_nip as $nip) {
                    if ($nip != $request->ketua_nip) {
                        $anggotaSync[$nip] = [
                            'role' => 'anggota',
                            'assigned_at' => now(),
                        ];
                    }
                }
            }

            $pic->anggota()->sync($anggotaSync);

            // Sync instansi
            if ($request->has('instansi_id')) {
                $instansiSync = [];
                foreach ($request->instansi_id as $instansiId) {
                    $instansiSync[$instansiId] = ['assigned_at' => now()];
                }
                $pic->instansi()->sync($instansiSync);
            } else {
                $pic->instansi()->detach();
            }

            DB::commit();

            return redirect()->route('pic.index')
                ->with('success', 'PIC DMS berhasil diupdate!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate PIC DMS: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified PIC DMS from storage
     */
    public function destroy(Pic $pic)
    {
        try {
            $pic->delete();

            return redirect()->route('pic.index')
                ->with('success', 'PIC DMS berhasil dihapus!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus PIC DMS: ' . $e->getMessage());
        }
    }

    /**
     * Toggle PIC DMS status (active/inactive)
     */
    public function toggleActive(Pic $pic)
    {
        $pic->update(['is_active' => !$pic->is_active]);

        $status = $pic->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "PIC DMS berhasil {$status}!",
            'is_active' => $pic->is_active
        ]);
    }
}
