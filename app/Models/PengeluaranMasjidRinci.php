<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranMasjidRinci extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['nominal' => 'decimal:2'];

    public function header()
    {
        return $this->belongsTo(PengeluaranMasjidHeader::class, 'pengeluaran_masjid_header_id');
    }
}
