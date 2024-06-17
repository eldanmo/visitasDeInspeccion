<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaNoLaboral extends Model
{
    protected $table = 'dia_no_laborable';

    use HasFactory;

    protected $fillable = [
        'descripcion_dia',
        'dia',
    ];
}
