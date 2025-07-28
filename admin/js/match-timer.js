/**
 * Script pour la mise à jour en temps réel du minuteur du match
 */

document.addEventListener('DOMContentLoaded', function() {
    const matchTimer = document.getElementById('match-timer');
    if (!matchTimer) return;
    
    // Fonction pour formater le temps en minutes:secondes
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Fonction pour mettre à jour le minuteur
    function updateTimer() {
        const matchId = new URLSearchParams(window.location.search).get('id');
        if (!matchId) return;
        
        // Appel AJAX pour récupérer les données mises à jour du minuteur
        fetch(`/ujem/api/update_timer.php?match_id=${matchId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau lors de la récupération des données du minuteur');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage du minuteur
                    matchTimer.textContent = `${data.display_time} / ${data.total_duration}`;
                    
                    // Mettre à jour le statut du match
                    const matchStatus = document.querySelector('.match-status');
                    if (matchStatus) {
                        matchStatus.textContent = data.is_ongoing ? 'Match en cours' : 'Match terminé';
                    }
                    
                    // Ajouter/supprimer la classe 'ongoing' pour l'animation
                    if (data.is_ongoing) {
                        matchTimer.classList.add('ongoing');
                    } else {
                        matchTimer.classList.remove('ongoing');
                    }
                    
                    // Mettre à jour le score si nécessaire
                    const scoreElement = document.querySelector('.score');
                    if (scoreElement && data.score_home !== undefined && data.score_away !== undefined) {
                        scoreElement.textContent = `${data.score_home} - ${data.score_away}`;
                    }
                } else {
                    console.error('Erreur lors de la mise à jour du minuteur:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour du minuteur:', error);
            });
    }
    
    // Mettre à jour le minuteur immédiatement, puis toutes les secondes
    updateTimer();
    setInterval(updateTimer, 1000);
});
