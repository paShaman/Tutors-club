<form method="post" onsubmit="return submitRegistrationForm($(this))" novalidate>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="email" id="form_email" required>
        <label for="form_email">{{ lng('email') }}</label>
        <small class="form-text text-muted">{{ lng('email_tip') }}</small>
    </div>
    <div class="form-group label-inside">
        <input type="password" class="form-control form-control-lg" name="password" id="form_password" required>
        <label for="form_password">{{ lng('password') }}</label>
    </div>
    <div class="form-group label-inside">
        <input type="password" class="form-control form-control-lg" name="password_confirmation" id="form_password_confirm" required>
        <label for="form_password_confirm">{{ lng('password_confirmation') }}</label>
    </div>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="first_name" id="form_first_name" required>
        <label for="form_first_name">{{ lng('first_name') }}</label>
    </div>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="last_name" id="form_last_name" required>
        <label for="form_last_name">{{ lng('last_name') }}</label>
    </div>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="middle_name" id="form_middle_name" required>
        <label for="form_middle_name">{{ lng('middle_name') }}</label>
    </div>
    <div class="form-group">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="policy_agree" id="form_policy_agree">
            <label class="custom-control-label" for="form_policy_agree">{!!  lng('policy_agree') !!}</label>
        </div>
    </div>
    <div class="form-group">
        <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_KEY') }}"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg">{{ lng('btn.register') }}</button>
</form>

<div class="mt-4">
    <a href="/login">{{ lng('login_do') }}</a> &nbsp;&nbsp;&nbsp; <a href="/password-recovery">{{ lng('password_forgot') }}</a>
</div>

<script>
    function submitRegistrationForm(form)
    {
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/register', form.serialize(), function (data) {
            if (data.success) {
                message(true, data.message);

                setTimeout(function () {
                    window.location.href = '/';
                }, 1000);
            } else {
                grecaptcha.reset();

                if (data.data) {
                    for (var i in data.data) {
                        $('[name=' + i + ']', form).addClass('is-invalid');
                        message(false, data.data[i]);
                    }
                }

                if (data.message) {
                    message(false, data.message);
                }
            }
        });

        return false;
    }
</script>