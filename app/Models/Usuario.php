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

    public function esAdministrador(): bool
    {
        return $this->rol === Rol::Administrador;
    }

    public function esNegocio(): bool
    {
        return $this->rol === Rol::Negocio;
    }
}
