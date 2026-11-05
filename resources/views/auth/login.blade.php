<x-layouts.app title="Iniciar Sesión - TuCarta">
    <div class="max-w-md mx-auto mt-8 bg-brand-beigeCard p-6 rounded-xl border border-brand-beigeBorder shadow-sm">
        <h2 class="text-2xl font-bold text-brand-dark mb-6 text-center">Iniciar Sesión</h2>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            <x-campo nombre="email" etiqueta="Correo electrónico" tipo="email" required autofocus autocomplete="username" placeholder="correo@ejemplo.com" />

            <x-campo nombre="password" etiqueta="Contraseña" tipo="password" required autocomplete="current-password" />

            <div class="pt-2">
                <x-button variant="primary" type="submit" class="w-full">
                    Entrar al sistema
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
