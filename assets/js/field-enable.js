jQuery(document).ready(function ($) {

    console.log('RC Field Enable Checkbox init');

    "use strict";

    $('body').on('click', '.rc_enable_checkbox .button-secondary', function () {

        $(this).parent().find('.button').toggleClass('button-primary button-secondary');
        var checkBox = $(this).parents('.rc_enable_checkbox').find('input');

        if (checkBox.is(':checked')) {

            checkBox.removeAttr('checked');
        } else {

            checkBox.attr('checked', 'checked');
        }
        checkBox.trigger('change');
    });
});