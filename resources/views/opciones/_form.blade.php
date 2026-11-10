<form method="POST" action="{{ route('opciones.store', $grupo) }}">
    @csrf
    <label>Nombre
        <input type="text" name="nombre" value="{{ old('nombre') }}" required>
    </label>
    <label>Precio extra
        <input type="number" step="0.01" name="incremento_precio" value="{{ old('incremento_precio', 0) }}" required>
    </label>
    @error('nombre') <p style="color:red">{{ $message }}</p> @enderror
    @error('incremento_precio') <p style="color:red">{{ $message }}</p> @enderror
    <button type="submit">Agregar opción</button>
</form>

@foreach ($grupo->opciones as $opcion)
    <div>
        {{ $opcion->nombre }} — ${{ number_format($opcion->incremento_precio, 2) }}
    </div>
@endforeach

@if (session('ok')) <p>{{ session('ok') }}</p> @endif
