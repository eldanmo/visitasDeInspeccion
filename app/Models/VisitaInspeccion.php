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
        'caracter_visita',
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
        'anexos_adicionales_informacion_adicional_recibida',
        'ciclo_informacion_adicional',
        'radicado_entrada_informacion_adicional',
        'enlace_plan_visita_ajustado',
        'anexos_plan_visita_ajustado',
        'anexos_confirmacion_plan_visita',
        'anexos_adicionales_abrir_visita',
        'ciclo_vida_confirmacion_visita',

        'radicado_salida_comunicado_visita_empresa_solidaria',
        'fecha_radicado_salida_comunicado_visita_empresa_solidaria',
        'radicado_salida_comunicado_visita_revisoria_fiscal',
        'fecha_radicado_salida_comunicado_visita_revisoria_fiscal',

        'radicado_entrada_respuesta_entidad_comunicado_visita',
        'fecha_radicado_entrada_respuesta_entidad_comunicado_visita',

        'radicado_oficio_traslado_empresa_solidaria',
        'fecha_radicado_oficio_traslado_empresa_solidaria',
        'radicado_oficio_traslado_revisoria_fiscal',
        'fecha_radicado_oficio_traslado_revisoria_fiscal',

        'radicado_entrada_pronunciacion_empresa_solidaria',
        'fecha_radicado_entrada_pronunciacion_empresa_solidaria',
        'radicado_entrada_pronunciacion_revisoria_fiscal',
        'fecha_radicado_entrada_pronunciacion_revisoria_fiscal',

        'ciclo_informe_final_hallazgos',
        'radicado_memorando_traslado',
        'fecha_radicado_memorando_traslado',

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

    public function solicitudDiasAdicionales()
    {
        return $this->hasMAny(SolicitudDiaAdicional::class, 'id_informe', 'id');
    }

    public function anexos()
    {
        return $this->hasMAny(AnexoRegistro::class, 'id_tipo_anexo', 'id')
                            ->where('proceso', 'VISITA DE INSPECCIÓN')
                            ->where('estado', 'ACTIVO');
    }

    public function diasActuales()
    {
        return $this->hasOne(ConteoDias::class, 'id_informe', 'id')
                    ->where('etapa', '!=', 'CANCELADO')
                    ->where('etapa', '!=', 'FINALIZADO')
                    ->where('etapa', '!=', 'SUSPENDIDO')
                    ->latest();
    }
}
