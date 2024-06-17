<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConteoDias extends Model
{

    protected $table = 'conteo_dias';

    use HasFactory;

    protected $fillable = [
        'id_informe',
        'etapa',
        'fecha_inicio',
        'fecha_fin',
        'dias_habiles',
        'conteo_dias',
        'fecha_limite_etapa',
        'usuario_creacion'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }
}
