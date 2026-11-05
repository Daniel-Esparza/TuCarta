<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreTemporalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Las paginas de error y las vistas cargan el layout con @vite; en pruebas no hace falta compilarlo.
        $this->withoutVite();
    }

    /** Ruta de prueba protegida por el middleware (todavia no existe el envio real de pedidos). */
    private function urlPedido(Negocio $negocio): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/prueba/enviar-pedido';
    }

    /** Horario de 24 horas todos los dias, para que el horario nunca sea la causa del cierre. */
    private function abiertoTodoElDia(Negocio $negocio): void
    {
        foreach (range(1, 7) as $dia) {
            $negocio->horarios()->create([
                'dia_semana' => $dia,
                'hora_apertura' => '00:00:00',
                'hora_cierre' => Horario::FIN_DE_DIA,
            ]);
        }
    }

    public function test_un_negocio_abierto_y_dentro_de_horario_recibe_el_pedido(): void
    {
        $negocio = Negocio::factory()->create();
        $this->abiertoTodoElDia($negocio);

        $this->get($this->urlPedido($negocio))
            ->assertOk()
            ->assertSee('Pedido enviado');
    }

    public function test_con_cierre_temporal_el_servidor_rechaza_aunque_el_horario_este_abierto(): void
    {
        $negocio = Negocio::factory()->create(['cerrado_temporal' => true]);
        $this->abiertoTodoElDia($negocio);

        // Confirma que el horario si esta abierto: lo unico que bloquea es el cierre manual.
        $this->assertTrue($negocio->dentroDeHorario());

        $this->get($this->urlPedido($negocio))->assertForbidden();
    }

    public function test_fuera_de_horario_el_servidor_rechaza_el_pedido(): void
    {
        // Sin ningun horario configurado, el negocio nunca esta dentro de horario.
        $negocio = Negocio::factory()->create();

        $this->get($this->urlPedido($negocio))->assertForbidden();
    }

    public function test_al_reabrir_el_negocio_vuelve_a_recibir_pedidos(): void
    {
        $negocio = Negocio::factory()->create(['cerrado_temporal' => true]);
        $this->abiertoTodoElDia($negocio);

        $this->get($this->urlPedido($negocio))->assertForbidden();

        $negocio->update(['cerrado_temporal' => false]);

        $this->get($this->urlPedido($negocio))->assertOk();
    }

    public function test_un_negocio_pendiente_o_inexistente_responde_404(): void
    {
        $pendiente = Negocio::factory()->pendiente()->create();
        $this->abiertoTodoElDia($pendiente);

        $this->get($this->urlPedido($pendiente))->assertNotFound();

        $this->get('http://no-existe.'.config('tucarta.dominio').'/prueba/enviar-pedido')->assertNotFound();
    }
}
