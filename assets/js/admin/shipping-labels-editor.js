/**
 * Script pour l'édition des étiquettes d'expédition Relais Colis
 */
jQuery(document).ready(function($) {
    console.log('shipping-labels-editor.js loaded');
    // Gérer le clic sur le lien d'édition
    $(document).on('click', '.edit-shipping-labels', function(e) {
        e.preventDefault();
        console.log('edit-shipping-labels clicked');
        var $this = $(this);
        var $container = $this.closest('.rc-tracking-links-info');
        
        // Récupérer l'ID de commande
        var orderId = $container.data('order-id');
        if (!orderId) {
            orderId = window.location.href.match(/post=(\d+)|id=(\d+)/)[1] || window.location.href.match(/post=(\d+)|id=(\d+)/)[2];
        }
        
        // Ajouter un bouton de validation globale s'il n'existe pas déjà
        if ($container.find('.save-all-labels').length === 0) {
            var $globalActions = $('<div class="global-actions" style="margin-top: 15px; text-align: right;"></div>');
            var $saveAllBtn = $('<button class="button button-primary save-all-labels">Valider toutes les modifications</button>');
            var $cancelAllBtn = $('<button class="button cancel-all-edits" style="margin-left: 5px;">Annuler tout</button>');
            $globalActions.append($saveAllBtn).append($cancelAllBtn);
            $container.append($globalActions);
        }
        
        // Transformer les liens en champs éditables
        $container.find('ul li').each(function() {
            var $listItem = $(this);
            var $link = $listItem.find('a');
            
            // Ne pas créer un champ d'édition s'il existe déjà
            if ($listItem.find('.edit-field').length > 0) {
                return;
            }
            
            var currentText = $link.text();
            var currentUrl = $link.attr('href');
            // Extraire juste le numéro d'étiquette sans le mot "Package" ou "Colis"
            var packageNumber = $link.find('span').text().trim();
            
            // Créer l'élément d'édition sans les boutons individuels
            // var $editField = $('<div class="edit-field" style="margin-top: 5px;"></div>');
            var $input = $('<input class="edit-field" type="text" style="width: 150px;" value="' + packageNumber + '">');
            
            // $editField.append($input);
            
            // Stocker les valeurs originales pour pouvoir annuler
            $link.data('original-text', currentText);
            $link.data('original-url', currentUrl);
            $link.data('package-number', packageNumber);
            
            // Masquer le lien et ajouter le champ d'édition
            $link.hide();
            $listItem.append($input);
        });
        
        // Cacher le lien d'édition
        $this.hide();
    });
    
    // Gérer l'annulation de toutes les éditions
    $(document).on('click', '.cancel-all-edits', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.rc-tracking-links-info');
        
        // Afficher à nouveau tous les liens
        $container.find('ul li a').show();
        
        // Supprimer tous les champs d'édition
        $container.find('.edit-field').remove();
        
        // Supprimer les actions globales
        $container.find('.global-actions').remove();
        
        // Afficher à nouveau le lien d'édition
        $container.find('.edit-shipping-labels').show();
    });
    
    // Gérer le clic sur le bouton de sauvegarde globale
    $(document).on('click', '.save-all-labels', function(e) {
        e.preventDefault();

        var $container = $(this).closest('.rc-tracking-links-info');
        var $editFields = $container.find('.edit-field');

        if ($editFields.length === 0) {
            alert('Aucune modification à enregistrer');
            return;
        }

        var changes = [];
        $editFields.each(function() {
            var $editField = $(this);
            var $listItem = $editField.closest('li');
            var $link = $listItem.find('a');
            var newLabel = $editField.val();
            var oldLabel = $link.data('package-number');
            var orderId = $container.data('order-id');
            if (!orderId) {
                orderId = window.location.href.match(/post=(\d+)|id=(\d+)/)[1] || window.location.href.match(/post=(\d+)|id=(\d+)/)[2];
            }
            if (newLabel !== '') {
                changes.push({
                    order_id: orderId,
                    old_label: oldLabel,
                    new_label: newLabel
                });
            }
        });

        if (changes.length === 0) {
            alert('Aucune modification à enregistrer');
            return;
        }

        console.log(changes);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_shipping_label',
                changes: JSON.stringify(changes),
                security: rc_shipping_labels_editor.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Toutes les étiquettes ont été mises à jour avec succès');
                    // Met à jour l'UI comme avant
                    window.location.reload();
                } else {
                    alert('Erreur(s) rencontrée(s):\n' + (response.data ? response.data.message : 'Erreur inconnue'));
                }
            },
            error: function() {
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    });
}); 