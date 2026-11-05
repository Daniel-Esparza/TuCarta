<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Sprint 6: motivo del rechazo (US-26b) y datos que el auto-registro
    // (US-26a) todavía no pide. Los estados válidos están en App\Enums\EstadoNegocio.
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->text('motivo_rechazo')->nullable()->after('estado');
            $table->string('direccion')->nullable()->change();
            $table->string('categoria')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('negocios')->whereNull('direccion')->update(['direccion' => '']);
        DB::table('negocios')->whereNull('categoria')->update(['categoria' => '']);

        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('motivo_rechazo');
            $table->string('direccion')->nullable(false)->change();
            $table->string('categoria')->nullable(false)->change();
        });
    }
};
