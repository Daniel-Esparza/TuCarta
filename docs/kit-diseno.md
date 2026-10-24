# Kit de diseno (Sprint 6, US-34)

Componentes Blade listos para usar en las pantallas del panel central. Solo usan los colores y el layout del sistema "Beige Terroso y Gourmet" (tokens de `app.css`). No hay emojis y todo control mide al menos 44 px.

## Insignia de estado
```blade
<x-estado-negocio :estado="$negocio->estado" />
```
Estados: `activo`, `pendiente`, `suspendido`, `rechazado`. Cualquier otro valor se muestra con estilo neutro.

## Tabla que se vuelve tarjetas en celular
```blade
<x-tabla :encabezados="['Nombre', 'Direccion web', 'Estado']">
    @foreach ($negocios as $negocio)
        <x-tabla.fila>
            <x-tabla.celda etiqueta="Nombre">{{ $negocio->nombre }}</x-tabla.celda>
            <x-tabla.celda etiqueta="Direccion web">{{ $negocio->subdominio }}</x-tabla.celda>
            <x-tabla.celda etiqueta="Estado"><x-estado-negocio :estado="$negocio->estado" /></x-tabla.celda>
        </x-tabla.fila>
    @endforeach
</x-tabla>
```
La `etiqueta` de cada celda es el texto que se ve en celular; debe ser igual a su encabezado.

## Ventana de confirmacion
```blade
<x-confirmar
    :accion="route('negocios.suspender', $negocio)"
    metodo="PATCH"
    etiqueta="Suspender"
    titulo="Suspender este negocio"
    mensaje="Su menu mostrara un aviso. No se borra su informacion."
    confirmar="Suspender" />
```
Usa el `<dialog>` del navegador (no necesita Alpine). Envia el formulario con `@csrf` y el metodo indicado (`POST` por defecto).

## Campo con etiqueta y error por campo
```blade
<x-campo nombre="direccion_web" etiqueta="Direccion web" :valor="$negocio->subdominio ?? null"
         ayuda="Solo minusculas, numeros y guion, de 3 a 30 caracteres." />
```
Muestra el error de validacion de ese campo y conserva lo escrito (`old`). Si `tipo="password"`, nunca repite el valor.

## Alerta
```blade
<x-alerta>Negocio creado.</x-alerta>
<x-alerta tipo="error">No se pudo guardar.</x-alerta>
```

## Pruebas
`tests/Feature/KitDisenoTest.php` renderiza cada componente. Correr con `./vendor/bin/sail artisan test`.
