<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface ServicioImagen
{
    /**
     * Guarda la imagen procesada en formato WebP y retorna la ruta relativa.
     */
    public function guardar(UploadedFile $archivo, string $directorio = 'productos'): string;

    /**
     * Elimina una imagen del almacenamiento si existe.
     */
    public function eliminar(?string $ruta): bool;
}
