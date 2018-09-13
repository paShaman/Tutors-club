<div class="mw-500 ml-auto mr-auto">
    <form method="post" onsubmit="return submitAuthForm($(this))" novalidate>
        <div class="form-group label-inside">
            <input type="email" class="form-control form-control-lg" name="email" id="form_email" required>
            <label for="form_email">{{ lng('email') }}</label>
            <small class="form-text text-muted">{{ lng('email_tip') }}</small>
        </div>
        <div class="form-group label-inside">
            <input type="password" class="form-control form-control-lg" name="password" id="form_password" required>
            <label for="form_password">{{ lng('password') }}</label>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">{{ lng('btn.auth') }}</button>
    </form>

    <div class="mt-4">
        <a href="/registration">{{ lng('registration_do') }}</a> &nbsp;&nbsp;&nbsp; <a href="/password-recovery">{{ lng('password_forgot') }}</a>
    </div>
</div>

<script>
    function submitAuthForm(form)
    {
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/login', form.serialize(), function (data) {
            if (data.success) {
                message(true, data.message);

                window.location.href = '/';
            } else {
                if (data.data) {
                    for (var i in data.data) {
                        $('[name=' + i + ']', form).addClass('is-invalid');
                        message(false, data.data[i]);
                    }
                } else {
                    message(false, data.message);
                }
            }
        });

        return false;
    }
</script>