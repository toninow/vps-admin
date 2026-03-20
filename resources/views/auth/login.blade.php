<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Musical Princesa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#b90064',
                        'primary-container': '#e6007e',
                        secondary: '#5e5e5e',
                        surface: '#fbf9f8',
                        'surface-container-low': '#f5f3f3',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-high': '#eae8e7',
                        'outline-variant': '#e2bdc7',
                        'on-surface': '#1b1c1c',
                        'on-surface-variant': '#5a3f47',
                    },
                    fontFamily: {
                        headline: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                    borderRadius: {
                        DEFAULT: '0.125rem',
                        lg: '0.25rem',
                        xl: '0.5rem',
                        full: '0.75rem',
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 450, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .login-bg {
            background: url('/assets/login-bg.jpg') center/cover no-repeat;
        }
        .login-overlay {
            background:
                radial-gradient(circle at top right, rgba(230, 0, 126, 0.20), transparent 24%),
                linear-gradient(135deg, rgba(12, 12, 12, 0.78) 0%, rgba(16, 16, 16, 0.62) 45%, rgba(185, 0, 100, 0.18) 100%);
        }
        .lights-track {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .light-streak {
            position: absolute;
            height: 2px;
            border-radius: 9999px;
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
        .light-streak.ltr { animation-name: pass; }
        .light-streak.rtl { animation-name: pass-reverse; }
        @keyframes pass {
            0% { transform: translateX(-150px); }
            100% { transform: translateX(100vw); }
        }
        @keyframes pass-reverse {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-150px); }
        }
        .glass-panel {
            backdrop-filter: blur(12px);
            background-color: rgba(255, 255, 255, 0.68);
        }
        .no-border-input {
            border: none !important;
            border-bottom: 2px solid transparent !important;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }
        .no-border-input:focus {
            box-shadow: none !important;
            border-bottom: 2px solid #b90064 !important;
            background-color: rgba(255, 255, 255, 0.95) !important;
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-surface font-body text-on-surface">
    <div class="fixed inset-0 login-bg"></div>
    <div class="fixed inset-0 login-overlay"></div>

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

    <div class="relative z-10 flex min-h-screen flex-col justify-between">
        <header class="flex h-16 items-center justify-between px-6 text-white/90">
            <div class="font-headline text-xl font-extrabold tracking-tight">Musical Princesa</div>
            <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-xl">language</span>
                <span class="material-symbols-outlined text-xl">info</span>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="w-full max-w-[460px]">
                <div class="glass-panel rounded-xl border border-outline-variant/15 p-10 shadow-[0_12px_32px_-4px_rgba(27,28,28,0.06)]">
                    <div class="mb-10 text-center">
                        <div class="mb-4 flex justify-center">
                            <img src="/assets/logo.png" alt="Musical Princesa" class="h-24 w-auto drop-shadow-sm">
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="email" class="block text-[0.6875rem] font-bold uppercase tracking-wider text-on-surface-variant">Email o usuario</label>
                            <div class="group relative flex items-center rounded-lg bg-surface-container-low">
                                <span class="material-symbols-outlined absolute left-4 text-xl text-gray-400 transition-colors group-focus-within:text-primary">person</span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="ejemplo@musicalprincesa.com"
                                    class="no-border-input w-full bg-transparent py-3.5 pl-12 pr-4 text-sm font-medium text-on-surface placeholder:text-gray-400 focus:ring-0"
                                >
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-end justify-between">
                                <label for="password" class="block text-[0.6875rem] font-bold uppercase tracking-wider text-on-surface-variant">Contraseña</label>
                                <span class="text-[0.6875rem] font-bold uppercase tracking-wider text-primary/80">Acceso seguro</span>
                            </div>
                            <div class="group relative flex items-center rounded-lg bg-surface-container-low">
                                <span class="material-symbols-outlined absolute left-4 text-xl text-gray-400 transition-colors group-focus-within:text-primary">lock</span>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="no-border-input w-full bg-transparent py-3.5 pl-12 pr-12 text-sm font-medium text-on-surface placeholder:text-gray-400 focus:ring-0"
                                >
                                <button
                                    type="button"
                                    id="toggle-password"
                                    class="absolute right-3 inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-white/70 hover:text-primary"
                                    aria-label="Mostrar contraseña"
                                    aria-pressed="false"
                                >
                                    <span class="material-symbols-outlined text-xl">visibility</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/20">
                            <label for="remember" class="text-sm font-medium text-secondary">Recordarme en este dispositivo</label>
                        </div>

                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-primary to-primary-container px-6 py-4 text-xs font-bold uppercase tracking-[0.22em] text-white shadow-lg shadow-primary/20 transition-transform duration-200 hover:scale-[0.99]">
                            Entrar al sistema
                            <span class="material-symbols-outlined text-base">login</span>
                        </button>
                    </form>

                    <div class="mt-8 flex flex-col items-center gap-4 border-t border-outline-variant/15 pt-8">
                        <div class="flex items-center gap-2 text-secondary/70">
                            <span class="material-symbols-outlined text-lg">verified_user</span>
                            <span class="text-[0.6875rem] font-bold uppercase tracking-wider">Autenticación segura SSL</span>
                        </div>
                    </div>
                </div>

                <p class="mt-8 text-center text-sm text-white/85">
                    ¿Necesitas acceso? <span class="font-bold text-white">Contacta con TI</span>
                </p>
            </div>
        </main>

        <footer class="flex flex-col gap-3 border-t border-white/10 px-6 py-4 text-xs text-white/75 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap items-center gap-4">
                <span class="font-headline text-sm font-bold text-white">Musical Princesa</span>
                <span>© 2026 Musical Princesa. Todos los derechos reservados.</span>
            </div>
            <div class="flex flex-wrap gap-6">
                <span class="font-medium">Estado del sistema</span>
                <span class="font-medium">Privacidad</span>
                <span class="font-medium">Soporte</span>
            </div>
        </footer>
    </div>

    <script>
        (() => {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');

            if (!passwordInput || !toggleButton) {
                return;
            }

            const icon = toggleButton.querySelector('.material-symbols-outlined');

            toggleButton.addEventListener('click', () => {
                const showing = passwordInput.type === 'text';
                passwordInput.type = showing ? 'password' : 'text';
                toggleButton.setAttribute('aria-pressed', String(!showing));
                toggleButton.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');

                if (icon) {
                    icon.textContent = showing ? 'visibility' : 'visibility_off';
                }
            });
        })();
    </script>
</body>
</html>
