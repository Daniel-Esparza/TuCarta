<x-layouts.app>
    <h1>Perfil del negocio</h1>

    @if (session('estado'))
        <x-alerta>{{ session('estado') }}</x-alerta>
    @endif

    <form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PATCH')

        <x-campo nombre="nombre" etiqueta="Nombre" :valor="$negocio->nombre" />
        <x-campo nombre="descripcion" etiqueta="Descripción" :valor="$negocio->descripcion"
                 ayuda="Una frase breve de lo que preparas." />
        <x-campo nombre="direccion" etiqueta="Dirección" :valor="$negocio->direccion" />
        <x-campo nombre="telefono_whatsapp" etiqueta="WhatsApp" :valor="$negocio->telefono_whatsapp"
                 ayuda="Formato internacional, por ejemplo +524441234567." />

        <div class="space-y-1">
            <span class="block text-sm font-semibold text-brand-dark">Dirección web</span>
            <p class="min-h-[44px] px-3.5 py-2.5 bg-brand-beigeInput/60 text-brand-dark/80 border border-brand-beigeBorder rounded-lg">
                {{ $negocio->subdominio }}.{{ config('tucarta.dominio') }}
            </p>
            <p class="text-xs text-brand-dark/70">Dato de solo lectura: solo el administrador puede cambiarla.</p>
        </div>

        @if ($negocio->logo_path)
            <div class="space-y-1">
                <span class="block text-sm font-semibold text-brand-dark">Logo actual</span>
                <img src="{{ Storage::url($negocio->logo_path) }}" alt="Logo de {{ $negocio->nombre }}"
                     class="h-16 w-16 rounded-full border border-brand-beigeBorder object-cover">
            </div>
        @endif

        <x-campo nombre="logo" etiqueta="Logo" tipo="file" ayuda="JPG, PNG o WebP, máximo 2 MB." />

        <x-button type="submit">Guardar cambios</x-button>
    </form>
</x-layouts.app>