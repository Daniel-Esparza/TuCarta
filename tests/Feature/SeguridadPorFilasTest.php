<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Support\SeguridadPorFilas;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class SeguridadPorFilasTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $tacos;

    private Negocio $sushi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tacos = Negocio::factory()->create(['subdominio' => 'tacos-juan']);
        $this->sushi = Negocio::factory()->create(['subdominio' => 'sushi-ana']);

        Categoria::create(['negocio_id' => $this->tacos->id, 'nombre' => 'Tacos']);
        Categoria::create(['negocio_id' => $this->tacos->id, 'nombre' => 'Bebidas']);
        Categoria::create(['negocio_id' => $this->sushi->id, 'nombre' => 'Rollos']);
    }

    // Consulta directa a la base, sin Eloquent ni su filtro.
    private function categoriasVisibles(): array
    {
        return DB::table('categorias')->orderBy('nombre')->pluck('nombre')->all();
    }

    public function test_el_rol_de_la_aplicacion_no_se_salta_el_rls(): void
    {
        $rol = DB::selectOne('select rolsuper, rolbypassrls, rolcanlogin from pg_roles where rolname = ?', [SeguridadPorFilas::ROL]);

        $this->assertFalse($rol->rolsuper);
        $this->assertFalse($rol->rolbypassrls);
        $this->assertFalse($rol->rolcanlogin);
    }

    public function test_las_9_tablas_con_negocio_id_tienen_rls_forzado(): void
    {
        $tablas = DB::table('pg_class')
            ->where('relkind', 'r')
            ->where('relrowsecurity', true)
            ->where('relforcerowsecurity', true)
            ->orderBy('relname')
            ->pluck('relname')
            ->all();

        $this->assertSame([
            'categorias', 'grupos_opciones', 'horarios', 'items', 'opciones',
            'pedido_estado_historial', 'pedido_item_opciones', 'pedido_items', 'pedidos',
        ], $tablas);
    }

    public function test_la_base_solo_entrega_las_filas_del_negocio_fijado(): void
    {
        SeguridadPorFilas::usarRolDeAplicacion();
        SeguridadPorFilas::fijarNegocio($this->tacos->id);

        $this->assertSame(['Bebidas', 'Tacos'], $this->categoriasVisibles());
    }

    public function test_sin_negocio_fijado_la_base_no_entrega_nada(): void
    {
        SeguridadPorFilas::usarRolDeAplicacion();

        $this->assertSame([], $this->categoriasVisibles());
    }

    public function test_el_administrador_puede_ver_todos_los_negocios(): void
    {
        SeguridadPorFilas::usarRolDeAplicacion();
        SeguridadPorFilas::verTodo();

        $this->assertCount(3, $this->categoriasVisibles());
    }

    public function test_la_base_no_deja_insertar_filas_de_otro_negocio(): void
    {
        SeguridadPorFilas::usarRolDeAplicacion();
        SeguridadPorFilas::fijarNegocio($this->tacos->id);

        $this->expectException(QueryException::class);
        DB::table('categorias')->insert(['negocio_id' => $this->sushi->id, 'nombre' => 'Intruso']);
    }

    public function test_la_base_no_deja_modificar_ni_borrar_filas_de_otro_negocio(): void
    {
        SeguridadPorFilas::usarRolDeAplicacion();
        SeguridadPorFilas::fijarNegocio($this->tacos->id);

        $this->assertSame(0, DB::table('categorias')->where('negocio_id', $this->sushi->id)->update(['nombre' => 'Cambiado']));
        $this->assertSame(0, DB::table('categorias')->where('negocio_id', $this->sushi->id)->delete());

        SeguridadPorFilas::verTodo();
        $this->assertContains('Rollos', $this->categoriasVisibles());
    }

    public function test_las_tablas_del_nucleo_quedan_fuera_de_rls(): void
    {
        SeguridadPorFilas::usarRolDeAplicacion();

        $this->assertSame(2, DB::table('negocios')->count());
    }

    public function test_rechaza_nombres_de_tabla_raros(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SeguridadPorFilas::activar('pedidos; drop table negocios');
    }
}
