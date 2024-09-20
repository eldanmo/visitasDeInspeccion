<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsuntoEspecial extends Model
{
    use HasFactory;

    protected $table = 'asuntos_especiales';

    use HasFactory;

    protected $fillable = [
        'entidad',
        'etapa',
        'estado_etapa',
        'tipo_toma',
        'usuarios_actuales',
        'fecha_inicio_toma',
        'carpeta_drive',
        'ciclo_memorando',
        'usuario_creacion',
        'memorando_traslado',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }

    public function entidad_data()
    {
        return $this->belongsTo(Entidad::class, 'entidad');
    }

    public function conteoDias()
    {
        return $this->hasMany(ConteoDias::class, 'id_informe', 'id')
                    ->where('proceso', 'ASUNTOS_ESPECIALES');
    }

    public function etapaProceso()
    {
        return $this->hasOne(Parametro::class, 'estado', 'etapa')
                        ->where('proceso', 'ASUNTOS_ESPECIALES');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialVisitas::class, 'id_informe', 'id')
                    ->where('proceso', 'ASUNTOS_ESPECIALES');
    }

    public function anexos()
    {
        return $this->hasMany(AnexoRegistro::class, 'id_tipo_anexo', 'id')
                            ->where('proceso', 'ASUNTOS_ESPECIALES')
                            ->where('estado', 'ACTIVO');
    }
}
