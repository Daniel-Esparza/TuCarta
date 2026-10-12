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
        Schema::create('usuario_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('permiso_id')->constrained('permisos');
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios');
            // null = el permiso aplica a todos los negocios
            $table->foreignId('negocio_id')->nullable()->constrained('negocios');
            $table->timestamps();

            $table->unique(['usuario_id', 'permiso_id', 'negocio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_permisos');
    }
};
