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
    public function show(Pic $pic)
    {
        $pic->load(['ketua', 'anggota', 'instansi'])
            ->loadCount(['anggota', 'instansi']);

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
                'performaAnggota' => []
            ]);
        }

        // Get overall team statistics
        $stats = [
            'total_aktivitas' => DB::table('log_aktivitas')
                ->whereIn('created_by_nip', $anggotaNips)
                ->count(),

            'total_mapping' => DB::table('log_aktivitas')
                ->whereIn('created_by_nip', $anggotaNips)
                ->where('event_name', 'NOT LIKE', '%inject%')
                ->where('event_name', 'NOT LIKE', '%Inject%')
                ->count(),

            'total_inject' => DB::table('log_aktivitas')
                ->whereIn('created_by_nip', $anggotaNips)
                ->where(function($q) {
                    $q->where('event_name', 'LIKE', '%inject%')
                      ->orWhere('event_name', 'LIKE', '%Inject%');
                })
                ->count(),

            'unique_pns' => DB::table('log_aktivitas')
                ->whereIn('created_by_nip', $anggotaNips)
                ->whereNotNull('object_pns_id')
                ->distinct('object_pns_id')
                ->count('object_pns_id'),
        ];

        // Get individual performance
        $performaAnggota = DB::table('pegawai as p')
            ->leftJoin('log_aktivitas as la', 'p.nip', '=', 'la.created_by_nip')
            ->select(
                'p.nip',
                'p.nama',
                DB::raw('COUNT(la.id) as total_aktivitas'),
                DB::raw('COUNT(CASE WHEN la.event_name NOT LIKE "%inject%" AND la.event_name NOT LIKE "%Inject%" THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.event_name LIKE "%inject%" OR la.event_name LIKE "%Inject%" THEN 1 END) as total_inject'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as unique_pns')
            )
            ->whereIn('p.nip', $anggotaNips)
            ->groupBy('p.nip', 'p.nama')
            ->orderByDesc('total_aktivitas')
            ->get();

        return view('pic.show', compact('pic', 'stats', 'performaAnggota'));
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
