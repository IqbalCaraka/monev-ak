<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArsipPensiunFile extends Model
{
    protected $table = 'arsip_pensiun_files';

    protected $fillable = [
        'upload_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(ArsipPensiunUpload::class, 'upload_id');
    }
}
