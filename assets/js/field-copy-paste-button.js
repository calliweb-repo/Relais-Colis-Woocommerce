jQuery(document).ready(function ($) {
    console.log('RC Field Copy Paste Button initialized');

    'use strict';

    // Retrieve the IDs from rc_params
    const copyPasteButtonClass = rc_params.copy_paste_button_css_class;
    const infoTextAreaClass = rc_params.info_text_area_css_class;

    // Ensure both IDs are defined
    if (!copyPasteButtonClass || !infoTextAreaClass) {
        console.error('Copy Paste Button or Text Area class is missing.');
        return;
    }

    // Add a click event listener to the copy buttons
    $(document).on('click', '.copy_paste_button', function () {
        const $button = $(this);
        const $textarea = $button.prev('.info_text_area');

        // Check if textarea exists
        if ($textarea.length === 0) {
            console.error('Aucun textarea associé trouvé.');
            return;
        }

        // Rendre le textarea temporairement visible pour la sélection
        $textarea.show();
        $textarea.select();

        // Copier le texte dans le presse-papiers
        try {
            const successful = document.execCommand('copy');
            $textarea.hide(); // Cacher de nouveau après la copie

            // Changer temporairement le texte du bouton pour montrer que la copie a réussi
            const originalText = $button.text();
            if (successful) {
                $button.text(rc_params.copied_label);
                setTimeout(() => {
                    $button.text(originalText);
                }, 2000);
            } else {
                throw new Error('Échec de la copie');
            }
        } catch (err) {
            console.error('Erreur lors de la copie du texte :', err);
            $button.text(rc_params.copy_failed_label);
            setTimeout(() => {
                $button.text(originalText);
            }, 2000);
        }
    });
});