<?php

use App\Support\SeguridadPorFilas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Rol sin privilegios para las peticiones web. No inicia sesión por sí
    // solo: la conexión cambia a él con SET ROLE.
    public function up(): void
    {
        $rol = SeguridadPorFilas::ROL;

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{$rol}') THEN
                    CREATE ROLE {$rol} NOLOGIN NOSUPERUSER NOBYPASSRLS;
                END IF;
            END
            \$\$
        ");

        DB::statement("GRANT {$rol} TO CURRENT_USER");
        DB::statement("GRANT USAGE ON SCHEMA public TO {$rol}");
        DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO {$rol}");
        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO {$rol}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO {$rol}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO {$rol}");
    }

    public function down(): void
    {
        $rol = SeguridadPorFilas::ROL;

        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM {$rol}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM {$rol}");
        DB::statement("REVOKE ALL ON ALL TABLES IN SCHEMA public FROM {$rol}");
        DB::statement("REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM {$rol}");
        DB::statement("REVOKE USAGE ON SCHEMA public FROM {$rol}");

        // El rol es de todo el servidor (lo comparte la base de pruebas).
        DB::statement("
            DO \$\$
            BEGIN
                DROP ROLE IF EXISTS {$rol};
            EXCEPTION WHEN dependent_objects_still_exist THEN
                NULL;
            END
            \$\$
        ");
    }
};
