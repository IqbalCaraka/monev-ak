<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArsipPensiunUpload extends Model
{
    protected $table = 'arsip_pensiun_uploads';

    protected $fillable = [
        'nama_pengupload',
        'zip_filename',
        'zip_path',
        'jumlah_dokumen',
        'status',
        'message',
        'tanggal_unggah',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ArsipPensiunFile::class, 'upload_id');
    }
}
