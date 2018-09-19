<footer class="footer bg-light">
    <div class="container-fluid">
        <span class="text-muted">&copy; Tutors club {{ date('Y') }}</span>

        @if (Auth::check())
            <a href="#" class="sp_notify_prompt">{{ lng('push_enable') }}</a>
        @endif
    </div>
</footer>