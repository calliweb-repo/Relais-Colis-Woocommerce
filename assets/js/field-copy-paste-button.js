jQuery(document).ready(function ($) {
    console.log('RC Field Copy Paste Button initialized');

    'use strict';

    // Retrieve the IDs from rc_params
    const copyPasteButtonId = rc_params.copy_paste_button_css_id;
    const infoTextAreaId = rc_params.info_text_area_css_id;

    // Ensure both IDs are defined
    if (!copyPasteButtonId || !infoTextAreaId) {
        console.error('Copy Paste Button or Text Area ID is missing.');
        return;
    }

    // Add a click event listener to the copy button
    $('#' + copyPasteButtonId).on('click', function () {
        const $textarea = $('#' + infoTextAreaId);

        // Ensure the textarea exists
        if ($textarea.length === 0) {
            console.error('Text Area with ID ' + infoTextAreaId + ' not found.');
            return;
        }

        // Select the content of the textarea
        $textarea.show(); // Temporarily show it to select the text
        $textarea.select();

        // Copy the selected content to the clipboard
        const successful = document.execCommand('copy');
        $textarea.hide(); // Hide it again after copying

        // Provide feedback to the user
        const $button = $('#' + copyPasteButtonId);
        const originalText = $button.text();

        if (successful) {
            $button.text(rc_params.copied_label);
            setTimeout(() => {
                $button.text(originalText);
            }, 2000);
        } else {
            console.error('Failed to copy text.');
            $button.text(rc_params.copy_failed_label);
            setTimeout(() => {
                $button.text(originalText);
            }, 2000);
        }
    });
});