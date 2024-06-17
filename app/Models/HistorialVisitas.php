<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialVisitas extends Model
{
    protected $table = 'historial_informes';

    use HasFactory;

    protected $fillable = [
        'id_informe',
        'accion',
        'etapa',
        'estado',
        'fecha_creacion',
        'observaciones',
        'estado_etapa',
        'usuario_asignado',
        'fecha_inicio',
        'fecha_fin',
        'conteo_dias',
        'fecha_limite_etapa',
        'usuario_creacion',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }
}
