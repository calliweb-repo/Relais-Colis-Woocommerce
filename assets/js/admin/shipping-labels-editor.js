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
        
        // On crée un compteur pour suivre l'avancement
        var totalChanges = $editFields.length;
        var successCount = 0;
        var errors = [];
        
        // On enregistre chaque modification séquentiellement
        $editFields.each(function(index) {
            var $editField = $(this);
            var $listItem = $editField.closest('li');
            var $link = $listItem.find('a');
            var newLabel = $editField.val();
            
            
            if (newLabel === '') {
                errors.push('Étiquette vide trouvée - ignorée');
                if (successCount + errors.length === totalChanges) {
                    showCompletionMessage($container, successCount, errors);
                }
                return;
            }
            
            saveLabel($link, newLabel, $editField, function(success, message) {
                if (success) {
                    successCount++;
                } else {
                    errors.push(message);
                }
                
                // Quand toutes les modifications sont traitées
                if (successCount + errors.length === totalChanges) {
                    showCompletionMessage($container, successCount, errors);
                }
            });
        });
    });
    
    // Fonction pour afficher un message de complétion
    function showCompletionMessage($container, successCount, errors) {
        if (errors.length === 0) {
            alert('Toutes les étiquettes ont été mises à jour avec succès');
        } else {
            var message = successCount + ' étiquette(s) mise(s) à jour avec succès.\n';
            message += errors.length + ' erreur(s) rencontrée(s):\n' + errors.join('\n');
            alert(message);
        }
        
        // Supprimer les actions globales
        $container.find('.global-actions').remove();
        
        // Supprimer tous les champs d'édition restants
        $container.find('.edit-field').remove();
        
        // Afficher à nouveau tous les liens
        $container.find('ul li a').show();
        
        // Afficher à nouveau le lien d'édition
        $container.find('.edit-shipping-labels').show();
    }
    
    // Fonction pour sauvegarder une étiquette
    function saveLabel($link, newLabel, $editField, callback) {
        // Récupérer l'ID de commande
        var $container = $link.closest('.rc-tracking-links-info');
        var orderId = $container.data('order-id');
        if (!orderId) {
            orderId = window.location.href.match(/post=(\d+)|id=(\d+)/)[1] || window.location.href.match(/post=(\d+)|id=(\d+)/)[2];
        }
        
        // Récupérer l'ancien numéro d'étiquette
        var oldLabel = $link.data('package-number');
        
        // Effectuer la requête AJAX pour mettre à jour l'étiquette
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_shipping_label',
                order_id: orderId,
                old_label: oldLabel,
                new_label: newLabel,
                security: rc_shipping_labels_editor.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le texte du lien
                    $link.find('span').text(newLabel);
                    $link.attr('href', 'https://service.relaiscolis.com/wssuivicoliscritere/PageSuivi.aspx?Ref=' + newLabel);
                    $link.data('package-number', newLabel);
                    
                    // Afficher le lien mis à jour
                    $link.show();
                    
                    // Supprimer le champ d'édition
                    $editField.remove();
                    
                    if (callback) {
                        callback(true);
                    }
                } else {
                    if (callback) {
                        callback(false, 'Erreur pour l\'étiquette ' + oldLabel + ': ' + (response.data ? response.data.message : 'Erreur inconnue'));
                    }
                }
            },
            error: function() {
                var errorMsg = 'Erreur lors de la communication avec le serveur pour l\'étiquette ' + oldLabel;
                if (callback) {
                    callback(false, errorMsg);
                }
            }
        });
    }
}); 