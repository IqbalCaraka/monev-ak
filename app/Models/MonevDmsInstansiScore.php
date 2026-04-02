<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonevDmsInstansiScore extends Model
{
    protected $table = 'monev_dms_instansi_score';

    protected $fillable = [
        'id_instansi',
        'nama_instansi',
        'upload_date',
        'monev_skor_instansi',
        'jumlah_asn',
        'sangat_lengkap',
        'lengkap',
        'cukup_lengkap',
        'kurang_lengkap',
        'kantor_regional_id',
        'monev_status_kelengkapan',
    ];

    protected $casts = [
        'upload_date' => 'date',
        'monev_skor_instansi' => 'decimal:2',
    ];
}
