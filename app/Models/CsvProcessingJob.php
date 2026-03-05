<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvProcessingJob extends Model
{
    protected $fillable = [
        'job_id',
        'filename',
        'file_path',
        'status',
        'progress',
        'error_message',
        'result_file',
    ];

    protected $casts = [
        'progress' => 'integer',
    ];
}
