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
        Schema::create('asuntos_especiales', function (Blueprint $table) {
            $table->id();
            $table->string('entidad');
            $table->string('etapa');
            $table->string('estado_etapa');
            $table->string('tipo_toma');
            $table->text('usuarios_actuales')->nullable();
            $table->date('fecha_inicio_toma')->nullable();
            $table->text('carpeta_drive')->nullable();
            $table->string('ciclo_memorando')->nullable();
            $table->string('usuario_creacion');
            $table->string('memorando_traslado')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asuntos_especiales');
    }
};
