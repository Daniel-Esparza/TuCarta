<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel - {{ $negocio->nombre }}</title>
</head>
<body>
    <h1>Hola Negocio</h1>
    <p>Panel de {{ $negocio->nombre }}. Sesión de {{ auth()->user()->email }}.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>

    @if (auth()->user()->tienePermiso(App\Enums\ClavePermiso::GestionarEquipo))
        <p><a href="{{ route('equipo.index') }}">Equipo de trabajo</a></p>
    @endif

    <h2>Categorías</h2>
    @if ($categorias->isEmpty())
        <p>Este negocio todavía no tiene categorías.</p>
    @else
        <ul>
            @foreach ($categorias as $categoria)
                <li>
                    <a href="{{ route('panel.negocio.categoria', $categoria) }}">{{ $categoria->nombre }}</a>
                    ({{ $categoria->items_count }} productos)
                </li>
            @endforeach
        </ul>
    @endif
</body>
</html>
