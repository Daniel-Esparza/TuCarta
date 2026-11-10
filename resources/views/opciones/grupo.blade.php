<x-layouts.app>
    <h1>Grupo: {{ $grupo->nombre }}</h1>
<div x-data="{ disp: {{ $grupo->item->disponible_hoy ? 'true' : 'false' }} }" style="margin:16px 0">
    <p>Producto: {{ $grupo->item->nombre }}</p>
    <button type="button"
        style="min-height:44px;min-width:44px;padding:8px 16px;color:white"
        :style="disp ? 'background:#16a34a' : 'background:#6b7280'"
        @click="fetch('{{ route('productos.disponibilidad', $grupo->item) }}', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => disp = d.disponible)"
        x-text="disp ? 'Disponible' : 'Agotado'">
    </button>
</div>
    @include('opciones._form', ['grupo' => $grupo])
</x-layouts.app>

