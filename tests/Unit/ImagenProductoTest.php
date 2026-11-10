<?php

namespace Tests\Unit;

use App\Services\ImagenProducto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class ImagenProductoTest extends TestCase
{
    private ImagenProducto $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->servicio = new ImagenProducto('public');
    }

    private function datosDe(string $ruta): array
    {
        return getimagesizefromstring(Storage::disk('public')->get($ruta));
    }

    // Foto de muestra con mucho detalle, para que el peso sea parecido al de una real.
    private function fotoConDetalle(int $ancho, int $alto): UploadedFile
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        mt_srand(8);

        for ($i = 0; $i < 4000; $i++) {
            $x = mt_rand(0, $ancho);
            $y = mt_rand(0, $alto);
            $color = imagecolorallocate($imagen, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledrectangle($imagen, $x, $y, $x + mt_rand(4, 90), $y + mt_rand(4, 90), $color);
        }

        ob_start();
        imagejpeg($imagen, null, 95);

        return UploadedFile::fake()->createWithContent('foto.jpg', (string) ob_get_clean());
    }

    public function test_una_imagen_grande_queda_en_1200_px_y_en_webp(): void
    {
        $rutas = $this->servicio->guardar(UploadedFile::fake()->image('producto.jpg', 2400, 1600), 7);

        [$ancho, $alto, , ] = $this->datosDe($rutas['imagen']);

        $this->assertSame(1200, $ancho);
        $this->assertSame(800, $alto);
        $this->assertSame('image/webp', $this->datosDe($rutas['imagen'])['mime']);
    }

    public function test_una_imagen_chica_no_se_agranda(): void
    {
        $rutas = $this->servicio->guardar(UploadedFile::fake()->image('producto.png', 300, 200), 7);

        $this->assertSame(300, $this->datosDe($rutas['imagen'])[0]);
        $this->assertSame(300, $this->datosDe($rutas['miniatura'])[0]);
    }

    public function test_genera_la_miniatura_de_400_px(): void
    {
        $rutas = $this->servicio->guardar(UploadedFile::fake()->image('producto.jpg', 1600, 1200), 7);

        $this->assertSame(400, $this->datosDe($rutas['miniatura'])[0]);
        $this->assertSame('image/webp', $this->datosDe($rutas['miniatura'])['mime']);
    }

    public function test_el_archivo_final_pesa_200_kb_o_menos(): void
    {
        $archivo = $this->fotoConDetalle(3000, 2000);
        $this->assertGreaterThan(200 * 1024, $archivo->getSize());

        $rutas = $this->servicio->guardar($archivo, 7);

        $this->assertLessThanOrEqual(200 * 1024, Storage::disk('public')->size($rutas['imagen']));
    }

    public function test_cada_negocio_guarda_en_su_carpeta_con_nombre_unico(): void
    {
        $archivo = UploadedFile::fake()->image('producto.jpg', 600, 600);

        $uno = $this->servicio->guardar($archivo, 7);
        $otro = $this->servicio->guardar($archivo, 7);
        $ajeno = $this->servicio->guardar($archivo, 8);

        $this->assertStringStartsWith('negocios/7/productos/', $uno['imagen']);
        $this->assertStringStartsWith('negocios/7/productos/', $uno['miniatura']);
        $this->assertStringStartsWith('negocios/8/productos/', $ajeno['imagen']);
        $this->assertNotSame($uno['imagen'], $otro['imagen']);
        $this->assertSame([], Storage::disk('public')->files('negocios/8/productos/otra'));
    }

    public function test_eliminar_borra_la_imagen_y_la_miniatura(): void
    {
        $rutas = $this->servicio->guardar(UploadedFile::fake()->image('producto.png', 400, 400), 7);

        $this->servicio->eliminar($rutas['imagen'], $rutas['miniatura']);

        Storage::disk('public')->assertMissing($rutas['imagen']);
        Storage::disk('public')->assertMissing($rutas['miniatura']);
    }

    public function test_eliminar_con_rutas_nulas_no_hace_nada(): void
    {
        $this->servicio->eliminar(null, null);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_un_archivo_que_no_es_imagen_se_rechaza(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->guardar(UploadedFile::fake()->createWithContent('falsa.jpg', 'esto no es una imagen'), 7);
    }
}
