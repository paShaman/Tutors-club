@extends('layouts.modal')

@section('modal-body')

    <form class="form" novalidate>
        <div class="form-group label-inside">
            <input type="text" class="form-control form-control-lg" name="student_name" id="form_student_name" required>
            <label for="form_student_name">{{ lng('student_name') }}</label>
        </div>
        <div class="form-group label-inside mb-0">
            <textarea class="form-control form-control-lg" name="student_description" id="form_student_description" required></textarea>
            <label for="form_student_description">{{ lng('student_description') }}</label>
        </div>
    </form>

    <script>
        function submitAddStudentForm(btn)
        {
            var form = btn.closest('.modal').find('.form');

            form.find('.is-invalid').removeClass('is-invalid');

            $.post('/student/add', form.serialize(), function (data) {
                endSubmitForm();

                if (data.success) {
                    message(true, data.data);

                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                } else {
                    errorMessages(data, form);
                }
            });
        }
    </script>

@endsection

@section('modal-button')
    <button type="button" class="btn btn-primary" onclick="submitForm($(this), submitAddStudentForm)">{{ lng('btn.add') }}</button>
@endsection