<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'TuCarta' }}</title>

    {{-- Fuente oficial: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-beigeBg font-sans text-brand-dark antialiased">
    <div class="min-h-screen px-4 py-6 sm:py-10">
        {{-- Marco móvil obligatorio: clases exactas de la guía de estilos --}}
        <main class="max-w-md bg-brand-beigeCard rounded-[32px] shadow-2xl shadow-brand-dark/10 border border-brand-beigeBorder overflow-hidden p-5 sm:p-6 relative min-h-[660px] flex flex-col justify-between mx-auto">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
