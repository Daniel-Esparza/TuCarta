<?php

namespace App\Services;

use App\Contracts\ServicioImagen;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImagenProducto implements ServicioImagen
{
    private const ANCHO_IMAGEN = 1200;

    private const ANCHO_MINIATURA = 400;

    private const PESO_MAXIMO = 200 * 1024;

    private const CALIDADES = [80, 70, 60, 50, 40];

    private const PIXELES_MAXIMOS = 50_000_000;

    public function __construct(private string $disco = 'public') {}

    public function guardar(UploadedFile $archivo, int $negocioId): array
    {
        $origen = $this->abrir($archivo);

        $nombre = Str::uuid()->toString();
        $carpeta = "negocios/{$negocioId}/productos";
        $rutas = [
            'imagen' => "{$carpeta}/{$nombre}.webp",
            'miniatura' => "{$carpeta}/{$nombre}_miniatura.webp",
        ];

        $this->escribir($origen, self::ANCHO_IMAGEN, $rutas['imagen']);
        $this->escribir($origen, self::ANCHO_MINIATURA, $rutas['miniatura']);

        return $rutas;
    }

    public function eliminar(?string $imagen, ?string $miniatura): void
    {
        $rutas = array_filter([$imagen, $miniatura]);

        if ($rutas !== []) {
            Storage::disk($this->disco)->delete($rutas);
        }
    }

    // Se lee el contenido real del archivo: el tipo que manda el navegador se puede falsificar.
    private function abrir(UploadedFile $archivo): GdImage
    {
        $contenido = (string) file_get_contents($archivo->getRealPath());
        $datos = $contenido === '' ? false : @getimagesizefromstring($contenido);

        if (! $datos || $datos[0] * $datos[1] > self::PIXELES_MAXIMOS) {
            throw new InvalidArgumentException('El archivo no es una imagen válida.');
        }

        $imagen = @imagecreatefromstring($contenido);

        if (! $imagen) {
            throw new InvalidArgumentException('El archivo no es una imagen válida.');
        }

        imagepalettetotruecolor($imagen);

        return $imagen;
    }

    private function escribir(GdImage $origen, int $anchoMaximo, string $ruta): void
    {
        $imagen = $this->reducir($origen, $anchoMaximo);
        $contenido = '';

        foreach (self::CALIDADES as $calidad) {
            ob_start();
            imagewebp($imagen, null, $calidad);
            $contenido = (string) ob_get_clean();

            if (strlen($contenido) <= self::PESO_MAXIMO) {
                break;
            }
        }

        Storage::disk($this->disco)->put($ruta, $contenido);
    }

    private function reducir(GdImage $origen, int $anchoMaximo): GdImage
    {
        $ancho = imagesx($origen);
        $alto = imagesy($origen);

        if ($ancho <= $anchoMaximo) {
            imagesavealpha($origen, true);

            return $origen;
        }

        $altoNuevo = max(1, (int) round($alto * $anchoMaximo / $ancho));

        $destino = imagecreatetruecolor($anchoMaximo, $altoNuevo);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $anchoMaximo, $altoNuevo, $ancho, $alto);

        return $destino;
    }
}
