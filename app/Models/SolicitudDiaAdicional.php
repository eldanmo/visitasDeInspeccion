<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudDiaAdicional extends Model
{
    use HasFactory;

    protected $table = 'solicitud_dias_adicionales';

    protected $fillable = [
        'dias',
        'observacion',
        'estado',
        'id_informe',
        'usuario_creacion',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }

    public function historial()
    {
        return $this->hasMany( HistoricoSolicitudDiaAdicional::class, 'id_solicitud', 'id');
    }

    public function anexosDiasAdicionales()
    {
        return $this->hasMAny(AnexoRegistro::class, 'id_sub_proceso', 'id')
                            ->where('proceso', 'VISITA DE INSPECCIÓN')
                            ->where('estado', 'ACTIVO');
    }
}
