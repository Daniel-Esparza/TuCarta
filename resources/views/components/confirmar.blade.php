@props([
    'accion',
    'metodo' => 'POST',
    'etiqueta' => 'Continuar',
    'titulo' => 'Confirmar accion',
    'mensaje' => null,
    'confirmar' => 'Confirmar',
    'variante' => 'outline',
])

{{-- Boton que abre una ventana de confirmacion antes de enviar el formulario.
     Usa el <dialog> nativo del navegador: no depende de Alpine y se cierra con Esc. --}}
@php
    $dialogo = 'confirmar-' . \Illuminate\Support\Str::random(8);
    $abrir = "document.getElementById('{$dialogo}').showModal()";
@endphp

<x-button :variant="$variante" type="button" :onclick="$abrir" {{ $attributes }}>
    {{ $etiqueta }}
</x-button>

<dialog id="{{ $dialogo }}" aria-labelledby="{{ $dialogo }}-titulo"
        class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-lg border border-brand-beigeBorder bg-brand-beigeCard p-0 text-brand-dark shadow-xl backdrop:bg-brand-dark/60">
    <form method="POST" action="{{ $accion }}" class="space-y-3 p-5">
        @csrf
        @if (strtoupper($metodo) !== 'POST')
            @method($metodo)
        @endif

        <h2 id="{{ $dialogo }}-titulo" class="text-lg font-bold text-brand-dark">{{ $titulo }}</h2>

        @if ($mensaje)
            <p class="text-sm text-brand-dark/80">{{ $mensaje }}</p>
        @endif

        {{ $slot }}

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-button variant="outline" type="button" onclick="this.closest('dialog').close()">Cancelar</x-button>
            <x-button variant="primary" type="submit">{{ $confirmar }}</x-button>
        </div>
    </form>
</dialog>
