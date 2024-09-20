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
        Schema::create('maestro_entidades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_entidad');
            $table->string('nit');
            $table->string('razon_social');
            $table->string('sigla')->nullable();
            $table->string('nivel_supervision')->nullable();
            $table->string('naturaleza_organizacion')->nullable();
            $table->string('tipo_organizacion')->nullable();
            $table->string('categoria')->nullable();
            $table->string('grupo_niif')->nullable();
            $table->string('ciudad_municipio');
            $table->string('departamento');
            $table->string('direccion');
            $table->integer('numero_asociados')->nullable();
            $table->integer('numero_empleados')->nullable();
            $table->integer('total_activos')->nullable();
            $table->integer('total_pasivos')->nullable();
            $table->integer('total_patrimonio')->nullable();
            $table->integer('total_ingresos')->nullable();
            $table->date('fecha_ultimo_reporte')->nullable();
            $table->text('objeto_social');
            $table->string('entidad_que_vigila_segun_rues');
            $table->string('estado_matricula_rues');
            $table->string('vigilada_supersolidaria_segun_depuracion');
            $table->string('entidad_debe_vigilar_segun_depuracion')->nullable();
            $table->string('permiten_notificacion_correo_electronico')->nullable();
            $table->string('correo_notificaciones_judiciales')->nullable();
            $table->string('en_liquidacion_rues')->nullable();
            $table->string('tipo_liquidacion_rues')->nullable();
            $table->string('ecomun')->nullable();
            $table->string('cafetera')->nullable();
            $table->string('ano_renovacion_matricula')->nullable();
            $table->date('fecha_renovacion_matricula')->nullable();
            $table->string('certificado_rues')->nullable();
            $table->string('codigos_actividades_financieras')->nullable();
            $table->string('representate_legal')->nullable();
            $table->string('correo_representate_legal')->nullable();
            $table->string('telefono_representate_legal');
            $table->string('tipo_revisor_fiscal');
            $table->string('razon_social_revision_fiscal');
            $table->string('nombre_revisor_fiscal');
            $table->string('direccion_revisor_fiscal');
            $table->string('telefono_revisor_fiscal');
            $table->string('correo_revisor_fiscal');
            $table->string('usuario_creacion');
            $table->string('observaciones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maestro_entidades');
    }
};
