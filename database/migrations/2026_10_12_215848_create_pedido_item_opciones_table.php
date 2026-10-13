<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_estado_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos');
            $table->foreignId('negocio_id')->constrained('negocios');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('motivo', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pedido_id', 'created_at'], 'idx_historial_pedido_fecha');
        });

        DB::statement('ALTER TABLE pedido_estado_historial ADD COLUMN estado_anterior estado_pedido NULL');
        DB::statement('ALTER TABLE pedido_estado_historial ADD COLUMN estado_nuevo estado_pedido NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_estado_historial');
    }
};
