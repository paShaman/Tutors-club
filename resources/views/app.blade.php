<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <title data-inertia>{{ config('app.name', 'Tutors Club') }}</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400..700&family=Inter:wght@400..700&display=swap" rel="stylesheet" />

    @vite(['resources/js/app.ts'])

    {{-- Стартовая заставка. Живёт до первого монтирования приложения (app.ts снимает её
         по id), закрывая белый экран, пока грузятся чанк входа и CSS.
         Стили инлайновые: в dev-режиме app.css приезжает вместе с JS и до его
         исполнения utility-классов ещё нет. --}}
    <style>
        #app-splash {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
            background: #fff;
            font-family: "Geist", "Inter", ui-sans-serif, system-ui, sans-serif;
            /* Появляется только если загрузка затянулась: на быстрой загрузке приложение
               монтируется раньше, и заставка не мелькает. Порог тот же, что у полосы
               загрузки Inertia (250 мс). */
            opacity: 0;
            animation: app-splash-appear 0.15s ease-out 0.25s forwards;
        }
        #app-splash .app-splash__logo {
            display: flex;
            height: 44px;
            width: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: hsl(252 87% 67%);
            color: #fff;
            box-shadow: 0 10px 15px -3px hsl(252 87% 67% / 0.25);
        }
        #app-splash .app-splash__logo svg {
            height: 22px;
            width: 22px;
        }
        #app-splash .app-splash__spinner {
            height: 20px;
            width: 20px;
            border-radius: 9999px;
            border: 2px solid hsl(214.3 31.8% 91.4%);
            border-top-color: hsl(252 87% 67%);
            animation: app-splash-spin 0.8s linear infinite;
        }
        @keyframes app-splash-appear {
            to { opacity: 1; }
        }
        @keyframes app-splash-spin {
            to { transform: rotate(360deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            #app-splash .app-splash__spinner { animation: none; }
        }
    </style>

    @inertiaHead

    @php($metrikaId = (int) config('services.yandex_metrika.id'))
    @if ($metrikaId > 0 && ! request()->is('admin/*'))
        <!-- Yandex.Metrika counter -->
        <script>
            (function (m, e, t, r, i, k, a) {
                m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments) };
                m[i].l = 1 * new Date();
                for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
                k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
            })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id={{ $metrikaId }}', 'ym');

            window.yandexMetrikaId = {{ $metrikaId }};

            ym({{ $metrikaId }}, 'init', {
                ssr: true,
                webvisor: true,
                clickmap: true,
                accurateTrackBounce: true,
                trackLinks: true,
            });
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/{{ $metrikaId }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter -->
    @endif
</head>
<body class="min-h-screen bg-background font-sans text-foreground antialiased">
    <div id="app-splash" aria-hidden="true">
        <div class="app-splash__logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6" />
                <path d="M2 10l10-5 10 5-10 5z" />
                <path d="M6 12v5c3 3 9 3 12 0v-5" />
            </svg>
        </div>
        <span class="app-splash__spinner"></span>
    </div>

    @inertia
</body>
</html>