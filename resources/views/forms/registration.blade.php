<form method="post" onsubmit="return submitRegistrationForm($(this))">
    <div class="form-group">
        <label>{{ lng('email') }}</label>
        <input type="email" class="form-control" name="email">
        <small class="form-text text-muted">{{ lng('email_tip') }}</small>
    </div>
    <div class="form-group">
        <label>{{ lng('password') }}</label>
        <input type="password" class="form-control" name="password">
    </div>
    <div class="form-group">
        <label>{{ lng('password_confirm') }}</label>
        <input type="password" class="form-control" name="password_confirm">
    </div>
    <div class="form-group">
        <label>{{ lng('first_name') }}</label>
        <input type="text" class="form-control" name="first_name">
    </div>
    <div class="form-group">
        <label>{{ lng('last_name') }}</label>
        <input type="text" class="form-control" name="last_name">
    </div>
    <div class="form-group">
        <label>{{ lng('middle_name') }}</label>
        <input type="text" class="form-control" name="middle_name">
    </div>
    <div class="form-group">
        <label>
            <input type="checkbox" name="policy_agree">
            {!!  lng('policy_agree') !!}
        </label>
    </div>
    <button type="submit" class="btn btn-primary">{{ lng('btn.send') }}</button>
</form>

<a href="/auth">{{ lng('auth_do') }}</a>

<script>
    function submitRegistrationForm(form)
    {
        $.post('/register', form.serialize(), function (data) {
            if (data.success) {

            }
        });

        return false;
    }
</script>