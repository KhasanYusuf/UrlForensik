<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuktiDigital extends Model
{
    use HasFactory;

    protected $table = 'bukti_digital';
    protected $primaryKey = 'id_evidence';

    protected $fillable = [
        'id_kasus',
        'jenis_bukti',
        'file_url',
        'created_date',
        'keterangan',
    ];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    /**
     * Relasi ke tabel Kasus (Many to One)
     */
    public function kasus()
    {
        return $this->belongsTo(Kasus::class, 'id_kasus', 'id_kasus');
    }
}
