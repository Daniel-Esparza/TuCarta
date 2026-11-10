<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface ServicioImagen
{
    /**
     * Guarda la imagen y su miniatura en la carpeta del negocio.
     *
     * @return array{imagen: string, miniatura: string}
     *
     * @throws \InvalidArgumentException si el archivo no se puede leer como imagen
     */
    public function guardar(UploadedFile $archivo, int $negocioId): array;

    /**
     * Borra la imagen y la miniatura. Las rutas nulas se ignoran.
     */
    public function eliminar(?string $imagen, ?string $miniatura): void;
}
