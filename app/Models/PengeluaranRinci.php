<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranRinci extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['nominal' => 'decimal:2'];

    public function header()
    {
        return $this->belongsTo(PengeluaranHeader::class, 'pengeluaran_header_id');
    }
}
