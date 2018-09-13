<div class="mw-500 ml-auto mr-auto">
    <form method="post" onsubmit="return submitRecoveryForm($(this))" novalidate>
        <div class="form-group label-inside">
            <input type="text" class="form-control form-control-lg" name="email" id="form_email" required>
            <label for="form_email">{{ lng('email') }}</label>
            <small class="form-text text-muted">{{ lng('email_tip') }}</small>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">{{ lng('btn.send') }}</button>
    </form>

    <div class="mt-4">
        <a href="/login">{{ lng('login_do') }}</a> &nbsp;&nbsp;&nbsp; <a href="/register">{{ lng('register_do') }}</a>
    </div>
</div>

<script>
    function submitRecoveryForm(form)
    {
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/password-recovery', form.serialize(), function (data) {
            if (data.success) {
                message(true, data.message);

                setTimeout(function () {
                    window.location.href = '/';
                }, 1000);
            } else {
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