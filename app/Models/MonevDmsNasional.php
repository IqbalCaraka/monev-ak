<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonevDmsNasional extends Model
{
    protected $table = 'monev_dms_nasional';

    protected $fillable = [
        'upload_date',
        'total_instansi',
        'monev_skor_nasional',
        'count_sangat_lengkap',
        'count_lengkap',
        'count_cukup_lengkap',
        'count_kurang_lengkap',
    ];

    protected $casts = [
        'upload_date' => 'date',
        'monev_skor_nasional' => 'decimal:2',
    ];
}
