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

    $.jGrowl(message, { header: header, sticky: sticky ? true : false, theme: theme });
}

function errorMessages(data, form)
{
    if (data.data) {
        if (typeof data.data == 'string') {
            message(false, data.data);
        } else {
            for (var i in data.data) {
                message(false, data.data[i]);

                if (form) {
                    $('[name=' + i + ']', form).addClass('is-invalid');
                }
            }
        }
    }
}

function lng(word)
{
    return localization[word] ? localization[word] : '';
}

/*
 *  webpush start
 */

function onManageWebPushSubscriptionButtonClicked(event) {
    getSubscriptionState().then(function(state) {
        if (state.isPushEnabled) {
            /* Subscribed, opt them out */
            OneSignal.setSubscription(false);
        } else {
            if (state.isOptedOut) {
                /* Opted out, opt them back in */
                OneSignal.setSubscription(true);
            } else {
                /* Unsubscribed, subscribe them */
                OneSignal.registerForPushNotifications();
            }
        }
    });
    event.preventDefault();
}

function updateMangeWebPushSubscriptionButton(buttonSelector) {
    var hideWhenSubscribed = false;
    var subscribeText = lng('push_enable');
    var unsubscribeText = lng('push_disable');

    getSubscriptionState().then(function(state) {
        var buttonText = !state.isPushEnabled || state.isOptedOut ? subscribeText : unsubscribeText;

        var element = document.querySelector(buttonSelector);
        if (element === null) {
            return;
        }

        element.removeEventListener('click', onManageWebPushSubscriptionButtonClicked);
        element.addEventListener('click', onManageWebPushSubscriptionButtonClicked);
        element.textContent = buttonText;

        if (state.hideWhenSubscribed && state.isPushEnabled) {
            element.style.display = "none";
        } else {
            element.style.display = "";
        }
    });
}

function getSubscriptionState() {
    return Promise.all([
        OneSignal.isPushNotificationsEnabled(),
        OneSignal.isOptedOut()
    ]).then(function(result) {
        var isPushEnabled = result[0];
        var isOptedOut = result[1];

        return {
            isPushEnabled: isPushEnabled,
            isOptedOut: isOptedOut
        };
    });
}

function webPushHandlerInit(webpushButtonSelector, OneSignalAppId) {
    OneSignal.push(function() {
        OneSignal.init({
            appId: OneSignalAppId,
            autoRegister: false,
        });

        // If we're on an unsupported browser, do nothing
        if (!OneSignal.isPushNotificationsSupported()) {
            return;
        }

        updateMangeWebPushSubscriptionButton(webpushButtonSelector);

        OneSignal.on("subscriptionChange", function(isSubscribed) {
            OneSignal.getUserId(function(userId) {
                if (isSubscribed) {
                    $.post('/sender/subscribe', {id: userId, type: 'webpush'});
                } else {
                    $.post('/sender/unsubscribe', {id: userId, type: 'webpush'});
                }
            });
            /* If the user's subscription state changes during the page's session, update the button text */
            updateMangeWebPushSubscriptionButton(webpushButtonSelector);
        });
    });
}

/*
 *  webpush end
 */

var submitFormInAction = false;

/**
 * функция предварительной обработки перед сабмитом формы
 *
 * @param btn
 * @param callback
 * @returns {boolean}
 */
function submitForm(btn, callback)
{
    if (submitFormInAction) {
        return false;
    }

    submitFormInAction = true;

    var modal = btn.closest('.modal-content');

    addLoader(modal);

    callback(btn);
}

/**
 * завершаем обработку формы
 */
function endSubmitForm()
{
    var modal = $('.modal-content.loading');

    removeLoader(modal);

    submitFormInAction = false;
}

/**
 * добавление прелоадера на блок
 *
 * @param block
 */
function addLoader(block)
{
    if (block.is(":empty")) {
        block.addClass('loading__min-width');
    } else {
        block.append('<div class="loading__voile" />');
    }
    block.addClass('loading').append('<svg class="spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"><circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle></svg>')
}

/**
 * убираем прелоадер с блока
 *
 * @param block
 */
function removeLoader(block)
{
    block.find('.spinner').remove();
    block.find('.loading__voile').remove();
    block.removeClass('loading loading__min-width');
}

/**
 * закрытие открытого модального окна
 */
function modalClose()
{
    var modal = $('.modal.show');

    if (modal.length) {
        $('#' + modal.attr('id')).modal('hide');
    }
}