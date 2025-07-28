<?php
/**
 * Génère le HTML de la modale de mise à jour du score
 * @param int $matchId ID du match
 * @param int $currentHomeScore Score actuel de l'équipe à domicile
 * @param int $currentAwayScore Score actuel de l'équipe à l'extérieur
 * @return string HTML de la modale
 */
function renderScoreModal($matchId, $currentHomeScore, $currentAwayScore) {
    $homeScore = (int)$currentHomeScore;
    $awayScore = (int)$currentAwayScore;
    
    return <<<HTML
    <div class="modal fade" id="scoreModal-{$matchId}" tabindex="-1" aria-labelledby="scoreModalLabel-{$matchId}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="scoreModalLabel-{$matchId}">
                        <i class="fas fa-futbol me-2"></i>Mettre à jour le score
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form action="includes/match_actions/actions/update_score.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="match_id" value="{$matchId}">
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-4 text-center">
                                <div class="form-group">
                                    <label for="score_home_{$matchId}" class="form-label">Domicile</label>
                                    <input type="number" class="form-control form-control-lg text-center" 
                                           id="score_home_{$matchId}" name="score_home" 
                                           value="{$homeScore}" min="0" max="50" required>
                                    <div class="invalid-feedback">
                                        Veuillez entrer un score valide (0-50)
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 text-center">
                                <span class="h3">-</span>
                            </div>
                            <div class="col-4 text-center">
                                <div class="form-group">
                                    <label for="score_away_{$matchId}" class="form-label">Extérieur</label>
                                    <input type="number" class="form-control form-control-lg text-center" 
                                           id="score_away_{$matchId}" name="score_away" 
                                           value="{$awayScore}" min="0" max="50" required>
                                    <div class="invalid-feedback">
                                        Veuillez entrer un score valide (0-50)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Enregistrer
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
        var form = document.querySelector('form[action*="update_score.php"]');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }
    })();
    </script>
    HTML;
}
?>
