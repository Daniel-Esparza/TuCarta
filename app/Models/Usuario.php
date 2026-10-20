<?php

namespace App\Models;

use App\Enums\ClavePermiso;
use App\Enums\Rol;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

#[Fillable([
    'negocio_id',
    'rol',
    'nombre',
    'apellidos',
    'email',
    'correo_contacto',
    'password',
    'estado',
    'email_verified_at',
    'telefono_whatsapp',
])]
#[Hidden(['password'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    // La tabla no tiene columna remember_token.
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return [
            'rol' => Rol::class,
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(UsuarioPermiso::class);
    }

    /**
     * Un permiso con negocio_id nulo es global y aplica a todos los negocios.
     * Si no se indica negocio, se usa el del propio usuario.
     */
    public function tienePermiso(ClavePermiso $clave, Negocio|int|null $negocio = null): bool
    {
        $negocioId = $negocio instanceof Negocio ? $negocio->id : ($negocio ?? $this->negocio_id);

        return $this->asignacionesDe($clave)
            ->where(function ($consulta) use ($negocioId) {
                $consulta->whereNull('usuario_permisos.negocio_id');

                if ($negocioId !== null) {
                    $consulta->orWhere('usuario_permisos.negocio_id', $negocioId);
                }
            })
            ->exists();
    }

    public function tienePermisoGlobal(ClavePermiso $clave): bool
    {
        return $this->asignacionesDe($clave)->whereNull('usuario_permisos.negocio_id')->exists();
    }

    // IDs de los negocios sobre los que tiene el permiso de forma específica.
    public function negociosConPermiso(ClavePermiso $clave): Collection
    {
        return $this->asignacionesDe($clave)
            ->whereNotNull('usuario_permisos.negocio_id')
            ->pluck('usuario_permisos.negocio_id');
    }

    public function otorgarPermiso(ClavePermiso $clave, ?Negocio $negocio = null, ?Usuario $asignadoPor = null): UsuarioPermiso
    {
        // Un empleado nunca puede acumular permisos del catálogo de administrador.
        if ($clave->tipo() !== $this->rol) {
            throw new InvalidArgumentException(
                "El permiso {$clave->value} no pertenece al catálogo de {$this->rol->value}."
            );
        }

        return $this->permisos()->firstOrCreate(
            ['permiso_id' => Permiso::deClave($clave)->id, 'negocio_id' => $negocio?->id],
            ['asignado_por' => $asignadoPor?->id],
        );
    }

    public function quitarPermiso(ClavePermiso $clave, ?Negocio $negocio = null): void
    {
        $this->permisos()
            ->where('permiso_id', Permiso::deClave($clave)->id)
            ->where('negocio_id', $negocio?->id)
            ->delete();
    }

    public function otorgarPermisosDeDueno(?Usuario $asignadoPor = null): void
    {
        foreach (ClavePermiso::delCatalogo(Rol::Negocio) as $clave) {
            $this->otorgarPermiso($clave, $this->negocio, $asignadoPor);
        }
    }

    private function asignacionesDe(ClavePermiso $clave): HasMany
    {
        return $this->permisos()
            ->join('permisos', 'permisos.id', '=', 'usuario_permisos.permiso_id')
            ->where('permisos.clave', $clave->value);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === Rol::Administrador;
    }

    public function esNegocio(): bool
    {
        return $this->rol === Rol::Negocio;
    }
}
