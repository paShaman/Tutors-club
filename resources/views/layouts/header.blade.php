header

@if (Auth::check())
    <a href="/logout">{{ lng('logout') }}</a>
@endif