<form class="form" novalidate>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="email" id="form_email" required>
        <label for="form_email">{{ lng('email') }}</label>
        <small class="form-text text-muted">{{ lng('email_tip') }}</small>
    </div>
    <span class="btn waves-effect waves-light btn-primary btn-lg" onclick="submitForm($(this), submitRecoveryForm)">{{ lng('btn.send') }}</span>
</form>

<div class="mt-4">
    <a href="/login">{{ lng('login_do') }}</a> &nbsp;&nbsp;&nbsp; <a href="/register">{{ lng('register_do') }}</a>
</div>

<script>
    function submitRecoveryForm(btn)
    {
        var form = btn.closest('.form');
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/password-recovery', form.serialize(), function (data) {
            endSubmitForm();

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
    }
</script>