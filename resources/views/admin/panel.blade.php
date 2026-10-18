{{--
    US-01: Esqueleto del panel central (pagina protegida).
    Archivo: resources/views/admin/panel.blade.php
    Cami aplica el diseno final. Pendiente: restringirlo al rol administrador cuando llegue US-32.
--}}
<x-layouts.app title="Panel | TuCarta">
    <div class="flex flex-col gap-8 break-words">

        <header>
            <p class="text-xl font-extrabold text-brand-terracotta">TuCarta</p>
        </header>

        <section class="flex flex-col gap-2" aria-labelledby="titulo-panel">
            <h1 id="titulo-panel" class="text-2xl font-extrabold leading-tight">Panel central</h1>
            <p class="text-base text-brand-dark/80">Sesión iniciada como {{ auth()->user()->name }}.</p>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button type="submit" variant="outline">Cerrar sesión</x-button>
        </form>

    </div>
</x-layouts.app>
