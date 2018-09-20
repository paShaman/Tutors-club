<form class="form">
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
    <span class="btn waves-effect waves-light btn-primary btn-lg" onclick="submitForm($(this), submitSettingsForm)">{{ lng('btn.send') }}</span>
</form>

<script>
    function submitSettingsForm(btn)
    {
        var form = btn.closest('.form');
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/settings', form.serialize(), function (data) {
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