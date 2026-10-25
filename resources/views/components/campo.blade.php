@props([
    'nombre',
    'etiqueta',
    'tipo' => 'text',
    'valor' => null,
    'ayuda' => null,
    'disabled' => false,
])

@php
    $id = $attributes->get('id', $nombre);
    // $errors lo comparte Laravel en cada peticion web; el respaldo evita fallar fuera de ellas (por ejemplo en pruebas).
    $errores = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hayError = $errores->has($nombre);
    // Nunca se vuelve a mostrar una contrasena en el campo.
    $contenido = $tipo === 'password' ? null : old($nombre, $valor);
    $describe = collect([$ayuda ? "$id-ayuda" : null, $hayError ? "$id-error" : null])->filter()->implode(' ');
@endphp

<div class="space-y-1">
    <label for="{{ $id }}" class="block text-sm font-semibold text-brand-dark">{{ $etiqueta }}</label>

    <x-input
        :id="$id"
        :name="$nombre"
        :type="$tipo"
        :value="$contenido"
        :disabled="$disabled"
        :aria-invalid="$hayError ? 'true' : 'false'"
        :aria-describedby="$describe !== '' ? $describe : null"
        :class="$hayError ? 'ring-1 ring-brand-terracottaHover' : ''"
        {{ $attributes->except('id') }}
    />

    @if ($ayuda)
        <p id="{{ $id }}-ayuda" class="text-xs text-brand-dark/70">{{ $ayuda }}</p>
    @endif

    @if ($hayError)
        <p id="{{ $id }}-error" role="alert" class="text-sm font-medium text-brand-terracottaHover">{{ $errores->first($nombre) }}</p>
    @endif
</div>
