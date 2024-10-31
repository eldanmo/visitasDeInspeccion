<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visitas_inspeccion', function (Blueprint $table) {
            $table->id();
            $table->text('equipo_visita')->nullable();
            $table->string('como_efectua_visita')->nullable();
            $table->string('tipo_visita')->nullable();
            $table->string('id_entidad');
            $table->date('fecha_inicio_visita')->nullable();
            $table->date('fecha_fin_visita')->nullable();
            $table->longText('enlace_apertura')->nullable();
            $table->longText('carta_salvaguarda')->nullable();
            $table->longText('documentos_cierre_visita')->nullable();
            $table->longText('hallazgos_consolidados')->nullable();
            $table->longText('proyecto_informe_final')->nullable();
            $table->longText('informe_final')->nullable();
            $table->longText('evaluacion_respuesta_entidad')->nullable();
            $table->longText('ciclo_vida_contenidos_finales')->nullable();
            $table->longText('documentos_contenidos_finales')->nullable();
            $table->string('etapa');
            $table->string('numero_informe')->nullable();
            $table->string('estado_informe');
            $table->string('estado_etapa');
            $table->date('fecha_entrega_informe')->nullable();
            $table->longText('documentos_adicionales_diagnostico')->nullable();
            $table->date('fecha_inicio_diagnostico');
            $table->date('fecha_fin_diagnostico')->nullable();
            $table->date('fecha_inicio_gestion')->nullable();
            $table->date('fecha_fin_gestion')->nullable();
            $table->string('ciclo_vida_diagnostico')->nullable();
            $table->string('usuario_diagnostico');
            $table->date('fecha_inicio_plan_visita')->nullable();
            $table->date('fecha_fin_plan_visita')->nullable();
            $table->longText('plan_visita')->nullable();
            $table->date('fecha_inicio_preparacion_informacion_organizacion_solidaria')->nullable();
            $table->date('fecha_fin_preparacion_informacion_organizacion_solidaria')->nullable();
            $table->string('usuario_preparacion_informacion_organizacion_solidaria')->nullable();
            $table->string('usuario_financiero')->nullable();
            $table->string('usuario_juridico')->nullable();
            $table->string('intendente')->nullable();
            $table->string('ciclo_vida')->nullable();
            $table->string('carpeta_drive')->nullable();
            $table->string('caracter_visita')->nullable();
            $table->text('archivos')->nullable();
            $table->text('anexos_adicionales_plan_visita')->nullable();
            $table->string('enlace_subsanacion_diagnostico')->nullable();
            $table->string('ciclo_informacion_adicional')->nullable();
            $table->string('radicado_entrada_informacion_adicional')->nullable();
            $table->string('enlace_plan_visita_ajustado')->nullable();
            $table->text('anexos_plan_visita_ajustado')->nullable();
            $table->text('anexos_subsanacion_diagnostico')->nullable();
            $table->text('anexos_adicionales_informacion_adicional_recibida')->nullable();
            $table->text('anexos_adicionales_abrir_visita')->nullable();
            $table->text('anexos_confirmacion_plan_visita')->nullable();
            $table->string('usuario_creacion');
            $table->string('ciclo_vida_confirmacion_visita');
            $table->text('usuario_actual');
            $table->string('FUID')->nullable();
            $table->string('tabla_retencion_documental')->nullable();

            $table->string('radicado_salida_comunicado_visita_empresa_solidaria')->nullable();
            $table->date('fecha_radicado_salida_comunicado_visita_empresa_solidaria')->nullable();
            $table->string('radicado_salida_comunicado_visita_revisoria_fiscal')->nullable();
            $table->date('fecha_radicado_salida_comunicado_visita_revisoria_fiscal')->nullable();
            $table->string('radicado_entrada_respuesta_entidad_comunicado_visita')->nullable();
            $table->date('fecha_radicado_entrada_respuesta_entidad_comunicado_visita')->nullable();

            $table->string('radicado_oficio_traslado_empresa_solidaria')->nullable();
            $table->date('fecha_radicado_oficio_traslado_empresa_solidaria')->nullable();
            $table->string('radicado_oficio_traslado_revisoria_fiscal')->nullable();
            $table->date('fecha_radicado_oficio_traslado_revisoria_fiscal')->nullable();

            $table->string('radicado_entrada_pronunciacion_empresa_solidaria')->nullable();
            $table->date('fecha_radicado_entrada_pronunciacion_empresa_solidaria')->nullable();
            $table->string('radicado_entrada_pronunciacion_revisoria_fiscal')->nullable();
            $table->date('fecha_radicado_entrada_pronunciacion_revisoria_fiscal')->nullable();

            $table->string('ciclo_informe_final_hallazgos')->nullable();
            $table->string('radicado_memorando_traslado')->nullable();
            $table->date('fecha_radicado_memorando_traslado')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitas_inspeccion');
    }
};
