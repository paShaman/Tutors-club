<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="description" content="{{ $description }}">

    <link rel="icon" type="image/png" sizes="16x16" href="/images/logo.png">

    <title>{{ $titleFull }}</title>

    @foreach ($styles as $style)
        <link href="{{ $style }}" rel="stylesheet">
    @endforeach

    @foreach ($scripts as $script)
        <script src="{{ $script }}"></script>
    @endforeach

    @if (env('COUNTERS'))
        @include('layouts.counters')
    @endif
</head>

<body class="header-fixed footer-fixed">

    @include('layouts.header')

    <main class="container-fluid">
        @yield('content')
    </main>

    @include('layouts.footer')

<script>
    @foreach ($localization as $key => $value)
        localization['{{ $key }}'] = '{{ $value }}';
    @endforeach
</script>

</body>

</html>