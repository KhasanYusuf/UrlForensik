<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MonitoredSite;

class Kasus extends Model
{
    use HasFactory;

    protected $table = 'kasus';
    protected $primaryKey = 'id_kasus';

    protected $fillable = [
        'id_site',
        'jenis_kasus',
        'tanggal_kejadian',
        'deskripsi_kasus',
        'status_kasus',
        'detection_source',
        'impact_level',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
    ];

    /**
     * Relasi ke tabel Korban (Many to One)
     */
    public function korban()
    {
        return $this->belongsTo(MonitoredSite::class, 'id_site', 'id_site');
    }

    /**
     * Relasi ke tabel Bukti Digital (One to Many)
     */
    public function buktiDigital()
    {
        return $this->hasMany(BuktiDigital::class, 'id_kasus', 'id_kasus');
    }

    /**
     * Relasi ke tabel Tindakan Forensik (One to Many)
     */
    public function tindakanForensik()
    {
        return $this->hasMany(TindakanForensik::class, 'id_kasus', 'id_kasus');
    }
}
