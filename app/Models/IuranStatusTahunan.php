<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IuranStatusTahunan extends Model
{
    use HasFactory;

    protected $table = 'iuran_status_tahunan';

    protected $guarded = ['id'];

    protected $casts = [
        'target_iuran' => 'decimal:2',
    ];
}
