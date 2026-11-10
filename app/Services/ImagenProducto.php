<?php

namespace App\Services;

use App\Contracts\ServicioImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagenProducto implements ServicioImagen
{
    protected string $disco;

    public function __construct(string $disco = 'public')
    {
        $this->disco = $disco;
    }

    public function guardar(UploadedFile $archivo, string $directorio = 'productos'): string
    {
        $nombreBase = Str::uuid()->toString();
        $nombreArchivo = "{$directorio}/{$nombreBase}.webp";
        
        $rutaAbsoluta = Storage::disk($this->disco)->path($nombreArchivo);
        
        // Crear el directorio si no existe
        $directorioAbsoluto = dirname($rutaAbsoluta);
        if (!is_dir($directorioAbsoluto)) {
            mkdir($directorioAbsoluto, 0755, true);
        }

        // Cargar imagen con GD
        $origen = match ($archivo->getClientMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($archivo->getRealPath()),
            'image/png' => imagecreatefrompng($archivo->getRealPath()),
            'image/webp' => imagecreatefromwebp($archivo->getRealPath()),
            default => imagecreatefromstring(file_get_contents($archivo->getRealPath())),
        };

        if (!$origen) {
            throw new \InvalidArgumentException("No se pudo procesar la imagen proporcionada.");
        }

        // Mantener transparencia si es PNG/WebP
        imagealphablending($origen, true);
        imagesavealpha($origen, true);

        // Convertir y guardar en formato WebP con calidad 80
        imagewebp($origen, $rutaAbsoluta, 80);
        imagedestroy($origen);

        return $nombreArchivo;
    }

    public function eliminar(?string $ruta): bool
    {
        if (!$ruta) {
            return false;
        }

        if (Storage::disk($this->disco)->exists($ruta)) {
            return Storage::disk($this->disco)->delete($ruta);
        }

        return false;
    }
}
