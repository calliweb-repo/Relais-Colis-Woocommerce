jQuery(document).ready(function ($) {

    console.log('RC Field Enable Toggle init');

    "use strict";

    $('.rc_enable_checkbox').on('click', function () {
        let checkBox = $(this).find('input[type="checkbox"]');
        let hiddenInput = $(this).find('input[type="hidden"]');

        // Vérifie si l'élément est désactivé
        if (checkBox.prop('disabled') || $(this).prop('disabled')) {
            return; // Ne pas modifier si désactivé
        }

        // Bascule l'état actif uniquement pour cet élément
        $(this).toggleClass('active');

        let isChecked = $(this).hasClass('active');

        // Met à jour uniquement les inputs de cet élément
        checkBox.prop('checked', isChecked);
        hiddenInput.val(isChecked ? 'yes' : 'no');

        // Déclenche l'événement change uniquement pour cet élément
        checkBox.trigger('change');
    });
});