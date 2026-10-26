@props(['encabezados' => []])

{{-- Tabla que en computadora se ve como tabla y en celular se vuelve una lista de tarjetas.
     Uso:
     <x-tabla :encabezados="['Nombre', 'Estado']">
         <x-tabla.fila>
             <x-tabla.celda etiqueta="Nombre">...</x-tabla.celda>
             <x-tabla.celda etiqueta="Estado">...</x-tabla.celda>
         </x-tabla.fila>
     </x-tabla>
     La "etiqueta" de cada celda es el texto que se ve en celular; debe coincidir con su encabezado. --}}
<div {{ $attributes->merge(['class' => 'md:overflow-hidden md:rounded-lg md:border md:border-brand-beigeBorder md:bg-brand-beigeCard']) }}>
    <table class="block w-full text-left text-sm md:table">
        <thead class="sr-only md:not-sr-only md:table-header-group">
            <tr class="md:table-row md:bg-brand-dark md:text-white">
                @foreach ($encabezados as $encabezado)
                    <th scope="col" class="px-4 py-3 font-semibold">{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="block md:table-row-group">
            {{ $slot }}
        </tbody>
    </table>
</div>
