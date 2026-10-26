@props([
    'nombre',
    'etiqueta',
    'disabled' => false,
    'mostrarError' => true,
])

{{-- Selector con etiqueta y error por campo, con el mismo aspecto que x-input.
     Las opciones van dentro: <x-select nombre="estado" etiqueta="Estado"><option ...></option></x-select> --}}
@php
    $id = $attributes->get('id', $nombre);
    // $errors lo comparte Laravel en cada peticion web; el respaldo evita fallar fuera de ellas (por ejemplo en pruebas).
    $errores = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hayError = $mostrarError && $errores->has($nombre);
    $clases = 'w-full min-h-[44px] px-3.5 py-2.5 bg-brand-beigeInput text-brand-dark border border-brand-beigeBorder rounded-lg focus:border-brand-terracotta focus:ring-1 focus:ring-brand-terracotta outline-none transition-colors duration-200' . ($hayError ? ' ring-1 ring-brand-terracottaHover' : '');
@endphp

<div class="space-y-1">
    <label for="{{ $id }}" class="block text-sm font-semibold text-brand-dark">{{ $etiqueta }}</label>

    <select id="{{ $id }}" name="{{ $nombre }}" @disabled($disabled) aria-invalid="{{ $hayError ? 'true' : 'false' }}"
        {{ $attributes->except('id')->merge(['class' => $clases]) }}>
        {{ $slot }}
    </select>

    @if ($hayError)
        <p id="{{ $id }}-error" role="alert" class="text-sm font-medium text-brand-terracottaHover">{{ $errores->first($nombre) }}</p>
    @endif
</div>
