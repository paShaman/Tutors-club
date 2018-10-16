@extends('layouts.modal')

@section('modal-body')

    <div class="form-group">
        <label>Пользователь</label>
        <input type="number" class="form-control" name="user_id">
    </div>

    <div class="form-group">
        <label>Сумма</label>
        <input type="number" class="form-control" name="amount">
    </div>

    <div class="form-group">
        <label>Причина добавления</label>
        <textarea class="form-control" name="reason"></textarea>
    </div>

    <script>
        function submitAddPaymentForm(btn)
        {
            var modal = btn.closest('.modal');

            var params = {
                user_id: $('[name=user_id]', modal).val(),
                amount: $('[name=amount]', modal).val(),
                reason: $('[name=reason]', modal).val().replace(/[\s]+/g, ' '),
            };

            if (!params.user_id || !params.amount || !params.reason) {
                message(false, 'Заполните все поля');
                endSubmitForm();
            }

            $.post('/admin/user/payment-add', params, function (data) {
                if (data.success) {
                    message(true, data.data);
                } else {
                    errorMessages(data);
                }

                endSubmitForm();
                modalClose();
            });
        }
    </script>

@endsection

@section('modal-button')
    <button type="button" class="btn btn-primary" onclick="submitForm($(this), submitAddPaymentForm)">{{ lng('btn.ok') }}</button>
@endsection