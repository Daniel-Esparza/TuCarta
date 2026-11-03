<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // US-26b: la dirección web de un negocio rechazado queda libre. El registro se
    // conserva con su motivo, pero ya no aparta el subdominio.
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropUnique(['subdominio']);
        });

        DB::statement("CREATE UNIQUE INDEX negocios_subdominio_unique ON negocios (subdominio) WHERE estado <> 'rechazado'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX negocios_subdominio_unique');

        // Los rechazados pueden repetir dirección: se les pone una propia antes de volver al índice completo.
        DB::statement("UPDATE negocios SET subdominio = subdominio || '-r' || id WHERE estado = 'rechazado'");

        Schema::table('negocios', function (Blueprint $table) {
            $table->unique('subdominio');
        });
    }
};
