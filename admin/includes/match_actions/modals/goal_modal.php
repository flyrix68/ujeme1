<?php
/**
 * Génère le HTML de la modale d'ajout de but
 * @param int $matchId ID du match
 * @param string $homeTeam Nom de l'équipe à domicile
 * @param string $awayTeam Nom de l'équipe à l'extérieur
 * @return string HTML de la modale
 */
function renderGoalModal($matchId, $homeTeam, $awayTeam) {
    $escapedHomeTeam = htmlspecialchars($homeTeam);
    $escapedAwayTeam = htmlspecialchars($awayTeam);
    
    // Récupérer la minute actuelle du match (pour pré-remplir le champ)
    $currentMinute = '';
    if (isset($_SESSION['match_timer'][$matchId])) {
        $elapsed = (int)$_SESSION['match_timer'][$matchId];
        $currentMinute = max(1, min(120, floor($elapsed / 60))); // Entre 1 et 120 minutes
    }
    
    return <<<HTML
    <div class="modal fade" id="goalModal-{$matchId}" tabindex="-1" aria-labelledby="goalModalLabel-{$matchId}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="goalModalLabel-{$matchId}">
                        <i class="fas fa-futbol me-2"></i>Ajouter un but
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form action="includes/match_actions/actions/add_goal.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="match_id" value="{$matchId}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="team_{$matchId}" class="form-label">Équipe</label>
                            <select class="form-select" id="team_{$matchId}" name="team" required>
                                <option value="">Sélectionner une équipe</option>
                                <option value="home">{$escapedHomeTeam} (Domicile)</option>
                                <option value="away">{$escapedAwayTeam} (Extérieur)</option>
                            </select>
                            <div class="invalid-feedback">
                                Veuillez sélectionner une équipe
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="player_{$matchId}" class="form-label">Buteur</label>
                            <input type="text" class="form-control" id="player_{$matchId}" 
                                   name="player" required placeholder="Nom du buteur">
                            <div class="invalid-feedback">
                                Veuillez saisir le nom du buteur
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="minute_{$matchId}" class="form-label">Minute</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="minute_{$matchId}" 
                                       name="minute" min="1" max="120" value="{$currentMinute}" required>
                                <span class="input-group-text">'</span>
                            </div>
                            <div class="form-text">Minute du but (1-120)</div>
                            <div class="invalid-feedback">
                                Veuillez saisir une minute valide (1-120)
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="own_goal_{$matchId}" name="own_goal" value="1">
                                <label class="form-check-label" for="own_goal_{$matchId}">
                                    But contre son camp
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="penalty_{$matchId}" name="penalty" value="1">
                                <label class="form-check-label" for="penalty_{$matchId}">
                                    Pénatly
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus-circle me-1"></i> Ajouter le but
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
        var form = document.querySelector('form[action*="add_goal.php"]');
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
        }
    })();
    </script>
    HTML;
}
?>
