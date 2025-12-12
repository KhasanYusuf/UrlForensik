<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TindakanForensik extends Model
{
    use HasFactory;

    protected $table = 'tindakan_forensik';
    protected $primaryKey = 'id_tindakan';

    protected $fillable = [
        'id_kasus',
        'jenis_tindakan',
        'waktu_pelaksanaan',
        'metode_forensik',
        'entry_point',
        'attacker_ip',
        'jenis_webshell',
        'hasil_tindakan',
        'petugas_forensik',
        'status_tindakan',
        'catatan',
    ];

    protected $casts = [
        'waktu_pelaksanaan' => 'datetime',
    ];

    /**
     * Relasi ke tabel Kasus (Many to One)
     */
    public function kasus()
    {
        return $this->belongsTo(Kasus::class, 'id_kasus', 'id_kasus');
    }
}
