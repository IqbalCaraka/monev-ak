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
    ];

    protected $casts = [
        'upload_date' => 'date',
        'skor_rata2_nasional' => 'decimal:2',
        'jumlah_asn' => 'integer',
    ];
}
