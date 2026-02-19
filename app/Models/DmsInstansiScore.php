<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsInstansiScore extends Model
{
    protected $fillable = [
        'upload_id',
        'instansi_id',
        'instansi_nama',
        'upload_date',
        'total_pns',
        'skor_instansi_calculated_system',
        'skor_instansi_calculated_csv',
        'min_skor_calculated',
        'max_skor_calculated',
        'count_80_100',
        'count_60_79',
        'count_40_59',
        'count_0_39',
        'status_kelengkapan',
        'calculation_status',
        'calculated_at',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
        'calculated_at' => 'datetime',
        'skor_instansi_calculated_system' => 'decimal:2',
        'skor_instansi_calculated_csv' => 'decimal:2',
        'min_skor_calculated' => 'decimal:2',
        'max_skor_calculated' => 'decimal:2',
    ];

    // Relationship
    public function upload()
    {
        return $this->belongsTo(DmsUpload::class, 'upload_id');
    }
}