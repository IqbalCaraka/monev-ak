<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogActivity extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    // ID adalah UUID string, bukan auto-increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'transaction_id',
        'event_name',
        'details',
        'created_by_id',
        'created_by_nama',
        'created_by_nip',
        'created_at_log',
        'object_pns_id',
    ];

    /**
     * Relasi ke Pegawai
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'created_by_nip', 'nip');
    }

    /**
     * Cek apakah aktivitas ini masuk kategori "Inject - Unggah Dokumen"
     */
    public function isInjectUnggahDokumen(): bool
    {
        return $this->event_name === 'unggah_dokumen'
               && $this->details !== 'unggah_dokumen';
    }

    /**
     * Cek apakah aktivitas ini masuk kategori "Inject - Mapping Dokumen"
     */
    public function isInjectMappingDokumen(): bool
    {
        return $this->event_name === 'mapping_dokumen'
               && stripos($this->details, 'inject') !== false;
    }

    /**
     * Cek apakah aktivitas ini masuk kategori "Unggah Dokumen" (normal)
     */
    public function isUnggahDokumen(): bool
    {
        return $this->event_name === 'unggah_dokumen'
               && $this->details === 'unggah_dokumen';
    }

    /**
     * Cek apakah aktivitas ini masuk kategori "Mapping Dokumen" (non-inject)
     */
    public function isMappingDokumen(): bool
    {
        return $this->event_name === 'mapping_dokumen'
               && (stripos($this->details, 'inject') === false || empty($this->details));
    }

    /**
     * Get kategori aktivitas berdasarkan business logic
     */
    public function getKategoriAttribute(): string
    {
        if ($this->isInjectUnggahDokumen()) {
            return 'Inject - Unggah Dokumen';
        }

        if ($this->isInjectMappingDokumen()) {
            return 'Inject - Mapping Dokumen';
        }

        if ($this->isUnggahDokumen()) {
            return 'Unggah Dokumen';
        }

        if ($this->isMappingDokumen()) {
            return 'Mapping Dokumen';
        }

        // Convert event_name lainnya ke Title Case
        return $this->formatEventName($this->event_name);
    }

    /**
     * Format event_name dari snake_case ke Title Case
     */
    private function formatEventName(string $eventName): string
    {
        // Mapping khusus untuk event_name tertentu
        $customMapping = [
            'lock_arsip' => 'Lock Arsip',
            'baca_arsip' => 'Baca Arsip',
            'menambahkan_user' => 'Menambahkan User',
            'menghapus_user' => 'Menghapus User',
            'Laporan-Kekurangan-Riwayat' => 'Laporan Kekurangan Riwayat',
        ];

        if (isset($customMapping[$eventName])) {
            return $customMapping[$eventName];
        }

        // Default: replace underscore dengan spasi dan capitalize setiap kata
        return ucwords(str_replace('_', ' ', $eventName));
    }
}
