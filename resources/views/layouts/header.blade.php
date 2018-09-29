<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <a class="navbar-brand font-weight-bold" href="/">Tutors club</a>

    @if (Auth::check())
    <a href="#" class="navbar-toggler waves-effect" data-toggle="collapse" data-target="#navbarSupportedContent">
        <i class="fa fa-bars"></i>
    </a>
    @endif

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ml-auto">
            @if (Auth::check())
                @if ($user->avatar)
                    <span class="avatar mr-2" style="background-image: url({{ $user->avatar }})"></span>
                @endif

                <li class="navbar-text mr-3">
                    @if (!$user->last_name && !$user->first_name)
                        {{ $user->email }}
                    @else
                        {{ $user->last_name }}
                        {{ $user->first_name }}
                    @endif
                </li>
                <li class="nav-item">
                    <a class="nav-link waves-effect pl-2 pr-2" href="/settings">
                        <i class="fa fa-cogs fa-lg fa-fw"></i>
                        <span class="d-lg-none ml-2">{{ lng('settings') }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link waves-effect pl-2 pr-2" href="/logout">
                        <i class="fa fa-sign-out-alt fa-lg fa-fw"></i>
                        <span class="d-lg-none ml-2">{{ lng('logout') }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</nav>