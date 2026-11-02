<x-layouts.app title="Horarios de atención">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Horarios de atención</h1>
            <p class="text-sm text-brand-dark/70">
                Define a qué horas atiende tu negocio cada día. Puedes poner varios horarios en un día.
                Si cierras después de la medianoche, por ejemplo de 17:00 a 01:00, escribe esas horas tal cual.
            </p>
        </header>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif

        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Tu semana</h2>
            @foreach ($dias as $numero => $nombre)
                <article class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3">
                    <p class="text-sm font-bold">{{ $nombre }}</p>
                    @forelse ($horarios->get($numero, collect()) as $horario)
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <p class="text-sm">
                                {{ substr($horario->hora_apertura, 0, 5) }}
                                a
                                {{ $horario->hora_cierre === \App\Models\Horario::FIN_DE_DIA ? 'medianoche' : substr($horario->hora_cierre, 0, 5) }}
                            </p>
                            <x-confirmar :accion="route('horarios.destroy', $horario)" metodo="DELETE"
                                         etiqueta="Eliminar"
                                         titulo="Eliminar este horario"
                                         mensaje="Tu negocio dejará de atender en esas horas."
                                         confirmar="Eliminar" />
                        </div>
                    @empty
                        <p class="mt-1 text-xs text-brand-dark/50">Cerrado todo el día.</p>
                    @endforelse
                </article>
            @endforeach
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Agregar horario</h2>
            <form method="POST" action="{{ route('horarios.store') }}" class="flex flex-col gap-4">
                @csrf
                <div class="flex flex-col gap-1">
                    <label for="dia_semana" class="text-sm font-bold">Día</label>
                    <select id="dia_semana" name="dia_semana" required
                            class="min-h-[44px] rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-terracotta">
                        @foreach ($dias as $numero => $nombre)
                            <option value="{{ $numero }}" @selected((int) old('dia_semana') === $numero)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('dia_semana')
                        <p class="text-xs text-brand-terracotta" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <x-campo nombre="hora_apertura" etiqueta="Abre" tipo="time" required />
                <x-campo nombre="hora_cierre" etiqueta="Cierra" tipo="time" required
                         ayuda="Si cierras después de la medianoche, pon la hora del día siguiente. Ejemplo: 01:00." />
                <x-button type="submit" variant="terracotta">Guardar horario</x-button>
            </form>
        </section>

        <a href="{{ url('/') }}"
           class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-brand-beigeBorder px-4 py-2.5 font-medium text-brand-dark transition-colors duration-200 hover:bg-brand-beigeCard/50 focus:outline-none focus:ring-2 focus:ring-brand-terracotta focus:ring-offset-2">
            Volver
        </a>
    </div>
</x-layouts.app>