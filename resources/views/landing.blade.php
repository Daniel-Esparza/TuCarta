{{-- US-31: landing page de TuCarta, dentro del layout compartido (US-34). --}}
<x-layouts.app title="TuCarta | Consulta la carta y pide por WhatsApp">
    <x-slot:header>
        <nav aria-label="Navegación principal" class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium">
            <a href="#como-funciona" class="hover:underline">Cómo funciona</a>
            <a href="#para-restaurantes" class="hover:underline">Para restaurantes</a>
            <a href="{{ route('login') }}" class="hover:underline">Iniciar sesión</a>
        </nav>
    </x-slot:header>

    <div id="inicio" class="flex flex-col gap-12">

        {{-- Sección principal --}}
        <section aria-labelledby="titulo-principal" class="flex flex-col gap-4 pt-6">
            <h1 id="titulo-principal" class="text-3xl font-extrabold leading-tight md:text-5xl">
                Consulta la carta de tu restaurante y haz tu pedido por WhatsApp
            </h1>
            <p class="max-w-2xl text-brand-dark/80">
                TuCarta es una plataforma donde puedes encontrar restaurantes, revisar sus cartas
                y armar tu pedido. Al confirmarlo, continúas por WhatsApp para comunicárselo al restaurante.
            </p>
            <div class="flex flex-wrap gap-3">
                <x-button variant="terracotta" :href="route('auto-registro.create')">Registrar mi negocio</x-button>
                <x-button variant="outline" href="#como-funciona">Ver cómo funciona</x-button>
            </div>
        </section>

        {{-- Cómo funciona --}}
        <section id="como-funciona" aria-labelledby="titulo-como-funciona" class="flex flex-col gap-4">
            <h2 id="titulo-como-funciona" class="text-2xl font-extrabold">Cómo funciona</h2>
            <p class="text-brand-dark/80">Pedir con TuCarta toma tres pasos.</p>

            <ol class="grid gap-4 md:grid-cols-3">
                <li class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Paso 1</p>
                    <h3 class="mt-1 font-bold">Encuentra un restaurante</h3>
                    <p class="mt-1 text-sm text-brand-dark/80">Busca el establecimiento que te interesa y consulta su carta.</p>
                </li>
                <li class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Paso 2</p>
                    <h3 class="mt-1 font-bold">Arma tu pedido</h3>
                    <p class="mt-1 text-sm text-brand-dark/80">Selecciona los productos que quieres y organiza tu pedido antes de continuar.</p>
                </li>
                <li class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Paso 3</p>
                    <h3 class="mt-1 font-bold">Continúa por WhatsApp</h3>
                    <p class="mt-1 text-sm text-brand-dark/80">Confirma tu pedido y sigue en WhatsApp para comunicárselo al restaurante.</p>
                </li>
            </ol>
        </section>

        {{-- Para restaurantes --}}
        <section id="para-restaurantes" aria-labelledby="titulo-restaurantes" class="flex flex-col gap-4">
            <h2 id="titulo-restaurantes" class="text-2xl font-extrabold">¿Tienes un restaurante? Presenta tu carta en TuCarta</h2>
            <p class="max-w-2xl text-brand-dark/80">
                TuCarta te ofrece un lugar para mostrar tu menú y facilitar que tus clientes
                te comuniquen sus pedidos por WhatsApp.
            </p>
            <ul class="flex list-disc flex-col gap-2 pl-5 text-brand-dark/80">
                <li>Muestra tu carta en un solo lugar, lista para consultarse desde el celular.</li>
                <li>Tus clientes revisan los productos antes de decidir qué pedir.</li>
                <li>Recibe los pedidos mediante WhatsApp, un canal que tus clientes ya conocen.</li>
            </ul>
            <div>
                <x-button variant="terracotta" :href="route('auto-registro.create')">Registrar mi negocio</x-button>
            </div>
        </section>

        {{-- Invitación final --}}
        <section aria-labelledby="titulo-final" class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-6">
            <h2 id="titulo-final" class="text-2xl font-extrabold">Conoce TuCarta</h2>
            <p class="max-w-2xl text-brand-dark/80">
                Registra tu negocio para presentar tu menú a tus clientes. Si ya tienes cuenta, entra a tu panel.
            </p>
            <div class="flex flex-wrap gap-3">
                <x-button variant="terracotta" :href="route('auto-registro.create')">Registrar mi negocio</x-button>
                <x-button variant="outline" :href="route('login')">Iniciar sesión</x-button>
            </div>
        </section>

        <footer class="flex flex-wrap items-center justify-between gap-4 border-t border-brand-beigeBorder pt-6 text-sm">
            <p class="font-bold">TuCarta</p>
            <nav aria-label="Navegación del pie de página" class="flex flex-wrap gap-4">
                <a href="#inicio" class="hover:underline">Inicio</a>
                <a href="#como-funciona" class="hover:underline">Cómo funciona</a>
                <a href="#para-restaurantes" class="hover:underline">Para restaurantes</a>
            </nav>
            <p>&copy; {{ date('Y') }} TuCarta</p>
        </footer>
    </div>
</x-layouts.app>
