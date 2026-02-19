<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsUpload extends Model
{
    protected $fillable = [
        'filename',
        'upload_date',
        'total_records',
        'processed_records',
        'status',
        'error_message',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
    ];

    // Relationships
    public function scoreLogs()
    {
        return $this->hasMany(DmsPnsScoreLog::class, 'upload_id');
    }

    public function instansiScores()
    {
        return $this->hasMany(DmsInstansiScore::class, 'upload_id');
    }

    // Helper method
    public function getProgressPercentage()
    {
        return $this->total_records > 0
            ? round(($this->processed_records / $this->total_records) * 100, 2)
            : 0;
    }
}
