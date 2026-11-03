@props(['href', 'titulo'])

{{-- Acceso a una sección del panel. --}}
<a href="{{ $href }}"
   class="flex min-h-[44px] flex-col gap-1 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5 transition-colors duration-200 hover:border-brand-terracotta focus:outline-none focus:ring-2 focus:ring-brand-terracotta focus:ring-offset-2">
    <span class="font-bold">{{ $titulo }}</span>
    <span class="text-sm text-brand-dark/70">{{ $slot }}</span>
</a>
