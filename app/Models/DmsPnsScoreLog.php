<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsPnsScoreLog extends Model
{
    protected $table = 'dms_pns_score_log';

    protected $fillable = [
        'upload_id',
        'pns_id',
        'status_arsip',
        'skor_csv',
        'skor_calculated',
        'status_kelengkapan',
    ];

    protected $casts = [
        'status_arsip' => 'array', // Auto decode JSON
        'skor_csv' => 'decimal:2',
        'skor_calculated' => 'decimal:2',
    ];

    // Relationships
    public function upload()
    {
        return $this->belongsTo(DmsUpload::class, 'upload_id');
    }

    public function dmsPns()
    {
        return $this->belongsTo(DmsPns::class, 'pns_id', 'pns_id');
    }
}
