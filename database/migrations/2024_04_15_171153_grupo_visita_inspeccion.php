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
        Schema::create('grupo_vista_inspeccion', function (Blueprint $table) {
            $table->id();
            $table->string('id_informe');
            $table->string('id_usuario');
            $table->string('rol');
            $table->string('estado');
            $table->longText('enlace_hallazgos')->nullable();
            $table->string('informe_firmado')->nullable();
            $table->string('usuario_creacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_vista_inspeccion');
    }
};
