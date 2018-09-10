<form method="POST" action="/login">
    <div class="form-group">
        <label>{{ lng('email') }}</label>
        <input type="email" class="form-control">
        <small class="form-text text-muted">{{ lng('email_tip') }}</small>
    </div>
    <div class="form-group">
        <label>{{ lng('password') }}</label>
        <input type="password" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">{{ lng('btn.send') }}</button>
</form>

<a href="/registration">{{ lng('registration_do') }}</a>