<?php

namespace Tests\Feature;

use Database\Seeders\NucleoSeeder;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigracionesNucleoTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tablas_coinciden_con_el_diagrama_del_sprint_2(): void
    {
        $this->assertSame([], array_diff([
            'id', 'nombre', 'subdominio', 'telefono_whatsapp', 'correo_contacto', 'descripcion',
            'logo_path', 'direccion', 'categoria', 'zona_horaria', 'cerrado_temporal',
            'personalizacion', 'estado', 'created_at', 'updated_at',
        ], Schema::getColumnListing('negocios')));

        $this->assertSame([], array_diff([
            'id', 'negocio_id', 'rol', 'nombre', 'apellidos', 'email', 'correo_contacto', 'password',
            'estado', 'email_verified_at', 'telefono_whatsapp', 'last_login', 'created_at', 'updated_at',
        ], Schema::getColumnListing('usuarios')));

        $this->assertSame([], array_diff([
            'id', 'clave', 'descripcion', 'tipo_usuario', 'created_at', 'updated_at',
        ], Schema::getColumnListing('permisos')));

        $this->assertSame([], array_diff([
            'id', 'usuario_id', 'permiso_id', 'asignado_por', 'negocio_id', 'created_at', 'updated_at',
        ], Schema::getColumnListing('usuario_permisos')));

        $this->assertFalse(Schema::hasTable('users'));
    }

    public function test_los_valores_por_defecto_son_los_del_diagrama(): void
    {
        $negocio = Negocio::create([
            'nombre' => 'Prueba',
            'subdominio' => 'prueba',
            'telefono_whatsapp' => '+524441234567',
            'correo_contacto' => 'contacto@prueba.mx',
            'direccion' => 'Centro',
            'categoria' => 'Tacos',
        ])->fresh();

        $this->assertSame('pendiente', $negocio->estado);
        $this->assertSame('America/Mexico_City', $negocio->zona_horaria);
        $this->assertFalse($negocio->cerrado_temporal);

        $empleado = Usuario::create([
            'negocio_id' => $negocio->id,
            'rol' => Rol::Negocio,
            'nombre' => 'Pedro',
            'apellidos' => 'López',
            'email' => 'pedro@prueba.tucarta.local',
            'telefono_whatsapp' => '+524447654321',
        ])->fresh();

        $this->assertSame('pendiente_activacion', $empleado->estado);
        $this->assertNull($empleado->password);
    }

    public function test_el_seeder_crea_administrador_moderador_y_dos_negocios_con_dueno(): void
    {
        $this->seed(NucleoSeeder::class);

        $administradores = Usuario::where('rol', Rol::Administrador)->get();
        $this->assertCount(2, $administradores);
        $this->assertTrue($administradores->every(fn ($u) => $u->negocio_id === null));

        $moderador = Usuario::where('email', 'moderador@tucarta.local')->firstOrFail();
        $this->assertSame(Rol::Administrador, $moderador->rol);
        $this->assertSame(0, $moderador->permisos()->count());

        $this->assertSame(2, Negocio::count());
        foreach (Negocio::all() as $negocio) {
            $dueno = $negocio->usuarios()->sole();
            $this->assertSame(Rol::Negocio, $dueno->rol);
            $this->assertStringEndsWith('@'.$negocio->subdominio.'.tucarta.local', $dueno->email);
        }

        $this->assertTrue(Hash::check('password', Usuario::where('email', 'admin@tucarta.local')->value('password')));
    }

    public function test_el_subdominio_no_se_puede_repetir(): void
    {
        Negocio::factory()->create(['subdominio' => 'repetido']);

        $this->expectException(QueryException::class);
        Negocio::factory()->create(['subdominio' => 'repetido']);
    }

    public function test_el_correo_de_usuario_no_se_puede_repetir(): void
    {
        Usuario::factory()->create(['email' => 'repetido@tucarta.local']);

        $this->expectException(QueryException::class);
        Usuario::factory()->create(['email' => 'repetido@tucarta.local']);
    }

    public function test_no_se_puede_asignar_dos_veces_el_mismo_permiso_en_el_mismo_negocio(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $permiso = Permiso::create(['clave' => 'pedidos.ver', 'tipo_usuario' => 'negocio']);

        UsuarioPermiso::create(['usuario_id' => $dueno->id, 'permiso_id' => $permiso->id, 'negocio_id' => $dueno->negocio_id]);

        $this->expectException(QueryException::class);
        UsuarioPermiso::create(['usuario_id' => $dueno->id, 'permiso_id' => $permiso->id, 'negocio_id' => $dueno->negocio_id]);
    }

    public function test_un_usuario_no_puede_apuntar_a_un_negocio_inexistente(): void
    {
        $this->expectException(QueryException::class);
        Usuario::factory()->create(['rol' => Rol::Negocio, 'negocio_id' => 999999]);
    }

    public function test_las_migraciones_se_pueden_revertir(): void
    {
        Artisan::call('migrate:reset');

        $this->assertFalse(Schema::hasTable('usuario_permisos'));
        $this->assertFalse(Schema::hasTable('permisos'));
        $this->assertFalse(Schema::hasTable('usuarios'));
        $this->assertFalse(Schema::hasTable('negocios'));

        Artisan::call('migrate');

        $this->assertTrue(Schema::hasTable('usuarios'));
    }
}
