var localization = {};

if (typeof Dropzone == 'function') {
    Dropzone.autoDiscover = false;
}

$(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).ajaxSuccess(function( event, xhr, settings) {
        console.log(xhr.responseJSON, xhr.responseJSON.messages, xhr.responseJSON.messages.length);
        if (xhr.responseJSON && xhr.responseJSON.messages && xhr.responseJSON.messages.length) {
            console.log(1);
            for (var i in xhr.responseJSON.messages) {
                message(xhr.responseJSON.messages[i].type, xhr.responseJSON.messages[i].text);
            }
        }
    });

});