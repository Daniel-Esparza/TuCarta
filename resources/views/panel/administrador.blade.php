<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel central - TuCarta</title>
</head>
<body>
    <h1>Hola Administrador</h1>
    <p>Sesión de {{ auth()->user()->email }}.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>

    <p><a href="{{ route('negocios.index') }}">Lista y estado de negocios</a></p>

    @can('create', App\Models\Usuario::class)
        <p><a href="{{ route('moderadores.index') }}">Moderadores y permisos</a></p>
    @endcan

    <h2>Negocios</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Subdominio</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($negocios as $negocio)
                <tr>
                    <td>{{ $negocio->nombre }}</td>
                    <td>{{ $negocio->subdominio }}</td>
                    <td>{{ $negocio->estado->etiqueta() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
