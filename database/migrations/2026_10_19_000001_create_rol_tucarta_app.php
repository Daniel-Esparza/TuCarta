<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'tucarta_app') THEN
                        CREATE ROLE tucarta_app NOLOGIN NOSUPERUSER NOBYPASSRLS;
                    END IF;
                END
                $$
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP ROLE IF EXISTS tucarta_app");
        }
    }
};