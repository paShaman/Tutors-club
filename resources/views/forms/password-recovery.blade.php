<form class="form" novalidate>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="email" id="form_email" required>
        <label for="form_email">{{ lng('email') }}</label>
        <small class="form-text text-muted">{{ lng('email_tip') }}</small>
    </div>
    <span class="<?=App\Common::BTN?> btn-primary btn-lg" onclick="submitForm($(this), submitRecoveryForm)">{{ lng('btn.send') }}</span>
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
                message(true, data.data);

                setTimeout(function () {
                    window.location.href = '/';
                }, 1000);
            } else {
                errorMessages(data, form);
            }
        });
    }
</script>