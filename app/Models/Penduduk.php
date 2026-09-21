<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penduduk extends Model
{
    use HasFactory;

    protected $table = 'penduduks';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
        'aktif' => 'boolean',
    ];
}
