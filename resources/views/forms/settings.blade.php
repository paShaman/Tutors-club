<form class="form" novalidate>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="last_name" id="form_last_name" required value="{{ $user->last_name }}">
        <label for="form_last_name">{{ lng('last_name') }}</label>
    </div>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="first_name" id="form_first_name" required value="{{ $user->first_name }}">
        <label for="form_first_name">{{ lng('first_name') }}</label>
    </div>
    <div class="form-group label-inside">
        <input type="text" class="form-control form-control-lg" name="middle_name" id="form_middle_name" required value="{{ $user->middle_name }}">
        <label for="form_middle_name">{{ lng('middle_name') }}</label>
    </div>
    <span class="<?=App\Common::BTN?> btn-primary btn-lg" onclick="submitForm($(this), submitSettingsForm)">{{ lng('btn.send') }}</span>
</form>

<script>
    function submitSettingsForm(btn)
    {
        var form = btn.closest('.form');
        form.find('.is-invalid').removeClass('is-invalid');

        $.post('/user/settings', form.serialize(), function (data) {
            endSubmitForm();

            if (data.success) {
                message(true, data.data);
            } else {
                errorMessages(data, form);
            }
        });
    }
</script>