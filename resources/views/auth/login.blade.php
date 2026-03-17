<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Musical Princesa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .login-bg {
            background: url('/assets/login-bg.jpg') center/cover no-repeat;
        }
        .login-overlay {
            background: linear-gradient(135deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.6) 50%, rgba(230,0,126,0.25) 100%);
        }
        /* Luces de coches pasando */
        .lights-track {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .light-streak {
            position: absolute;
            height: 2px;
            border-radius: 50%;
            filter: blur(1px);
            opacity: 0.9;
            animation: pass linear infinite;
        }
        .light-streak.head {
            width: 120px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), rgba(255,255,255,0.9), rgba(255,255,255,0.3), transparent);
            box-shadow: 0 0 20px rgba(255,255,255,0.8), 0 0 40px rgba(255,255,255,0.4);
        }
        .light-streak.tail {
            width: 80px;
            background: linear-gradient(90deg, transparent, rgba(255,80,80,0.4), rgba(255,40,40,0.9), rgba(255,80,80,0.4), transparent);
            box-shadow: 0 0 15px rgba(255,60,60,0.7);
        }
        .light-streak.amber {
            width: 100px;
            background: linear-gradient(90deg, transparent, rgba(255,200,100,0.5), rgba(255,180,80,0.9), transparent);
            box-shadow: 0 0 18px rgba(255,190,90,0.6);
        }
        @keyframes pass {
            0% { transform: translateX(-150px); }
            100% { transform: translateX(100vw); }
        }
        @keyframes pass-reverse {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-150px); }
        }
        .light-streak.ltr { animation-name: pass; }
        .light-streak.rtl { animation-name: pass-reverse; }
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center bg-gray-900 overflow-hidden">
    <div class="fixed inset-0 login-bg"></div>
    <div class="fixed inset-0 login-overlay"></div>

    <!-- Luces de coches -->
    <div class="lights-track">
        <div class="light-streak head ltr" style="top: 18%; animation-duration: 4.2s; animation-delay: 0s;"></div>
        <div class="light-streak tail rtl" style="top: 22%; animation-duration: 5s; animation-delay: 0.8s;"></div>
        <div class="light-streak amber ltr" style="top: 35%; animation-duration: 3.5s; animation-delay: 1.5s;"></div>
        <div class="light-streak head rtl" style="top: 48%; animation-duration: 4.8s; animation-delay: 0.3s;"></div>
        <div class="light-streak tail ltr" style="top: 55%; animation-duration: 5.5s; animation-delay: 2s;"></div>
        <div class="light-streak head ltr" style="top: 68%; animation-duration: 3.8s; animation-delay: 1s;"></div>
        <div class="light-streak amber rtl" style="top: 75%; animation-duration: 4.5s; animation-delay: 2.2s;"></div>
        <div class="light-streak tail ltr" style="top: 85%; animation-duration: 4s; animation-delay: 0.5s;"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-4">
        <div class="relative rounded-2xl bg-white/95 backdrop-blur-xl shadow-2xl border border-white/20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent pointer-events-none"></div>
            <div class="relative px-8 py-10">
                <div class="flex flex-col items-center mb-8">
                    <img src="/assets/logo.png" alt="Musical Princesa" class="h-20 w-auto drop-shadow-sm">
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-50/90 border border-red-200/80 px-4 py-3 text-sm text-red-800 shadow-inner">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                               class="block w-full rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-[#E6007E] focus:ring-2 focus:ring-[#E6007E]/20 focus:bg-white transition shadow-sm">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="block w-full rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-[#E6007E] focus:ring-2 focus:ring-[#E6007E]/20 focus:bg-white transition shadow-sm">
                    </div>
                    <div class="flex items-center">
                        <input id="remember" type="checkbox" name="remember"
                               class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Recordarme</label>
                    </div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-xl text-sm font-semibold text-white bg-[#E6007E] hover:bg-[#d1006f] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E6007E] shadow-lg shadow-[#E6007E]/25 transition">
                        Entrar
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
