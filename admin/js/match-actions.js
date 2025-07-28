/**
 * Gestion des actions liées aux matchs (score, buts, cartons)
 * Ce fichier doit être inclus après jQuery et Bootstrap
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Gestion de la soumission des formulaires AJAX
    $(document).on('submit', 'form[data-ajax="true"]', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalBtnText = $submitBtn.html();
        
        // Désactiver le bouton et afficher un indicateur de chargement
        $submitBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Envoi...'
        );
        
        // Envoyer la requête AJAX
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                // Afficher le message de succès
                if (response.success) {
                    showAlert('success', response.message);
                    
                    // Rediriger si nécessaire
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                        return;
                    }
                    
                    // Recharger la page si nécessaire
                    if (response.reload) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                        return;
                    }
                    
                    // Fermer la modale si elle est ouverte
                    var modal = bootstrap.Modal.getInstance($form.closest('.modal')[0]);
                    if (modal) {
                        modal.hide();
                    }
                    
                } else {
                    showAlert('danger', response.message || 'Une erreur est survenue.');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Erreur de connexion au serveur.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Utiliser le message d'erreur par défaut
                }
                showAlert('danger', errorMessage);
            },
            complete: function() {
                // Réactiver le bouton et restaurer le texte original
                $submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
    
    // Gestion des modales
    $('.modal').on('shown.bs.modal', function() {
        // Mettre le focus sur le premier champ de formulaire
        var $input = $(this).find('input[type!="hidden"], select, textarea').first();
        if ($input.length) {
            setTimeout(function() {
                $input.focus();
            }, 500);
        }
    });
    
    // Validation des formulaires
    $('form.needs-validation').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Initialisation des sélecteurs de temps
    initTimeInputs();
});

/**
 * Affiche une alerte
 * @param {string} type Type d'alerte (success, danger, warning, info)
 * @param {string} message Message à afficher
 * @param {number} [timeout=5000] Délai avant disparition en ms (0 = pas de disparition)
 */
function showAlert(type, message, timeout = 5000) {
    // Supprimer les anciennes alertes après 1 seconde
    $('.alert-dismissible').fadeOut(1000, function() {
        $(this).remove();
    });
    
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    `;
    
    // Ajouter l'alerte en haut de la page
    $('main').prepend(alertHtml);
    
    // Faire défiler jusqu'à l'alerte
    $('html, body').animate({
        scrollTop: 0
    }, 500);
    
    // Cacher l'alerte après le délai spécifié
    if (timeout > 0) {
        setTimeout(function() {
            $('.alert-dismissible').fadeOut(500, function() {
                $(this).remove();
            });
        }, timeout);
    }
}

/**
 * Initialise les champs de saisie de temps
 */
function initTimeInputs() {
    // Validation des champs de minute
    $('input[name="minute"]').on('input', function() {
        var value = parseInt($(this).val(), 10);
        if (isNaN(value) || value < 1 || value > 120) {
            this.setCustomValidity('La minute doit être comprise entre 1 et 120');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Validation des champs de score
    $('input[name^="score_"]').on('input', function() {
        var value = parseInt($(this).val(), 10);
        if (isNaN(value) || value < 0 || value > 50) {
            this.setCustomValidity('Le score doit être compris entre 0 et 50');
        } else {
            this.setCustomValidity('');
        }
    });
}

/**
 * Met à jour le minuteur du match
 * @param {number} matchId ID du match
 * @param {string} status Statut du minuteur (running, paused, ended)
 * @param {function} [callback] Fonction de rappel
 */
function updateMatchTimer(matchId, status, callback) {
    $.ajax({
        url: '../api/update_timer.php',
        type: 'POST',
        data: {
            match_id: matchId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            } else {
                showAlert('danger', response.message || 'Erreur lors de la mise à jour du minuteur');
            }
        },
        error: function() {
            showAlert('danger', 'Erreur de connexion au serveur');
        }
    });
}

// Exposer les fonctions au scope global
window.matchActions = {
    showAlert: showAlert,
    updateMatchTimer: updateMatchTimer
};
