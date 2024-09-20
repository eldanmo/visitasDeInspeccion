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
        Schema::create('historico_solicitud_dias_adicionales', function (Blueprint $table) {
            $table->id();
            $table->integer('dias');
            $table->text('observacion')->nullable();
            $table->string('estado');
            $table->string('id_solicitud');
            $table->string('usuario_creacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_solicitud_dias_adicionales');
    }
};
