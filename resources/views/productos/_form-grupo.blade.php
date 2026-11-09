@php($g = $grupo ?? null)

<form method="POST" action="{{ $accion }}" class="flex flex-col gap-4">
    @csrf
    @method($metodo)

    <x-campo nombre="nombre" etiqueta="Nombre del grupo" placeholder="Tamaño, Extras" :valor="$g?->nombre" required />

    <label class="flex min-h-[44px] items-center gap-3 text-sm font-medium">
        <input type="checkbox" name="es_multiple" value="1" class="h-5 w-5"
               @checked(old('es_multiple', $g?->es_multiple))>
        Puede elegir varias opciones
    </label>

    <label class="flex min-h-[44px] items-center gap-3 text-sm font-medium">
        <input type="checkbox" name="es_obligatorio" value="1" class="h-5 w-5"
               @checked(old('es_obligatorio', $g?->es_obligatorio))>
        Es obligatorio
    </label>

    <div class="grid grid-cols-2 gap-3">
        <label class="flex flex-col gap-1 text-sm font-bold">
            Mínimo
            <input type="number" name="min_selecciones" min="0" max="20"
                   value="{{ old('min_selecciones', $g?->min_selecciones ?? 0) }}"
                   class="min-h-[44px] rounded-lg border border-brand-beigeBorder bg-brand-beigeCard px-4 text-sm font-normal">
        </label>
        <label class="flex flex-col gap-1 text-sm font-bold">
            Máximo
            <input type="number" name="max_selecciones" min="1" max="20"
                   value="{{ old('max_selecciones', $g?->max_selecciones ?? 1) }}"
                   class="min-h-[44px] rounded-lg border border-brand-beigeBorder bg-brand-beigeCard px-4 text-sm font-normal">
        </label>
    </div>

    <x-button type="submit" variant="terracotta">{{ $texto }}</x-button>
</form>
