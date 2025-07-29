/**
 * Script pour la mise à jour en temps réel du minuteur du match
 */

document.addEventListener('DOMContentLoaded', function() {
    const matchTimer = document.getElementById('match-timer');
    if (!matchTimer) return;
    
    let lastUpdate = 0;
    let localTimer = 0;
    let isRunning = false;
    let lastServerTime = 0;
    let lastServerTimestamp = 0;
    let updateInterval;
    
    // Fonction pour formater le temps en minutes:secondes
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Fonction pour mettre à jour l'affichage du minuteur
    function updateDisplay(seconds) {
        const totalDuration = matchTimer.getAttribute('data-duration') || '40:00';
        const displayTime = formatTime(seconds);
        matchTimer.textContent = `${displayTime} / ${totalDuration}`;
        
        // Mettre à jour le minuteur local
        localTimer = seconds;
    }
    
    // Fonction pour démarrer le minuteur local
    function startLocalTimer() {
        if (isRunning) return;
        isRunning = true;
        lastUpdate = Date.now();
        
        updateInterval = setInterval(() => {
            const now = Date.now();
            const elapsed = Math.floor((now - lastUpdate) / 1000);
            lastUpdate = now;
            
            // Mettre à jour le minuteur local
            localTimer += elapsed;
            updateDisplay(localTimer);
        }, 1000);
    }
    
    // Fonction pour arrêter le minuteur local
    function stopLocalTimer() {
        isRunning = false;
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }
    
    // Fonction pour synchroniser avec le serveur
    function syncWithServer() {
        const matchId = new URLSearchParams(window.location.search).get('id');
        if (!matchId) return;
        
        // Utiliser un timestamp pour éviter le cache
        const timestamp = new Date().getTime();
        
        fetch(`/ujem/api/update_timer.php?match_id=${matchId}&_=${timestamp}`, {
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau lors de la récupération des données du minuteur');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mettre à jour le statut du match
                const matchStatus = document.querySelector('.match-status');
                if (matchStatus) {
                    matchStatus.textContent = data.is_ongoing ? 'Match en cours' : 'Match terminé';
                }
                
                // Mettre à jour le score si nécessaire
                const scoreElement = document.querySelector('.score');
                if (scoreElement && data.score_home !== undefined && data.score_away !== undefined) {
                    scoreElement.textContent = `${data.score_home} - ${data.score_away}`;
                }
                
                // Si le minuteur du serveur a changé, mettre à jour le minuteur local
                if (data.timer_value !== lastServerTime) {
                    lastServerTime = data.timer_value;
                    lastServerTimestamp = Date.now();
                    localTimer = data.timer_value;
                    updateDisplay(localTimer);
                }
                
                // Gérer l'état du minuteur local
                if (data.is_ongoing) {
                    matchTimer.classList.add('ongoing');
                    startLocalTimer();
                } else {
                    matchTimer.classList.remove('ongoing');
                    stopLocalTimer();
                }
                
                // Mettre à jour la durée totale si nécessaire
                if (data.total_duration) {
                    matchTimer.setAttribute('data-duration', data.total_duration);
                }
            } else {
                console.error('Erreur lors de la mise à jour du minuteur:', data.error);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la synchronisation avec le serveur:', error);
            // En cas d'erreur, continuer avec le minuteur local
            if (isRunning) {
                startLocalTimer();
            }
        });
    }
    
    // Initialisation
    if (matchTimer) {
        // Récupérer la durée totale depuis l'attribut data
        const totalDuration = matchTimer.getAttribute('data-duration') || '40:00';
        matchTimer.setAttribute('data-duration', totalDuration);
        
        // Démarrer la synchronisation avec le serveur
        syncWithServer();
        
        // Synchroniser toutes les 10 secondes
        setInterval(syncWithServer, 10000);
    }
});
