<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $categoria->nombre }} - {{ $negocio->nombre }}</title>
</head>
<body>
    <p><a href="{{ route('panel.negocio') }}">Volver al panel</a></p>

    <h1>{{ $categoria->nombre }}</h1>

    @if ($categoria->items->isEmpty())
        <p>Esta categoría no tiene productos.</p>
    @else
        <ul>
            @foreach ($categoria->items as $item)
                <li>{{ $item->nombre }} - ${{ number_format($item->precio, 2) }}{{ $item->disponible_hoy ? '' : ' (agotado)' }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
