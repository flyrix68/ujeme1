<?php
/**
 * Génère le HTML de la modale d'ajout de carton
 * @param int $matchId ID du match
 * @param string $homeTeam Nom de l'équipe à domicile
 * @param string $awayTeam Nom de l'équipe à l'extérieur
 * @return string HTML de la modale
 */
function renderCardModal($matchId, $homeTeam, $awayTeam) {
    $escapedHomeTeam = htmlspecialchars($homeTeam);
    $escapedAwayTeam = htmlspecialchars($awayTeam);
    
    // Récupérer la minute actuelle du match (pour pré-remplir le champ)
    $currentMinute = '';
    if (isset($_SESSION['match_timer'][$matchId])) {
        $elapsed = (int)$_SESSION['match_timer'][$matchId];
        $currentMinute = max(1, min(120, floor($elapsed / 60))); // Entre 1 et 120 minutes
    }
    
    return <<<HTML
    <div class="modal fade" id="cardModal-{$matchId}" tabindex="-1" aria-labelledby="cardModalLabel-{$matchId}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="cardModalLabel-{$matchId}">
                        <i class="fas fa-exclamation-triangle me-2"></i>Ajouter un carton
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form action="includes/match_actions/actions/add_card.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="match_id" value="{$matchId}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="card_team_{$matchId}" class="form-label">Équipe</label>
                            <select class="form-select" id="card_team_{$matchId}" name="team" required>
                                <option value="">Sélectionner une équipe</option>
                                <option value="home">{$escapedHomeTeam} (Domicile)</option>
                                <option value="away">{$escapedAwayTeam} (Extérieur)</option>
                            </select>
                            <div class="invalid-feedback">
                                Veuillez sélectionner une équipe
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="card_player_{$matchId}" class="form-label">Joueur</label>
                            <input type="text" class="form-control" id="card_player_{$matchId}" 
                                   name="player" required placeholder="Nom du joueur">
                            <div class="invalid-feedback">
                                Veuillez saisir le nom du joueur
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type de carton</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="card_type" 
                                           id="yellow_{$matchId}" value="yellow" required checked>
                                    <label class="form-check-label" for="yellow_{$matchId}">
                                        <i class="fas fa-square text-warning"></i> Jaune
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="card_type" 
                                           id="red_{$matchId}" value="red" required>
                                    <label class="form-check-label" for="red_{$matchId}">
                                        <i class="fas fa-square text-danger"></i> Rouge
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="card_type" 
                                           id="blue_{$matchId}" value="blue" required>
                                    <label class="form-check-label" for="blue_{$matchId}">
                                        <i class="fas fa-square text-primary"></i> Bleu (Maracana)
                                    </label>
                                </div>
                                <div class="invalid-feedback">
                                    Veuillez sélectionner un type de carton
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="card_minute_{$matchId}" class="form-label">Minute</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="card_minute_{$matchId}" 
                                           name="minute" min="1" max="120" value="{$currentMinute}" required>
                                    <span class="input-group-text">'</span>
                                </div>
                                <div class="form-text">Minute du carton (1-120)</div>
                                <div class="invalid-feedback">
                                    Veuillez saisir une minute valide (1-120)
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="card_reason_{$matchId}" class="form-label">Raison (optionnel)</label>
                            <textarea class="form-control" id="card_reason_{$matchId}" 
                                      name="reason" rows="2" placeholder="Faute, simulation, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-warning text-dark">
                            <i class="fas fa-plus-circle me-1"></i> Ajouter le carton
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Validation du formulaire
    (function() {
        'use strict';
        var form = document.querySelector('form[action*="add_card.php"]');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
            
            // Validation personnalisée pour la minute
            var minuteInput = form.querySelector('input[name="minute"]');
            if (minuteInput) {
                minuteInput.addEventListener('input', function() {
                    var value = parseInt(this.value, 10);
                    if (isNaN(value) || value < 1 || value > 120) {
                        this.setCustomValidity('La minute doit être entre 1 et 120');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            // Mise à jour de la couleur du bouton en fonction du type de carton
            var yellowRadio = form.querySelector('#yellow_{$matchId}');
            var redRadio = form.querySelector('#red_{$matchId}');
            var submitBtn = form.querySelector('button[type="submit"]');
            
            function updateButtonColor() {
                if (redRadio.checked) {
                    submitBtn.classList.remove('btn-warning');
                    submitBtn.classList.add('btn-danger');
                } else {
                    submitBtn.classList.remove('btn-danger');
                    submitBtn.classList.add('btn-warning');
                }
            }
            
            if (yellowRadio && redRadio && submitBtn) {
                yellowRadio.addEventListener('change', updateButtonColor);
                redRadio.addEventListener('change', updateButtonColor);
                updateButtonColor(); // Initialisation
            }
        }
    })();
    </script>
    HTML;
}
?>
