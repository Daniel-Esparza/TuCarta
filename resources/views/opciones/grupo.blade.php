<x-layouts.app>
    <h1>Grupo: {{ $grupo->nombre }}</h1>
    @include('opciones._form', ['grupo' => $grupo])
</x-layouts.app>

