{{--
    Campo de formulario de TuCarta.
    Uso:
        <x-input name="nombre" label="Nombre" placeholder="Tu nombre" />
        <x-input name="telefono" label="WhatsApp" type="tel" />
--}}
@props(['name', 'label' => null])

<label for="{{ $name }}" class="block">
    @if ($label)
        <span class="mb-1.5 block text-xs font-semibold text-brand-dark">{{ $label }}</span>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->merge([
            'type' => 'text',
            'class' => 'w-full rounded-xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3 text-sm text-brand-dark placeholder:text-brand-dark/40 focus:border-brand-teal focus:outline-none focus:ring-2 focus:ring-brand-teal/20',
        ]) }}
    >
</label>
