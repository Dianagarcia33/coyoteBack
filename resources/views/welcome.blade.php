<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Coyote Workout - Únete en preventa</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at 50% 30%, #0b111e 0%, #0b111e 40%, #000000 80%, #1c0a00 100%);
        }

        .text-gradient {
            background: linear-gradient(to right, #f97316, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body class="text-white min-h-screen px-4 pt-10 text-center flex flex-col items-center">

    <!-- HERO -->
    <section class="flex flex-col items-center mb-12">
        <img src="{{ asset('logo_transparente.png') }}" alt="Logo Coyote" class="w-36 mb-4" />
        <h1 class="text-4xl md:text-5xl font-bold text-gradient">Coyote Workout</h1>
        <p class="text-white/90 mt-3 text-base md:text-lg max-w-sm">Tu manada de motivación, resultados y constancia.
        </p>
    </section>

    <!-- ¿QUÉ ES COYOTE WORKOUT? -->
    <section class="bg-white/5 p-6 rounded-xl max-w-4xl w-full backdrop-blur-sm space-y-3 shadow-md mb-10">
        <h2 class="text-2xl font-semibold text-orange-400">¿Qué es Coyote Workout?</h2>
        <p class="text-white/80 text-sm">
            Una app de fitness que conecta a usuarios con entrenadores reales, certificados y rutinas personalizadas.
            También tendrás clases presenciales cerca de ti (Parche Fit), seguimiento de progreso y sistema de
            recompensas.
        </p>
    </section>

    <!-- GRID PROFESIONAL / CLIENTE -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl w-full mb-10">
        <!-- Profesionales -->
        <div class="bg-white/5 p-6 rounded-xl backdrop-blur-sm space-y-4 shadow-md">
            <h3 class="text-xl font-semibold text-gradient">¿Eres profesional del fitness?</h3>
            <p class="text-white/80">Únete en preventa y obtén beneficios exclusivos:</p>
            <ul class="text-left list-disc list-inside text-white/80 space-y-1 text-sm">
                <li>💸 Precio especial como fundador</li>
                <li>🌟 Visibilidad destacada en la app</li>
                <li>🚀 Acceso anticipado al módulo de gestión de clientes</li>
            </ul>
        </div>

        <!-- Clientes -->
        <div class="bg-white/5 p-6 rounded-xl backdrop-blur-sm space-y-4 shadow-md">
            <h3 class="text-xl font-semibold text-gradient">¿Eres cliente?</h3>
            <p class="text-white/80">¿Cansado de rutinas genéricas que no te funcionan?</p>
            <p class="text-white/80">Con Coyote Workout accedes a:</p>
            <ul class="text-left list-disc list-inside text-white/80 space-y-1 text-sm">
                <li>📲 Profesionales reales y certificados</li>
                <li>🔥 Rutinas 100% personalizadas</li>
                <li>📍 Clases presenciales cerca de ti (Parche Fit)</li>
                <li>🎯 Seguimiento de progreso</li>
                <li>🏆 Sistema de puntos con premios</li>
            </ul>
            <p class="text-white/90 mt-2 font-semibold">¡El cambio empieza contigo, nosotros te acompañamos!</p>
        </div>
        <div class="bg-white/5 p-6 rounded-xl backdrop-blur-sm space-y-4 shadow-md">
            <form method="POST" action="{{ route('registro') }}"
                class="bg-white/10 p-6 rounded-xl max-w-md w-full backdrop-blur-sm space-y-4 shadow-md mb-12">
                @csrf

                <h3 class="text-lg font-semibold text-gradient text-center">Déjanos tus datos y sé parte del movimiento
                </h3>

                <input type="text" name="nombre" placeholder="Tu nombre" value="{{ old('nombre') }}" required
                    class="w-full px-4 py-2 rounded-md bg-white/20 border border-white/30 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-orange-500">

                <input type="email" name="email" placeholder="Correo electrónico" value="{{ old('email') }}"
                    required
                    class="w-full px-4 py-2 rounded-md bg-white/20 border border-white/30 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-orange-500">

                <select name="tipo" required
                    class="w-full px-4 py-2 rounded-md bg-white text-black border border-white/30 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Soy...</option>
                    <option value="profesional" {{ old('tipo') === 'profesional' ? 'selected' : '' }}>Profesional del
                        fitness</option>
                    <option value="cliente" {{ old('tipo') === 'cliente' ? 'selected' : '' }}>Cliente / Usuario</option>
                </select>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-orange-500 to-red-500 text-white py-2 rounded-md hover:brightness-110 shadow-lg active:scale-95 transition">
                    Unirme en preventa
                </button>
            </form>
        </div>
    </section>

    <!-- MENSAJE DE ÉXITO -->
    @if (session('success'))
        <div id="flash-message"
            class="bg-green-500/20 border border-green-400 text-green-200 px-4 py-3 rounded-md text-sm mb-4 max-w-md w-full text-center">
            {{ session('success') }}
        </div>

        <script>
            setTimeout(() => {
                const flash = document.getElementById('flash-message');
                if (flash) flash.style.display = 'none';
            }, 5000);
        </script>
    @endif

    <!-- MENSAJE DE ERROR -->
    @if ($errors->any())
        <div
            class="bg-red-500/20 border border-red-400 text-red-200 px-4 py-3 rounded-md text-sm mb-4 max-w-md w-full text-left">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FORMULARIO -->


    <!-- FOOTER -->
    <footer class="text-white/50 text-sm mb-6">
        © {{ date('Y') }} Coyote Workout. Todos los derechos reservados.
    </footer>

</body>

</html>
