<form class="form" novalidate>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="email" id="form_email" required>
        <label for="form_email">{{ lng('email') }}</label>
        <small class="form-text text-muted">{{ lng('email_tip') }}</small>
    </div>
    <div class="form-group label-inside">
        <input type="password" class="form-control form-control-lg" name="password" id="form_password" required>
        <label for="form_password">{{ lng('password') }}</label>
    </div>
    <span class="<?=App\Common::BTN?> btn-primary btn-lg" onclick="submitForm($(this), submitAuthForm)">{{ lng('btn.login') }}</span>
</form>

<div class="mt-4">
    <a href="/register">{{ lng('register_do') }}</a> &nbsp;&nbsp;&nbsp; <nobr><a href="/password-recovery">{{ lng('password_forgot') }}</a></nobr>
</div>

<div class="mt-4">
    <a href="/login/facebook"><i class="fab fa-facebook-f"></i></a> &nbsp;&nbsp;&nbsp;
    <a href="/login/vkontakte"><i class="fab fa-vk"></i></a> &nbsp;&nbsp;&nbsp;
    <a href="/login/google"><i class="fab fa-google"></i></a>
</div>

<script>
    function submitAuthForm(btn)
    {
        var form = btn.closest('.form');
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/login', form.serialize(), function (data) {
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