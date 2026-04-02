<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonevDmsSkorRata2Nasional extends Model
{
    protected $table = 'monev_dms_skor_rata2_nasional';

    protected $fillable = [
        'upload_date',
        'jumlah_asn',
        'skor_rata2_nasional',
        'status_kelengkapan',
        'kurang_lengkap',
        'cukup_lengkap',
        'lengkap',
        'sangat_lengkap',
    ];

    protected $casts = [
        'upload_date' => 'date',
        'skor_rata2_nasional' => 'decimal:2',
        'jumlah_asn' => 'integer',
        'kurang_lengkap' => 'integer',
        'cukup_lengkap' => 'integer',
        'lengkap' => 'integer',
        'sangat_lengkap' => 'integer',
    ];
}
