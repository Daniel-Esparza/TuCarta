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
        Schema::create('opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_opcion_id')->constrained('grupos_opciones')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->decimal('incremento_precio', 10, 2)->default(0);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['grupo_opcion_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opciones');
    }
};
