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
    @inertia
</body>
</html>