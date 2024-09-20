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
        Schema::create('anexos_registro', function (Blueprint $table) {
            $table->id();
            $table->text('nombre')->nullable();
            $table->text('ruta');

            $table->text('id_entidad');
            $table->text('proceso')->nullable();
            $table->text('sub_proceso')->nullable();
            $table->text('id_sub_proceso')->nullable();
            $table->text('tipo_anexo')->nullable();
            $table->text('id_tipo_anexo')->nullable();
            $table->text('estado');
            
            $table->string('usuario_creacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anexos_registro');
    }
};
