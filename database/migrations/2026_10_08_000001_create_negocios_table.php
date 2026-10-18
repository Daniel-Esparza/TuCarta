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
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('subdominio')->unique();
            $table->string('telefono_whatsapp');
            $table->string('correo_contacto');
            $table->string('descripcion')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('direccion');
            $table->string('categoria');
            $table->string('zona_horaria')->default('America/Mexico_City');
            $table->boolean('cerrado_temporal')->default(false);
            $table->jsonb('personalizacion')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
