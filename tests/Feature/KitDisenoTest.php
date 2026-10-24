<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pruebas del kit de diseno del Sprint 6 (US-34).
 * Solo renderizan los componentes Blade: no necesitan base de datos ni sesion.
 */
class KitDisenoTest extends TestCase
{
    public function test_la_insignia_muestra_el_texto_de_cada_estado(): void
    {
        $estados = [
            'activo' => 'Activo',
            'pendiente' => 'Pendiente',
            'suspendido' => 'Suspendido',
            'rechazado' => 'Rechazado',
        ];

        foreach ($estados as $estado => $texto) {
            $this->blade('<x-estado-negocio :estado="$estado" />', ['estado' => $estado])
                ->assertSee($texto);
        }
    }

    public function test_la_insignia_acepta_un_estado_desconocido_sin_romperse(): void
    {
        $this->blade('<x-estado-negocio estado="archivado" />')
            ->assertSee('Archivado');
    }

    public function test_la_confirmacion_envia_con_el_metodo_indicado(): void
    {
        $vista = $this->blade('<x-confirmar accion="/negocios/1/suspender" metodo="PATCH" etiqueta="Suspender" titulo="Suspender este negocio" confirmar="Si, suspender" />');

        $vista->assertSee('<dialog', false);
        $vista->assertSee('action="/negocios/1/suspender"', false);
        $vista->assertSee('name="_token"', false);
        $vista->assertSee('name="_method"', false);
        $vista->assertSee('value="PATCH"', false);
        $vista->assertSee('Suspender este negocio');
        $vista->assertSee('Cancelar');
    }

    public function test_la_confirmacion_con_post_no_agrega_method(): void
    {
        $this->blade('<x-confirmar accion="/negocios" etiqueta="Guardar" />')
            ->assertDontSee('name="_method"', false);
    }

    public function test_el_campo_muestra_el_error_de_su_nombre(): void
    {
        $this->withViewErrors(['direccion_web' => 'Esta direccion ya esta en uso.'])
            ->blade('<x-campo nombre="direccion_web" etiqueta="Direccion web" />')
            ->assertSee('Esta direccion ya esta en uso.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('role="alert"', false);
    }

    public function test_el_campo_no_muestra_el_error_de_otro_campo(): void
    {
        $this->withViewErrors(['correo' => 'Correo no valido.'])
            ->blade('<x-campo nombre="direccion_web" etiqueta="Direccion web" />')
            ->assertDontSee('Correo no valido.')
            ->assertSee('aria-invalid="false"', false);
    }

    public function test_el_campo_sin_errores_no_necesita_el_contenedor_de_errores(): void
    {
        $this->blade('<x-campo nombre="nombre" etiqueta="Nombre" valor="Tacos Juan" />')
            ->assertSee('Nombre')
            ->assertSee('value="Tacos Juan"', false)
            ->assertDontSee('role="alert"', false);
    }

    public function test_el_campo_de_contrasena_nunca_repite_el_valor(): void
    {
        $this->blade('<x-campo nombre="password" etiqueta="Contrasena" tipo="password" valor="secreta123" />')
            ->assertDontSee('secreta123');
    }

    public function test_la_tabla_muestra_encabezados_y_etiquetas_para_celular(): void
    {
        $vista = $this->blade(<<<'BLADE'
            <x-tabla :encabezados="['Nombre', 'Estado']">
                <x-tabla.fila>
                    <x-tabla.celda etiqueta="Nombre">Tacos Juan</x-tabla.celda>
                    <x-tabla.celda etiqueta="Estado"><x-estado-negocio estado="activo" /></x-tabla.celda>
                </x-tabla.fila>
            </x-tabla>
        BLADE);

        $vista->assertSee('scope="col"', false);
        $vista->assertSee('data-etiqueta="Nombre"', false);
        $vista->assertSee('data-etiqueta="Estado"', false);
        $vista->assertSeeInOrder(['Nombre', 'Estado', 'Tacos Juan', 'Activo']);
    }

    public function test_la_alerta_usa_el_rol_correcto_segun_el_tipo(): void
    {
        $this->blade('<x-alerta>Negocio creado.</x-alerta>')
            ->assertSee('role="status"', false)
            ->assertSee('Negocio creado.');

        $this->blade('<x-alerta tipo="error">No se pudo guardar.</x-alerta>')
            ->assertSee('role="alert"', false);
    }
}
