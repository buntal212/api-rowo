<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranMasjidHeader extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pengeluaran' => 'date:Y-m-d',
        'total_nominal' => 'decimal:2',
    ];

    public function rincis()
    {
        return $this->hasMany(PengeluaranMasjidRinci::class, 'pengeluaran_masjid_header_id');
    }
}
