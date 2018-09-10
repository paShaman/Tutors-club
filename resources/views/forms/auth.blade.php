<form>
    <div class="form-group">
        <label>{{ trans('messages.auth.email') }}</label>
        <input type="email" class="form-control">
        <small class="form-text text-muted">{{ lng('auth.email_tip') }}</small>
    </div>
    <div class="form-group">
        <label>{{ trans('messages.auth.password') }}</label>
        <input type="password" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">{{ lng('btn.send') }}</button>
</form>