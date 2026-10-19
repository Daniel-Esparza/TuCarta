<!DOCTYPE html>
<html lang="es" class="h-full bg-brand-beigeBg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TuCarta' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-brand-dark font-sans antialiased flex flex-col">
    <header class="bg-brand-beigeCard border-b border-brand-beigeBorder px-4 py-3 shadow-sm">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold text-brand-terracotta">TuCarta</h1>
            {{ $header ?? '' }}
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-6">
        {{ $slot }}
    </main>
</body>
</html>
