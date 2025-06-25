jQuery(document).ready(function ($) {

    console.log('RC Field Prices Grid init');

    'use strict';

    let tariffIndex = 0;

    function buildDeliveryMethodOptions(selected = '') {
        return rc_ajax.available_offers.map(offer => {
            let isSelected = offer.value === selected ? 'selected' : '';
            return `<option value="${offer.value}" ${isSelected}>${offer.label}</option>`;
        }).join('');
    }

    // Charger les grilles existantes
    if (Object.keys(groupedTariffs).length > 0) {
        Object.entries(groupedTariffs).forEach(([key, tariff]) => {
            let newTariff = `
                <div class="tariff-box" data-index="${tariffIndex}">
                    <button type="button" class="remove-tariff"><i class="fas fa-trash-alt"></i></button>

                    <label>${rc_ajax.delivery_method_label}</label>
                    <select name="tariffs[${tariffIndex}][method_name]">
                        ${buildDeliveryMethodOptions(tariff.method_name)}
                    </select>

                    <label>${rc_ajax.criteria_label}</label>
                    <select name="tariffs[${tariffIndex}][criteria]">
                        <option value="price" ${tariff.criteria === 'price' ? 'selected' : ''}>${rc_ajax.total_price_label}</option>
                        <option value="weight" ${tariff.criteria === 'weight' ? 'selected' : ''}>${rc_ajax.weight_label}</option>
                    </select>

                    <label>${rc_ajax.shipping_threshold_label}</label>
                    <input type="number" name="tariffs[${tariffIndex}][shipping_threshold]" value="${tariff.shipping_threshold}" placeholder="${rc_ajax.shipping_threshold_label}" step="0.01">

                    <div class="lines-container">
                        <h4>${rc_ajax.tariff_ranges_label} - ${tariff.criteria === 'price' ? rc_ajax.total_price_label : rc_ajax.weight_label}</h4>
                        <button type="button" class="add-line"><i class="fas fa-plus"></i> ${rc_ajax.add_line_label}</button>
                        ${tariff.lines.map((line, lineIndex) => `
                            <div class="line-row">
                                <input type="number" name="tariffs[${tariffIndex}][lines][${lineIndex}][min]" value="${line.min_value}" placeholder="Min" step="0.001">
                                <input type="number" name="tariffs[${tariffIndex}][lines][${lineIndex}][max]" value="${line.max_value !== null ? line.max_value : ''}" placeholder="Max" step="0.001">
                                <input type="number" name="tariffs[${tariffIndex}][lines][${lineIndex}][price]" value="${line.price}" placeholder="Prix" step="0.01">
                                <button type="button" class="remove-line"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        `).join('')}
                    </div>
                </div>`;

            $("#tariffs-list").append(newTariff);
            tariffIndex++;
        });
    }

    // Ajouter une nouvelle grille tarifaire
    $("#add-tariff").click(function () {
        let newTariff = `
            <div class="tariff-box" data-index="${tariffIndex}">
                <button type="button" class="remove-tariff"><i class="fas fa-trash-alt"></i></button>
                
                <label>${rc_ajax.delivery_method_label}</label>
                <select name="tariffs[${tariffIndex}][method_name]">
                    ${buildDeliveryMethodOptions()}
                </select>
                
                <label>${rc_ajax.criteria_label}</label>
                <select name="tariffs[${tariffIndex}][criteria]">
                    <option value="price">${rc_ajax.total_price_label}</option>
                    <option value="weight">${rc_ajax.weight_label}</option>
                </select>

                <label>${rc_ajax.shipping_threshold_label}</label>
                <input type="number" name="tariffs[${tariffIndex}][shipping_threshold]" placeholder="${rc_ajax.shipping_threshold_label}" step="0.01">

                <div class="lines-container">
                    <h4>${rc_ajax.tariff_ranges_label}</h4>
                    <button type="button" class="add-line"><i class="fas fa-plus"></i> ${rc_ajax.add_line_label}</button>
                </div>
            </div>`;

        $("#tariffs-list").append(newTariff);
        tariffIndex++;
    });

    // Ajouter une ligne tarifaire dans une grille existante
    $(document).on("click", ".add-line", function () {
        let parentBox = $(this).closest(".tariff-box");
        let tariffIndex = parentBox.data("index");
        let lineIndex = parentBox.find(".line-row").length;

        let newLine = `
            <div class="line-row">
                <input type="number" name="tariffs[${tariffIndex}][lines][${lineIndex}][min]" placeholder="Min" step="0.001">
                <input type="number" name="tariffs[${tariffIndex}][lines][${lineIndex}][max]" placeholder="Max" step="0.001">
                <input type="number" name="tariffs[${tariffIndex}][lines][${lineIndex}][price]" placeholder="Prix" step="0.01">
                <button type="button" class="remove-line"><i class="fas fa-trash-alt"></i></button>
            </div>`;

        parentBox.find(".lines-container").append(newLine);
    });

    // Supprimer une ligne tarifaire
    $(document).on("click", ".remove-line", function () {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette ligne de tarif ?')) {
            $(this).closest(".line-row").remove();
        }
    });

    // Supprimer une grille tarifaire
    $(document).on("click", ".remove-tariff", function () {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette grille de tarif ?')) {
            $(this).closest(".tariff-box").remove();
        }
    });
});