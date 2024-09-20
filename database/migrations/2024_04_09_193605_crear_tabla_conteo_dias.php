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
        Schema::create('conteo_dias', function (Blueprint $table) {
            $table->id();
            $table->string('id_informe');
            $table->string('etapa');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('dias_habiles')->nullable();
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
        Schema::dropIfExists('conteo_dias');
    }
};
