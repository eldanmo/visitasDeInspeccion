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
        Schema::create('historial_informes', function (Blueprint $table) {
            $table->id();
            $table->string('id_informe');
            $table->string('accion');
            $table->string('etapa');
            $table->string('estado');
            $table->date('fecha_creacion');
            $table->text('observaciones')->nullable();
            $table->string('estado_etapa')->nullable();
            $table->string('usuario_asignado')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('conteo_dias')->nullable();
            $table->date('fecha_limite_etapa')->nullable();
            $table->string('usuario_creacion');
            $table->string('proceso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_informes');
    }
};
