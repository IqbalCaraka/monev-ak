<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PegawaiAktivitasSummary extends Model
{
    use HasFactory;

    protected $table = 'pegawai_aktivitas_summary';

    protected $fillable = [
        'nip',
        'kategori_aktivitas',
        'total_aktivitas',
        'last_activity_at',
    ];

    /**
     * Relasi ke Pegawai
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'nip', 'nip');
    }

    /**
     * Scope untuk filter berdasarkan NIP
     */
    public function scopeByNip($query, string $nip)
    {
        return $query->where('nip', $nip);
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeByKategori($query, string $kategori)
    {
        return $query->where('kategori_aktivitas', $kategori);
    }

    /**
     * Get total aktivitas untuk satu NIP
     */
    public static function getTotalByNip(string $nip): int
    {
        return self::where('nip', $nip)->sum('total_aktivitas');
    }

    /**
     * Get top aktivitas pegawai
     */
    public static function getTopPegawai(int $limit = 10)
    {
        return self::selectRaw('nip, SUM(total_aktivitas) as total')
                   ->groupBy('nip')
                   ->orderByDesc('total')
                   ->with('pegawai')
                   ->limit($limit)
                   ->get();
    }
}
