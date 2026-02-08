<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pegawai = Pegawai::with('role')->orderBy('nama')->paginate(20);
        return view('pengaturan.pegawai.index', compact('pegawai'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pegawai $pegawai)
    {
        $roles = Role::all();
        return view('pengaturan.pegawai.edit', compact('pegawai', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:18|unique:pegawai,nip,' . $pegawai->id,
            'jabatan' => 'required|string|max:255',
            'golongan' => 'required|string|max:10',
            'email' => 'nullable|email|unique:pegawai,email,' . $pegawai->id,
            'role_id' => 'nullable|exists:roles,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($pegawai->photo && file_exists(public_path('uploads/photos/' . $pegawai->photo))) {
                unlink(public_path('uploads/photos/' . $pegawai->photo));
            }

            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/photos'), $filename);
            $validated['photo'] = $filename;
        }

        $pegawai->update($validated);

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil diupdate');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Pegawai $pegawai)
    {
        $pegawai->update(['is_active' => !$pegawai->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $pegawai->is_active,
            'message' => 'Status pegawai berhasil diubah'
        ]);
    }
}
