<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoredSite extends Model
{
    use HasFactory;

    protected $table = 'monitored_sites';
    protected $primaryKey = 'id_site';

    protected $fillable = [
        'site_url',
        'ip_address',
        'baseline_hash',
        'baseline_file_path',
        'last_checked_at',
        'status',
        'sensitivity',
        'allowed_domains',
        'baseline_script_count',
        'selector_static',
        'selector_dynamic',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'sensitivity' => 'integer',
        'baseline_script_count' => 'integer',
        'allowed_domains' => 'array',
    ];

    /**
     * Relasi ke tabel Kasus (One to Many)
     */
    public function kasus()
    {
        return $this->hasMany(Kasus::class, 'id_site', 'id_site');
    }
}
