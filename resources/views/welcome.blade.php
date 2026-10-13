<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Estilos - TuCarta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col items-center justify-center p-6">
    <div class="max-w-md w-full bg-slate-800 p-8 rounded-2xl shadow-2xl border border-slate-700 text-center">
        <h1 class="text-3xl font-bold text-indigo-400 mb-4">¡TuCarta Funciona! </h1>
        <p class="text-slate-300 mb-6">
            El entorno de desarrollo está completamente listo. Ya puedes comenzar a diseñar tus componentes y vistas.
        </p>
        <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-6 py-2.5 rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20">
            Probar Botón
        </button>
    </div>
</body>
</html>