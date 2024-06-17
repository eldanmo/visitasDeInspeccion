<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitaInspeccion extends Model
{
    use HasFactory;

    protected $table = 'visitas_inspeccion';

    use HasFactory;

    protected $fillable = [
        'equipo_visita',
        'como_efectua_visita',
        'tipo_visita',
        'id_entidad',
        'fecha_inicio_visita',
        'fecha_fin_visita',
        'hallazgos_consolidados',
        'proyecto_informe_final',
        'informe_final',
        'evaluacion_respuesta_entidad',
        'etapa',
        'consecutivo',
        'numero_informe',
        'estado_informe',
        'estado_etapa',
        'fecha_entrega_informe',
        'fecha_inicio_diagnostico',
        'fecha_fin_diagnostico',
        'usuario_diagnostico',
        'fecha_inicio_plan_visita',
        'fecha_fin_plan_visita',
        'fecha_inicio_preparacion_informacion_organizacion_solidaria',
        'fecha_fin_preparacion_informacion_organizacion_solidaria',
        'usuario_preparacion_informacion_organizacion_solidaria',
        'usuario_financiero',
        'usuario_juridico',
        'intendente',
        'ciclo_vida',
        'archivos',
        'usuario_creacion',
        'ciclo_vida_contenidos_finales',
        'documentos_contenidos_finales',
        'fecha_inicio_gestion',
        'fecha_fin_gestion',
        'carpeta_drive',
        'documentos_adicionales_diagnostico',
        'enlace_subsanacion_diagnostico',
        'anexos_subsanacion_diagnostico',
        'anexos_adicionales_plan_visita',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function conteoDias()
    {
        return $this->hasMany(ConteoDias::class, 'id_informe', 'id');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialVisitas::class, 'id_informe', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }

    public function usuarioDiagnostico()
    {
        return $this->belongsTo(User::class, 'usuario_diagnostico');
    }

    public function etapaProceso()
    {
        return $this->hasOne(Parametro::class, 'estado', 'etapa');
    }

    public function grupoInspeccion()
    {
        return $this->hasMAny(GrupoVisitaInspeccion::class, 'id_informe', 'id')
                    ->where('estado', 'ACTIVO');
    }
}
