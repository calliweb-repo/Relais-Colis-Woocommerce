jQuery(document).ready(function ($) {

    console.log('RC Field Grilles Tarifaires init');

    'use strict';

    let grilleIndex = $("#grilles-container .grille-container").length;

    // Add a new grid
    $("#add-grille").click(function () {
        const newGrille = rc_templates.grille_template;
        $("#grilles-container").append(newGrille.replace(/__INDEX__/g, grilleIndex));
        grilleIndex++;
    });

    // Add a new row
    $(document).on("click", ".add-line", function () {
        const parent = $(this).closest(".grille-container");
        const grilleIndex = parent.data("index");
        const lineIndex = parent.find(".line-row").length;
        const newLine = rc_templates.line_template;
        parent.find(".lines-container").append(
            newLine.replace(/__GRILLE_INDEX__/g, grilleIndex).replace(/__LINE_INDEX__/g, lineIndex)
        );
    });

    // Delete a row
    $(document).on("click", ".remove-line", function () {
        $(this).closest(".line-row").remove();
    });

    // Delete a grid
    $(document).on("click", ".remove-grille", function () {
        $(this).closest(".grille-container").remove();
    });
});