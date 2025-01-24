jQuery(document).ready(function ($) {
    console.log('RC Field Action Buttons init');

    'use strict';

    $("#rc-refresh-info").click(function () {
        $.ajax({
            url: rc_ajax.ajax_url, // Passé depuis wp_localize_script
            method: "POST",
            data: {
                action: "rc_refresh_client_info",
                security: rc_ajax.nonce // Nonce dédié pour AJAX
            },
            success: function (response) {
                if (response.success) {
                    let data = response.data;
                    let infoHtml = `<strong>${rc_ajax.nom_label} :</strong> ${data.nom}<br>
                                    <strong>${rc_ajax.prenom_label} :</strong> ${data.prenom}<br>
                                    <strong>${rc_ajax.solde_label} :</strong> ${data.solde}`;
                    $("#rc-client-info").html(infoHtml);
                } else {
                    alert(response.data.message || "Une erreur est survenue.");
                }
            },
            error: function () {
                alert("Erreur de connexion avec l'API.");
            }
        });
    });

    $("#rc-extract-info").click(function () {
        $.ajax({
            url: rc_ajax.ajax_url,
            method: "POST",
            data: {
                action: "rc_extract_client_info",
                security: rc_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    let data = response.data;
                    alert(`Nom : ${data.nom}\nPrénom : ${data.prenom}\nSolde : ${data.solde}`);
                } else {
                    alert(response.data.message || "Une erreur est survenue.");
                }
            },
            error: function () {
                alert("Erreur de connexion avec l'API.");
            }
        });
    });
});