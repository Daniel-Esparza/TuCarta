<x-layouts.app title="Iniciar Sesión - TuCarta">
    <div class="max-w-md mx-auto mt-8 bg-brand-beigeCard p-6 rounded-xl border border-brand-beigeBorder shadow-sm">
        <h2 class="text-2xl font-bold text-brand-dark mb-6 text-center">Iniciar Sesión</h2>
        
        <form method="POST" action="/login" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-brand-dark mb-1">Correo electrónico</label>
                <x-input type="email" name="email" required placeholder="correo@ejemplo.com" />
            </div>

            <div>
                <label class="block text-sm font-medium text-brand-dark mb-1">Contraseña</label>
                <x-input type="password" name="password" required />
            </div>

            <div class="pt-2">
                <x-button variant="primary" type="submit" class="w-full">
                    Entrar al sistema
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
