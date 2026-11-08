<?php

namespace Tests\Unit;

use App\Services\ImagenProducto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagenProductoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guarda_imagen_y_la_convierte_a_webp(): void
    {
        $servicio = new ImagenProducto('public');
        $file = UploadedFile::fake()->image('producto.jpg', 600, 600);

        $ruta = $servicio->guardar($file, 'productos');

        $this->assertStringEndsWith('.webp', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_elimina_imagen_existente(): void
    {
        $servicio = new ImagenProducto('public');
        $file = UploadedFile::fake()->image('producto.png', 400, 400);

        $ruta = $servicio->guardar($file, 'productos');
        $this->assertTrue($servicio->eliminar($ruta));

        Storage::disk('public')->assertMissing($ruta);
    }

    public function test_retorna_false_al_eliminar_ruta_nula_o_inexistente(): void
    {
        $servicio = new ImagenProducto('public');

        $this->assertFalse($servicio->eliminar(null));
        $this->assertFalse($servicio->eliminar('productos/inexistente.webp'));
    }
}
