<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parametro extends Model
{
    protected $table = 'parametros';

    use HasFactory;

    protected $fillable = [
        'estado',
        'dias',
        'usuario_creacion',
        'proceso',
        'orden_etapa',
    ];
}
