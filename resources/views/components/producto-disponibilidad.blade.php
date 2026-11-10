@props(['producto'])

{{-- Con el permiso marcar_agotado es un botón que guarda sin recargar. Sin él, solo muestra el estado. --}}
@if (auth()->user()?->tienePermiso(App\Enums\ClavePermiso::MarcarAgotado))
    <span x-data="disponibilidad(@js(route('productos.disponibilidad', $producto)), @js((bool) $producto->disponible_hoy), @js(csrf_token()))"
          class="inline-flex flex-col items-start gap-1">
        <button type="button" @click="alternar" :disabled="enviando" :aria-pressed="(!disponible).toString()"
                :class="disponible ? 'bg-brand-teal hover:bg-brand-tealHover' : 'bg-brand-dark/60 hover:bg-brand-dark/80'"
                class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-terracotta focus:ring-offset-2 disabled:opacity-50 {{ $producto->disponible_hoy ? 'bg-brand-teal' : 'bg-brand-dark/60' }}"
                x-text="disponible ? 'Disponible' : 'Agotado'">{{ $producto->disponible_hoy ? 'Disponible' : 'Agotado' }}</button>
        <span x-show="fallo" style="display: none" role="alert" class="text-xs font-medium text-brand-terracottaHover">No se pudo guardar. Intenta de nuevo.</span>
    </span>
@else
    <span class="text-sm font-medium">{{ $producto->disponible_hoy ? 'Disponible' : 'Agotado' }}</span>
@endif
