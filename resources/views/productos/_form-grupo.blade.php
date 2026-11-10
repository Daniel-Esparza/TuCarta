@php
    $g = $grupo ?? null;
    $sufijo = $g?->id ?? 'nuevo';
    $varias = (bool) old('es_multiple', $g?->es_multiple);
@endphp

<form method="POST" action="{{ $accion }}" x-data="{ varias: @js($varias) }" class="flex flex-col gap-4">
    @csrf
    @method($metodo)

    <x-campo :id="'grupo-nombre-'.$sufijo" nombre="nombre" etiqueta="Nombre del grupo" placeholder="Tamaño, Extras, Salsas" :valor="$g?->nombre" maxlength="100" required />

    <fieldset class="flex flex-col gap-1">
        <legend class="text-sm font-semibold">¿Cuántas opciones puede elegir el cliente?</legend>
        <label class="flex min-h-[44px] items-center gap-3 text-sm">
            <input type="radio" name="es_multiple" value="0" class="size-6 accent-brand-terracotta" @change="varias = false" @checked(! $varias)>
            Solo una, como el tamaño
        </label>
        <label class="flex min-h-[44px] items-center gap-3 text-sm">
            <input type="radio" name="es_multiple" value="1" class="size-6 accent-brand-terracotta" @change="varias = true" @checked($varias)>
            Varias, como los ingredientes extra
        </label>
    </fieldset>

    <div class="grid grid-cols-2 gap-3" x-show="varias" @style(['display: none' => ! $varias])>
        <x-campo :id="'grupo-minimo-'.$sufijo" nombre="min_selecciones" etiqueta="Mínimo a elegir" tipo="number" min="0" max="20" :valor="$g?->min_selecciones ?? 0" />
        <x-campo :id="'grupo-maximo-'.$sufijo" nombre="max_selecciones" etiqueta="Máximo a elegir" tipo="number" min="1" max="20" :valor="$g?->max_selecciones ?? 1" />
    </div>

    <label class="flex min-h-[44px] items-center gap-3 text-sm">
        <input type="checkbox" name="es_obligatorio" value="1" class="size-6 accent-brand-terracotta" @checked(old('es_obligatorio', $g?->es_obligatorio))>
        Obligatorio: el cliente debe elegir antes de pedir
    </label>

    <x-button type="submit" variant="terracotta">{{ $texto }}</x-button>
</form>
