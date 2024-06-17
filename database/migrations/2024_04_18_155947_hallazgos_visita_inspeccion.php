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
        Schema::create('hallazgos_visita_inspeccion', function (Blueprint $table) {
            $table->id();
            $table->string('id_informe');
            $table->string('id_usuario');
            $table->string('enlace_hallazgos');
            $table->string('estado');
            $table->string('usuario_creacion');
            $table->timestamps();
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hallazgos_visita_inspeccion');
    }
};
