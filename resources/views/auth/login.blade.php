{{--
    US-01: Pantalla de inicio de sesion.
    Archivo: resources/views/auth/login.blade.php
    Usa <x-layouts.app>, <x-input> y <x-button> del equipo. El diseno final lo aplica Cami.
--}}
<x-layouts.app title="Iniciar sesión | TuCarta">
    <div class="flex flex-col gap-8 break-words">

        <header>
            <a href="{{ route('landing') }}" class="text-xl font-extrabold text-brand-terracotta">TuCarta</a>
        </header>

        <section class="flex flex-col gap-2" aria-labelledby="titulo-login">
            <h1 id="titulo-login" class="text-2xl font-extrabold leading-tight">Iniciar sesión</h1>
            <p class="text-sm text-brand-dark/80">Ingresa con tu correo electrónico y tu contraseña.</p>
        </section>

        @if ($errors->any())
            <div role="alert" class="flex flex-col gap-1 rounded-2xl border border-brand-terracotta bg-brand-beigeBg p-4 text-sm font-semibold text-brand-terracotta">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4" novalidate>
            @csrf

            <x-input name="email" label="Correo electrónico" type="email" :value="old('email')" autocomplete="email" autofocus />
            <x-input name="password" label="Contraseña" type="password" autocomplete="current-password" />

            <x-button type="submit" variant="terracotta">Entrar</x-button>
        </form>

    </div>
</x-layouts.app>
