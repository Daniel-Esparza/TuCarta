{{--
    US-19b: Cierre temporal del negocio (panel del dueño).
    Archivo: resources/views/cierre-temporal.blade.php
    Usa <x-layouts.app> y los componentes del kit (x-alerta, x-confirmar, x-button).
--}}
<x-layouts.app title="Cierre temporal | TuCarta">
    <div class="mx-auto flex w-full max-w-xl flex-col gap-6">

        <section class="flex flex-col gap-2" aria-labelledby="titulo-cierre">
            <h2 id="titulo-cierre" class="text-2xl font-extrabold leading-tight">Cierre temporal</h2>
            <p class="text-sm text-brand-dark/80">
                Mientras el cierre esté activo, tu menú se sigue viendo pero no se pueden enviar pedidos,
                aunque estés dentro de tu horario.
            </p>
        </section>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif

        <section class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5" aria-labelledby="titulo-estado">
            <h3 id="titulo-estado" class="text-sm font-semibold">Estado actual</h3>

            @if ($negocio->cerrado_temporal)
                <x-alerta tipo="error">Cerrado temporalmente. No se reciben pedidos.</x-alerta>
            @else
                <x-alerta>Sin cierre temporal. Los pedidos dependen de tu horario.</x-alerta>
            @endif

            <p class="text-sm text-brand-dark/80">
                Según tu horario, en este momento el negocio está
                {{ $dentroDeHorario ? 'dentro' : 'fuera' }} de su horario de atención.
            </p>

            <div class="flex flex-col gap-3">
                @if ($negocio->cerrado_temporal)
                    <form method="POST" action="{{ route('cierre-temporal.reabrir') }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="terracotta">Reabrir mi negocio</x-button>
                    </form>
                @else
                    <x-confirmar
                        :accion="route('cierre-temporal.cerrar')"
                        metodo="PATCH"
                        etiqueta="Cerrar temporalmente"
                        titulo="Cerrar temporalmente"
                        mensaje="Tu menú se seguirá viendo, pero no se podrán enviar pedidos hasta que reabras."
                        confirmar="Cerrar" />
                @endif

                <x-button variant="outline" :href="route('horarios.index')">Ver mis horarios</x-button>
            </div>
        </section>

    </div>
</x-layouts.app>
