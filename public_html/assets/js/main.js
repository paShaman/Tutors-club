var localization = {};

$(function () {
    
});

function message(type, message, sticky)
{
    var theme = 'default';
    var header = '';

    if (type == true || type ==  1 || type == 'success') {
        theme = 'success';
        header = lng('success');
    }

    if (type == false || type ==  0 || type == 'error') {
        theme = 'error';
        header = lng('error');
    }

    if (type == 'warning') {
        theme = 'warning';
        header = lng('warning');
    }

    if (type == 'info') {
        theme = 'info';
    }

    $.jGrowl(message, { header: header, sticky: sticky === true ? true : false, theme: theme });
}

function lng(word)
{
    return localization[word] ? localization[word] : '';
}