@props(['estado' => 'activo'])

@php
    // Insignia del estado de un negocio. Solo usa los colores de la guia (tokens de app.css).
    // El texto siempre se muestra: el color nunca es la unica forma de distinguir el estado.
    $estados = [
        'activo'     => ['Activo',     'bg-brand-teal/10 text-brand-teal border-brand-teal/40'],
        'pendiente'  => ['Pendiente',  'bg-brand-terracotta/10 text-brand-terracottaHover border-brand-terracotta/50'],
        'suspendido' => ['Suspendido', 'bg-brand-dark/10 text-brand-dark border-brand-dark/40'],
        'rechazado'  => ['Rechazado',  'bg-brand-dark text-white border-brand-dark'],
    ];

    $clave = strtolower((string) $estado);
    [$texto, $colores] = $estados[$clave] ?? [ucfirst((string) $estado), 'bg-brand-beigeInput text-brand-dark border-brand-beigeBorder'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap $colores"]) }}>
    {{ $texto }}
</span>
