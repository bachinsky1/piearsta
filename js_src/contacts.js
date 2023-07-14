$(document).on('ready', function () {

    var $themeSelect = $('#theme_select'),
        $submit = $('#contactsForm .btn_cont a'),
        predefinedValue = false;

    $submit.addClass('disabled');

    // Using select plugin Selectric
    $themeSelect.selectric({
        arrowButtonMarkup: '<b class="button">&#x25b2;</b>'
    });

    $themeSelect.on('change', function () {

        var val = $(this).val(),
            $message = $('#message');

        $message.val('');

        if(val) {

            if(
                typeof profile.contactThemes &&
                typeof profile.contactThemes[val] !== 'undefined' &&
                profile.contactThemes[val].message
            ) {

                predefinedValue = true;
                $message.attr('disabled', true).val(profile.contactThemes[val].message).parent('.cinput').removeClass('disabled');
                $submit.addClass('disabled');

            } else {

                predefinedValue = false;
                $message.attr('disabled', false).parent('.cinput').removeClass('disabled');

                $message.off('keyup').on('keyup', function () {

                    if($message.val()) {
                        $submit.removeClass('disabled');
                    } else {
                        $submit.addClass('disabled');
                    }
                });
            }

        } else {

            predefinedValue = false;
            $message.attr('disabled', true).parent('.cinput').addClass('disabled');
            $submit.addClass('disabled');
        }

        if(!predefinedValue && val && $message.val()) {

            $submit.removeClass('disabled');

        } else {

            $submit.addClass('disabled');
        }

    });

    $submit.on('click', function () {

        if($(this).hasClass('disabled')) {
            return false;
        }

        $('#contactsForm').trigger('submit');
    });



});