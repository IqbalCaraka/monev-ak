<?php

namespace App\Http\Controllers;

use App\Models\ArsipPensiunUpload;
use App\Models\ArsipPensiunFile;
use App\Jobs\ProcessArsipPensiunZip;
use Illuminate\Http\Request;
use ZipArchive;

class ArsipPensiunController extends Controller
{
    public function index()
    {
        $uploads = ArsipPensiunUpload::orderByDesc('created_at')->paginate(15);
        return view('arsip-pensiun.index', compact('uploads'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'nama_pengupload' => 'required|string|max:255',
            'zip_file' => 'required|file|max:512000', // max 500MB
        ]);

        $file = $request->file('zip_file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['zip', 'rar'])) {
            return back()->with('error', 'Format file harus ZIP atau RAR.');
        }

        try {
            $originalFilename = $file->getClientOriginalName();
            $filename = uniqid() . '_' . time() . '.' . $ext;
            $fullPath = storage_path('app/arsip_pensiun_zips/' . $filename);

            if (!file_exists(storage_path('app/arsip_pensiun_zips'))) {
                mkdir(storage_path('app/arsip_pensiun_zips'), 0755, true);
            }

            $file->move(storage_path('app/arsip_pensiun_zips'), $filename);

            $upload = ArsipPensiunUpload::create([
                'nama_pengupload' => $request->nama_pengupload,
                'zip_filename' => $originalFilename,
                'zip_path' => 'arsip_pensiun_zips/' . $filename,
                'status' => 'pending',
                'message' => 'Menunggu diproses...',
                'tanggal_unggah' => now(),
            ]);

            ProcessArsipPensiunZip::dispatch($upload->id, $fullPath);

            return redirect()->route('arsip-pensiun.show', $upload->id)
                ->with('success', 'File berhasil diunggah! Proses ekstraksi dimulai di background.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $upload = ArsipPensiunUpload::findOrFail($id);
        $files = $upload->files()->orderBy('original_filename')->paginate(50);
        return view('arsip-pensiun.show', compact('upload', 'files'));
    }

    public function checkStatus($id)
    {
        $upload = ArsipPensiunUpload::findOrFail($id);

        return response()->json([
            'status' => $upload->status,
            'message' => $upload->message,
            'jumlah_dokumen' => $upload->jumlah_dokumen,
        ]);
    }

    /**
     * Halaman semua dokumen dari seluruh upload
     */
    public function allFiles()
    {
        $totalFiles = ArsipPensiunFile::count();
        return view('arsip-pensiun.all-files', compact('totalFiles'));
    }

    /**
     * API: Get files with search & pagination (AJAX)
     */
    public function apiFiles(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 50;

        $query = ArsipPensiunFile::with('upload:id,nama_pengupload')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where('original_filename', 'like', "%{$search}%");
        }

        $files = $query->simplePaginate($perPage);

        $data = $files->map(function ($file, $index) use ($files) {
            $size = $file->file_size;
            if ($size >= 1048576) $sizeStr = number_format($size / 1048576, 1) . ' MB';
            elseif ($size >= 1024) $sizeStr = number_format($size / 1024, 1) . ' KB';
            else $sizeStr = $size . ' B';

            $ext = strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION));

            return [
                'id' => $file->id,
                'upload_id' => $file->upload_id,
                'no' => ($files->currentPage() - 1) * $files->perPage() + $index + 1,
                'original_filename' => $file->original_filename,
                'nama_pengupload' => $file->upload->nama_pengupload ?? '-',
                'ext' => strtoupper($ext),
                'is_pdf' => $ext === 'pdf',
                'size' => $sizeStr,
                'date' => $file->created_at ? $file->created_at->format('d/m/Y') : '-',
                'download_url' => route('arsip-pensiun.download-file', [$file->upload_id, $file->id]),
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $files->currentPage(),
            'has_more' => $files->hasMorePages(),
            'has_prev' => $files->currentPage() > 1,
        ]);
    }

    public function downloadFile($uploadId, $fileId)
    {
        $file = ArsipPensiunFile::where('id', $fileId)
            ->where('upload_id', $uploadId)
            ->firstOrFail();

        $fullPath = storage_path('app/' . $file->file_path);

        if (!file_exists($fullPath)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        return response()->download($fullPath, $file->original_filename);
    }

    /**
     * Download selected files as ZIP
     */
    public function downloadSelected(Request $request)
    {
        $fileIds = $request->input('file_ids', []);

        if (empty($fileIds)) {
            return response()->json(['error' => 'Tidak ada file yang dipilih.'], 400);
        }

        $files = ArsipPensiunFile::whereIn('id', $fileIds)->get();

        if ($files->isEmpty()) {
            return response()->json(['error' => 'File tidak ditemukan.'], 404);
        }

        $zipFilename = 'Arsip_Pensiun_Selected_' . date('Ymd_His') . '.zip';
        $tempZipPath = storage_path('app/temp/' . $zipFilename);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Gagal membuat file ZIP.'], 500);
        }

        foreach ($files as $file) {
            $fullPath = storage_path('app/' . $file->file_path);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file->original_filename);
            }
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    public function downloadAll($uploadId)
    {
        $upload = ArsipPensiunUpload::findOrFail($uploadId);
        $files = $upload->files()->get();

        if ($files->isEmpty()) {
            return back()->with('error', 'Tidak ada file untuk diunduh.');
        }

        $zipFilename = 'Arsip_Pensiun_' . str_replace(' ', '_', $upload->nama_pengupload) . '_' . date('Ymd_His') . '.zip';
        $tempZipPath = storage_path('app/temp/' . $zipFilename);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        foreach ($files as $file) {
            $fullPath = storage_path('app/' . $file->file_path);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file->original_filename);
            }
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    /**
     * Download ALL files across all uploads
     */
    public function downloadAllFiles()
    {
        $files = ArsipPensiunFile::all();

        if ($files->isEmpty()) {
            return back()->with('error', 'Tidak ada file untuk diunduh.');
        }

        $zipFilename = 'Arsip_Pensiun_Semua_' . date('Ymd_His') . '.zip';
        $tempZipPath = storage_path('app/temp/' . $zipFilename);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        foreach ($files as $file) {
            $fullPath = storage_path('app/' . $file->file_path);
            if (file_exists($fullPath)) {
                // Prefix with upload name to avoid filename conflicts
                $prefix = $file->upload_id;
                $zip->addFile($fullPath, $prefix . '/' . $file->original_filename);
            }
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    public function destroy($id)
    {
        $upload = ArsipPensiunUpload::findOrFail($id);

        $extractDir = storage_path('app/arsip_pensiun/' . $id);
        if (file_exists($extractDir)) {
            $this->deleteDirectory($extractDir);
        }

        $zipPath = storage_path('app/' . $upload->zip_path);
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $upload->delete();

        return redirect()->route('arsip-pensiun.index')
            ->with('success', 'Data upload berhasil dihapus.');
    }

    private function deleteDirectory($dir)
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
