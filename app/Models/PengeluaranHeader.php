<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranHeader extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['total_nominal' => 'decimal:2', 'tanggal_pengeluaran' => 'date:Y-m-d'];

    public function rincis()
    {
        return $this->hasMany(PengeluaranRinci::class, 'pengeluaran_header_id');
    }
}
