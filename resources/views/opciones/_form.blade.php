@php($o = $opcion ?? null)

<form method="POST" action="{{ $accion }}" class="flex flex-col gap-4">
    @csrf
    @method($metodo)

    <x-campo :id="'opcion-nombre-'.($o?->id ?? 'nueva')" nombre="nombre" etiqueta="Nombre"
             placeholder="Queso extra" :valor="$o?->nombre" maxlength="100" required />

    <x-campo :id="'opcion-precio-'.($o?->id ?? 'nueva')" nombre="incremento_precio" etiqueta="Precio extra"
             tipo="number" step="0.01" min="0" :valor="$o?->incremento_precio ?? 0"
             ayuda="En pesos. Puede ser 0." required />

    <x-button type="submit">{{ $texto }}</x-button>
</form>
