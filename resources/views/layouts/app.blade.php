<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="description" content="{{ $description }}">

    <link rel="icon" type="image/png" sizes="16x16" href="/images/logo.png">

    <title>{{ $title }}</title>

    @foreach ($styles as $style)
        <link href="{{ $style }}" rel="stylesheet">
    @endforeach

    @foreach ($scripts as $script)
        <script src="{{ $script }}"></script>
    @endforeach

    @include('layouts.counters')
</head>

<body>

<div class="wrapper">
    @include('layouts.header')

    @yield('content')

    @include('layouts.footer')
</div>
</body>

</html>