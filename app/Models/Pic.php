<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pic extends Model
{
    protected $table = 'pic_dms';

    protected $fillable = [
        'ketua_nip',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: PIC DMS punya 1 ketua (dari pegawai)
     */
    public function ketua(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ketua_nip', 'nip');
    }

    /**
     * Relationship: PIC DMS punya banyak anggota pegawai (Many-to-Many)
     */
    public function anggota(): BelongsToMany
    {
        return $this->belongsToMany(Pegawai::class, 'pic_dms_pegawai', 'pic_dms_id', 'pegawai_nip', 'id', 'nip')
            ->withPivot('role', 'assigned_at')
            ->withTimestamps()
            ->orderBy('pic_dms_pegawai.role', 'desc'); // Ketua dulu, baru anggota
    }

    /**
     * Relationship: PIC DMS pegang banyak instansi (Many-to-Many)
     */
    public function instansi(): BelongsToMany
    {
        return $this->belongsToMany(Instansi::class, 'pic_dms_instansi', 'pic_dms_id', 'instansi_id')
            ->withPivot('assigned_at')
            ->withTimestamps()
            ->orderBy('instansi.nama');
    }

    /**
     * Scope: Hanya PIC yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Helper: Total anggota tim (termasuk ketua)
     */
    public function getTotalAnggotaAttribute()
    {
        return $this->anggota()->count();
    }

    /**
     * Helper: Total instansi yang dipegang
     */
    public function getTotalInstansiAttribute()
    {
        return $this->instansi()->count();
    }
}
