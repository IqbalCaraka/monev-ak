<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsPnsScore extends Model
{
    protected $fillable = [
        'upload_id',
        'pns_id',
        'nip',
        'nama',
        'status_cpns_pns',
        'status_arsip',
        'skor_system',
        'instansi_id',
        'instansi_nama',
        'upload_date',
    ];

    protected $casts = [
        'status_arsip' => 'array', // Auto decode JSON
        'upload_date' => 'datetime',
        'skor_system' => 'decimal:2',
    ];

    // Relationship
    public function upload()
    {
        return $this->belongsTo(DmsUpload::class, 'upload_id');
    }
}