{{--
    US-26a: Formulario publico de auto-registro de negocios.
    Archivo: resources/views/auto-registro/create.blade.php
    Usa <x-layouts.app> y los componentes del kit (x-campo, x-alerta, x-button).
--}}
<x-layouts.app title="Registrar mi negocio | TuCarta">
    <div class="mx-auto flex w-full max-w-xl flex-col gap-6">

        <section class="flex flex-col gap-2" aria-labelledby="titulo-registro">
            <h2 id="titulo-registro" class="text-2xl font-extrabold leading-tight">Registra tu negocio</h2>
            <p class="text-sm text-brand-dark/80">
                Déjanos los datos de tu negocio. Un administrador revisará tu solicitud.
            </p>
        </section>

        @if (session('enviado'))
            <x-alerta>
                Recibimos tu solicitud. Tu negocio está en revisión.
            </x-alerta>

            <x-button variant="outline" :href="route('landing')">Volver al inicio</x-button>
        @else
            @if (session('limite'))
                <x-alerta tipo="error">{{ session('limite') }}</x-alerta>
            @endif

            <form method="POST" action="{{ route('auto-registro.store') }}" class="flex flex-col gap-4" novalidate>
                @csrf

                <x-campo nombre="nombre" etiqueta="Nombre del negocio" autocomplete="organization" />

                <x-campo
                    nombre="direccion_web"
                    etiqueta="Dirección web"
                    autocomplete="off"
                    autocapitalize="none"
                    :ayuda="'Solo minúsculas, números y guion, de 3 a 30 caracteres. Se verá como tu-negocio.'.config('tucarta.dominio')" />

                <x-campo
                    nombre="whatsapp"
                    etiqueta="WhatsApp"
                    tipo="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    ayuda="Con lada y 10 dígitos, por ejemplo +524441234567." />

                <x-campo nombre="correo_contacto" etiqueta="Correo de contacto" tipo="email" autocomplete="email" />

                <x-button type="submit" variant="terracotta">Enviar solicitud</x-button>
            </form>
        @endif

    </div>
</x-layouts.app>
