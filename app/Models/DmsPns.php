<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsPns extends Model
{
    protected $table = 'dms_pns';

    protected $fillable = [
        'pns_id',
        'nip',
        'nama',
        'status_cpns_pns',
        'instansi_id',
        'instansi_nama',
    ];

    // Relationships
    public function scoreLogs()
    {
        return $this->hasMany(DmsPnsScoreLog::class, 'dms_pns_id');
    }
}
