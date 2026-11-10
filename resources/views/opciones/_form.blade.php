@php($o = $opcion ?? null)

<form method="POST" action="{{ $accion }}" class="grid gap-3 md:grid-cols-[1fr_10rem_auto] md:items-end">
    @csrf
    @method($metodo)

    <x-campo :id="'opcion-nombre-'.$sufijo" nombre="nombre" etiqueta="Opción"
             placeholder="Queso, Grande" :valor="$o?->nombre" maxlength="100" required />

    <x-campo :id="'opcion-precio-'.$sufijo" nombre="incremento_precio" etiqueta="Precio extra"
             tipo="number" step="0.01" min="0" :valor="$o?->incremento_precio ?? 0" required />

    <x-button type="submit" variant="outline">{{ $texto }}</x-button>
</form>
