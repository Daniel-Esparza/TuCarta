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
        Schema::create('grupos_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->boolean('es_multiple')->default(false);
            $table->boolean('es_obligatorio')->default(false);
            $table->integer('min_selecciones')->default(0);
            $table->integer('max_selecciones')->default(1);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['item_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_opciones');
    }
};
