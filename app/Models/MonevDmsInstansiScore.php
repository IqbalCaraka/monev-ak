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
        'monev_status_kelengkapan',
    ];

    protected $casts = [
        'upload_date' => 'date',
        'monev_skor_instansi' => 'decimal:2',
    ];
}
