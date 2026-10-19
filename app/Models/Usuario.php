<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
     * ¿Este usuario tiene el permiso indicado?
     * US-23 y US-27 comparten esta lógica.
     */
    public function tienePermiso(string $clave, ?int $negocioId = null): bool
    {
        $negocioId ??= $this->negocio_id;

        return $this->permisos()
            ->join('permisos', 'permisos.id', '=', 'usuario_permisos.permiso_id')
            ->where('permisos.clave', $clave)
            ->where(function ($q) use ($negocioId) {
                $q->whereNull('usuario_permisos.negocio_id'); // permiso global
                if ($negocioId !== null) {
                    $q->orWhere('usuario_permisos.negocio_id', $negocioId);
                }
            })
            ->exists();
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
