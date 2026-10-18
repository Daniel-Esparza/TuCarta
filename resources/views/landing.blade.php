@php
    // Mientras no existan las rutas de restaurantes y de registro
    $urlRestaurantes = null;
    $urlRegistro = '#para-restaurantes';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TuCarta | Consulta la carta y pide por WhatsApp</title>
    <meta name="description" content="TuCarta es una plataforma para encontrar restaurantes, revisar sus cartas y hacer tu pedido por WhatsApp.">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.6; color: #1f1f1f; background: #ffffff; overflow-wrap: anywhere; }
        a { color: inherit; }
        a:focus-visible { outline: 3px solid currentColor; outline-offset: 3px; }
        .contenedor { width: 100%; max-width: 70rem; margin: 0 auto; padding: 0 1rem; }
        .seccion { padding: 3rem 0; }
        .seccion + .seccion { border-top: 1px solid #d9d9d9; }
        h1 { font-size: clamp(1.875rem, 6vw, 3rem); line-height: 1.15; margin: 0 0 1rem; }
        h2 { font-size: clamp(1.5rem, 4vw, 2rem); line-height: 1.2; margin: 0 0 1rem; }
        h3 { font-size: 1.125rem; margin: 0 0 .5rem; }
        p { margin: 0 0 1rem; max-width: 42rem; }

        .cabecera { border-bottom: 1px solid #d9d9d9; }
        .cabecera .contenedor { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1.5rem; padding-top: 1rem; padding-bottom: 1rem; }
        .marca { font-weight: 700; font-size: 1.25rem; text-decoration: none; }
        .navegacion { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1.25rem; }
        .navegacion a { text-decoration: none; }
        .navegacion a:hover { text-decoration: underline; }

        .acciones { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem; }
        .boton { display: inline-block; padding: .75rem 1.25rem; border: 2px solid currentColor; border-radius: .5rem; font-weight: 600; text-align: center; text-decoration: none; }
        .boton-principal { background: #1f1f1f; border-color: #1f1f1f; color: #ffffff; }
        .boton:hover { opacity: .85; }

        .pasos { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); }
        .paso { border: 1px solid #d9d9d9; border-radius: .75rem; padding: 1.25rem; }
        .paso-numero { display: inline-block; font-weight: 700; margin-bottom: .5rem; }
        .paso p { margin: 0; }

        .beneficios { margin: 0 0 1rem; padding-left: 1.25rem; max-width: 42rem; }
        .beneficios li { margin-bottom: .5rem; }

        .pie { border-top: 1px solid #d9d9d9; padding: 2rem 0; }
        .pie .contenedor { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem 2rem; }
        .pie p { margin: 0; }
    </style>
</head>
<body>

    <header class="cabecera">
        <div class="contenedor">
            <a class="marca" href="#inicio">TuCarta</a>

            <nav class="navegacion" aria-label="Navegación principal">
                <a href="#como-funciona">Cómo funciona</a>
                <a href="#para-restaurantes">Para restaurantes</a>
                @if ($urlRestaurantes)
                    <a href="{{ $urlRestaurantes }}">Explorar restaurantes</a>
                @endif
                <a class="boton boton-principal" href="{{ $urlRegistro }}">Registrar mi negocio</a>
            </nav>
        </div>
    </header>

    <main id="inicio">

        {{-- Seccion principal --}}
        <section class="seccion" aria-labelledby="titulo-principal">
            <div class="contenedor">
                <h1 id="titulo-principal">Consulta la carta de tu restaurante y haz tu pedido por WhatsApp</h1>
                <p>
                    TuCarta es una plataforma donde puedes encontrar restaurantes, revisar sus cartas
                    y armar tu pedido. Al confirmarlo, continúas por WhatsApp para comunicárselo al restaurante.
                </p>
                <div class="acciones">
                    @if ($urlRestaurantes)
                        <a class="boton boton-principal" href="{{ $urlRestaurantes }}">Explorar restaurantes</a>
                        <a class="boton" href="{{ $urlRegistro }}">Registrar mi negocio</a>
                    @else
                        <a class="boton boton-principal" href="{{ $urlRegistro }}">Registrar mi negocio</a>
                        <a class="boton" href="#como-funciona">Ver cómo funciona</a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Como funciona --}}
        <section class="seccion" id="como-funciona" aria-labelledby="titulo-como-funciona">
            <div class="contenedor">
                <h2 id="titulo-como-funciona">Cómo funciona</h2>
                <p>Pedir con TuCarta toma tres pasos.</p>

                <ol class="pasos">
                    <li class="paso">
                        <span class="paso-numero">Paso 1</span>
                        <h3>Encuentra un restaurante</h3>
                        <p>Busca el establecimiento que te interesa y consulta su carta.</p>
                    </li>
                    <li class="paso">
                        <span class="paso-numero">Paso 2</span>
                        <h3>Arma tu pedido</h3>
                        <p>Selecciona los productos que quieres y organiza tu pedido antes de continuar.</p>
                    </li>
                    <li class="paso">
                        <span class="paso-numero">Paso 3</span>
                        <h3>Continúa por WhatsApp</h3>
                        <p>Confirma tu pedido y sigue en WhatsApp para comunicárselo al restaurante.</p>
                    </li>
                </ol>
            </div>
        </section>

        {{-- Para restaurantes --}}
        <section class="seccion" id="para-restaurantes" aria-labelledby="titulo-restaurantes">
            <div class="contenedor">
                <h2 id="titulo-restaurantes">¿Tienes un restaurante? Presenta tu carta en TuCarta</h2>
                <p>
                    TuCarta te ofrece un lugar para mostrar tu menú y facilitar que tus clientes
                    te comuniquen sus pedidos por WhatsApp.
                </p>

                <ul class="beneficios">
                    <li>Muestra tu carta en un solo lugar, lista para consultarse desde el celular.</li>
                    <li>Tus clientes revisan los productos antes de decidir qué pedir.</li>
                    <li>Recibe los pedidos mediante WhatsApp, un canal que tus clientes ya conocen.</li>
                </ul>

                <div class="acciones">
                    <a class="boton boton-principal" href="{{ $urlRegistro }}">Registrar mi negocio</a>
                </div>
            </div>
        </section>

        {{-- Invitacion final --}}
        <section class="seccion" aria-labelledby="titulo-final">
            <div class="contenedor">
                <h2 id="titulo-final">Conoce TuCarta</h2>
                <p>
                    Descubre cómo se ve la carta de un restaurante o registra tu negocio
                    para presentar tu menú a tus clientes.
                </p>
                <div class="acciones">
                    @if ($urlRestaurantes)
                        <a class="boton boton-principal" href="{{ $urlRestaurantes }}">Explorar restaurantes</a>
                        <a class="boton" href="{{ $urlRegistro }}">Registrar mi negocio</a>
                    @else
                        <a class="boton boton-principal" href="{{ $urlRegistro }}">Registrar mi negocio</a>
                    @endif
                </div>
            </div>
        </section>

    </main>

    <footer class="pie">
        <div class="contenedor">
            <p><strong>TuCarta</strong></p>
            <nav class="navegacion" aria-label="Navegación del pie de página">
                <a href="#inicio">Inicio</a>
                <a href="#como-funciona">Cómo funciona</a>
                <a href="#para-restaurantes">Para restaurantes</a>
            </nav>
            <p>&copy; {{ date('Y') }} TuCarta</p>
        </div>
    </footer>

</body>
</html>
