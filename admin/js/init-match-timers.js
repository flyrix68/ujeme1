/**
 * Initialisation des minuteurs de match sur le tableau de bord
 * Ce script initialise et gère les minuteurs pour tous les matchs en cours
 */

document.addEventListener('DOMContentLoaded', function() {
    // Dictionnaire pour stocker les instances de minuteurs
    const matchTimers = {};
    
    // Sélectionner tous les conteneurs de minuteurs
    const timerContainers = document.querySelectorAll('[data-match-timer]');
    
    // Initialiser chaque minuteur
    timerContainers.forEach(container => {
        const matchId = container.getAttribute('data-match-id');
        
        // Créer un élément pour le minuteur s'il n'existe pas
        let timerElement = container.querySelector('.match-timer-display');
        if (!timerElement) {
            timerElement = document.createElement('div');
            timerElement.className = 'match-timer-display';
            container.appendChild(timerElement);
        }
        
        // Initialiser le minuteur
        matchTimers[matchId] = new MatchTimer(timerElement, matchId);
    });
    
    // Fonction pour mettre à jour tous les minuteurs
    function updateAllTimers() {
        fetch('/admin/api/get_ongoing_matches.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.matches) {
                    data.matches.forEach(match => {
                        const timer = matchTimers[match.id];
                        if (timer) {
                            timer.init(
                                match.timer_start,
                                match.timer_elapsed,
                                match.match_duration,
                                match.timer_status
                            );
                            
                            // Mettre à jour le score si nécessaire
                            const scoreElement = document.querySelector(`[data-match-id="${match.id}"] .match-score`);
                            if (scoreElement) {
                                scoreElement.textContent = `${match.score_home} - ${match.score_away}`;
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Erreur lors de la mise à jour des minuteurs:', error));
    }
    
    // Mettre à jour immédiatement et toutes les 10 secondes
    updateAllTimers();
    setInterval(updateAllTimers, 10000);
    
    // Exposer les minuteurs pour un accès global si nécessaire
    window.matchTimers = matchTimers;
});
