<x-layouts.app>
    <h1>Moderadores y permisos</h1>

    @if (session('estado'))
        <p>{{ session('estado') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Crear moderador</h2>
    <form method="POST" action="{{ route('moderadores.store') }}">
        @csrf
        <input name="nombre" placeholder="Nombre" required>
        <input name="apellidos" placeholder="Apellidos" required>
        <input name="email" type="email" placeholder="Correo de acceso" required>
        <input name="telefono_whatsapp" placeholder="WhatsApp" required>
        <input name="password" type="password" placeholder="Contraseña temporal" required>
        <button type="submit">[ CREAR ]</button>
    </form>

    <h2>Moderadores</h2>
    @forelse ($moderadores as $moderador)
        <section>
            <h3>{{ $moderador->nombre }} {{ $moderador->apellidos }} ({{ $moderador->email }})</h3>

            <ul>
                @forelse ($moderador->permisos as $asignacion)
                    <li>
                        {{ $asignacion->permiso->clave }}
                        - {{ $asignacion->negocio?->nombre ?? 'Todos los negocios' }}
                        <form method="POST" style="display:inline"
                              action="{{ route('moderadores.permisos.destroy', [$moderador, $asignacion]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">[ QUITAR ]</button>
                        </form>
                    </li>
                @empty
                    <li>Sin permisos</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('moderadores.permisos.store', $moderador) }}">
                @csrf
                <select name="permiso_id" required>
                    @foreach ($permisos as $permiso)
                        <option value="{{ $permiso->id }}">{{ $permiso->clave }}</option>
                    @endforeach
                </select>
                <select name="negocio_id">
                    <option value="">Todos los negocios</option>
                    @foreach ($negocios as $negocio)
                        <option value="{{ $negocio->id }}">{{ $negocio->nombre }}</option>
                    @endforeach
                </select>
                <button type="submit">[ AGREGAR PERMISO ]</button>
            </form>
        </section>
    @empty
        <p>Todavía no hay moderadores.</p>
    @endforelse
</x-layouts.app>