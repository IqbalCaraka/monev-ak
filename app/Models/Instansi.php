<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instansi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'instansi';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'lokasi_id',
        'nama',
        'jenis',
        'nama_baru',
        'nama_jabatan',
        'jenis_instansi_id',
        'kantor_regional_id',
        'prov_id',
    ];
}
