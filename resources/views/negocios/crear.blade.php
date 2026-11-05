<x-layouts.app title="Alta de negocio">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel central</p>
            <h1 class="text-xl font-extrabold">Alta de negocio</h1>
            <p class="text-sm text-brand-dark/70">El negocio queda activo en cuanto se guarda, y su dueño puede iniciar sesión en {{ 'auth.'.config('tucarta.dominio') }}.</p>
        </header>

    <form method="POST" action="{{ route('negocios.store') }}" novalidate class="flex flex-col gap-6">
        @csrf

        <fieldset class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5">
            <legend class="px-1 text-xs font-bold uppercase tracking-widest">Negocio</legend>

            <x-campo nombre="nombre" etiqueta="Nombre del negocio" required autocomplete="organization" />

            <x-campo
                nombre="direccion_web"
                etiqueta="Dirección web"
                required
                autocomplete="off"
                autocapitalize="none"
                :ayuda="'Solo minúsculas, números y guion, de 3 a 30 caracteres. Quedará como direccion.'.config('tucarta.dominio')" />

            <x-campo nombre="whatsapp" etiqueta="WhatsApp" tipo="tel" inputmode="tel" required ayuda="Con lada y 10 dígitos, por ejemplo +524441234567." />

            <x-campo nombre="ubicacion" etiqueta="Ubicación" required ayuda="Calle, número y colonia." />

            <x-campo nombre="descripcion" etiqueta="Descripción (opcional)" />
        </fieldset>

        <fieldset class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-5">
            <legend class="px-1 text-xs font-bold uppercase tracking-widest">Dueño</legend>

            <x-campo nombre="nombre_dueno" etiqueta="Nombre" required />

            <x-campo nombre="apellidos_dueno" etiqueta="Apellidos" required />

            <x-campo nombre="email" etiqueta="Correo" tipo="email" required autocomplete="off" ayuda="Con este correo inicia sesión." />

            <x-campo nombre="password" etiqueta="Contraseña" tipo="password" required autocomplete="new-password" ayuda="Mínimo 8 caracteres." />
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Dar de alta</x-button>
            <x-button variant="outline" :href="route('negocios.index')">Cancelar</x-button>
        </div>
    </form>
    </div>
</x-layouts.app>
