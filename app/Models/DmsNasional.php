<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsNasional extends Model
{
    protected $table = 'dms_nasional';

    protected $fillable = [
        'upload_id',
        'upload_date',
        'total_instansi',
        'total_pns',
        'avg_skor_nasional_system',
        'avg_skor_nasional_csv',
        'min_skor_instansi',
        'max_skor_instansi',
        'count_sangat_lengkap',
        'count_lengkap',
        'count_cukup_lengkap',
        'count_kurang_lengkap',
        'calculation_status',
        'calculated_at',
    ];

    protected $casts = [
        'upload_date' => 'date',
        'calculated_at' => 'datetime',
    ];

    public function upload()
    {
        return $this->belongsTo(DmsUpload::class, 'upload_id');
    }
}
