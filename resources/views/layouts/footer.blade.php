<footer class="footer bg-light">
    <div class="container-fluid">
        <span class="text-muted">&copy; Tutors club {{ date('Y') }}</span>

        @if (Auth::check())
            <a href="#" id="webpush-subscribe-button" style="display: none;"></a>
        @endif
    </div>
</footer>