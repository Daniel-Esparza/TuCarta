<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_item_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_item_id')->constrained('pedido_items');
            $table->foreignId('negocio_id')->constrained('negocios');
            $table->foreignId('opcion_id')->nullable()->constrained('opciones')->nullOnDelete();
            $table->string('nombre_grupo', 100);
            $table->string('nombre_opcion', 100);
            $table->decimal('incremento_precio', 10, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('pedido_item_id', 'idx_pedido_item_opciones_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_item_opciones');
    }
};