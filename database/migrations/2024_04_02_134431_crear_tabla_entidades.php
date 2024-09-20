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
        Schema::create('entidades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_entidad')->unique();
            $table->string('nit')->unique();
            $table->string('razon_social');
            $table->string('sigla')->nullable();
            $table->string('nivel_supervision');
            $table->string('naturaleza_organizacion');
            $table->string('tipo_organizacion');
            $table->string('categoria')->nullable();
            $table->string('grupo_niif');
            $table->string('incluye_sarlaft')->nullable();
            $table->string('ciudad_municipio');
            $table->string('departamento');
            $table->string('direccion');
            $table->string('numero_asociados');
            $table->string('numero_empleados');
            $table->string('total_activos');
            $table->string('total_pasivos');
            $table->string('total_patrimonio');
            $table->string('total_ingresos');
            $table->timestamp('fecha_ultimo_reporte');
            $table->timestamp('fecha_corte_visita');

            $table->string('representate_legal');
            $table->string('correo_representate_legal');
            $table->string('telefono_representate_legal');
            $table->string('tipo_revisor_fiscal');
            $table->string('razon_social_revision_fiscal')->nullable();
            $table->string('nombre_revisor_fiscal');
            $table->string('direccion_revisor_fiscal');
            $table->string('telefono_revisor_fiscal');
            $table->string('correo_revisor_fiscal');

            $table->string('usuario_creacion');
            $table->string('estado');
            $table->string('motivo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entidades');
    }
};
