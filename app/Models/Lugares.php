<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lugares extends Model
{
    protected $table = 'lugares';

    protected $fillable = [
        'region',
        'codigo_departamento',
        'departamento',
        'codigo_ciudad',
        'ciudad',
    ];
}

dd('Hola mundoooooo');
