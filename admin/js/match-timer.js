/**
 * Script pour la gestion du minuteur des matchs
 * Utilise la classe MatchTimer pour gérer l'affichage et la mise à jour des minuteurs
 */

class MatchTimer {
    constructor(timerElement, matchId) {
        this.timerElement = timerElement;
        this.matchId = matchId;
        this.intervalId = null;
        this.startTime = null;
        this.elapsed = 0;
        this.duration = 0;
        this.status = 'stopped';
    }

    /**
     * Initialise le minuteur avec les paramètres de base
     * @param {number} startTime - Timestamp de début du match
     * @param {number} elapsed - Temps déjà écoulé en secondes
     * @param {number} duration - Durée totale du match en secondes
     * @param {string} status - Statut actuel du match
     */
    init(startTime, elapsed, duration, status) {
        this.startTime = startTime;
        this.elapsed = elapsed;
        this.duration = duration;
        this.status = status;
        this.update();
        this.start();
    }

    /**
     * Démarre la mise à jour automatique du minuteur
     */
    start() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        this.intervalId = setInterval(() => this.update(), 1000);
    }

    /**
     * Arrête la mise à jour automatique du minuteur
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    /**
     * Met à jour l'affichage du minuteur
     */
    update() {
        const now = Math.floor(Date.now() / 1000);
        let totalSeconds = this.elapsed + (now - this.startTime);
        
        // Calcul selon le statut du match
        let displayTime = '';
        if (this.status === 'first_half') {
            const halfDuration = this.duration / 2;
            totalSeconds = Math.min(totalSeconds, halfDuration);
            displayTime = this.formatTime(totalSeconds) + ' / ' + this.formatTime(halfDuration);
        } 
        else if (this.status === 'second_half') {
            const firstHalfDuration = this.duration / 2;
            const secondHalfDuration = this.duration - firstHalfDuration;
            totalSeconds = Math.min(totalSeconds - firstHalfDuration, secondHalfDuration);
            displayTime = this.formatTime(totalSeconds + firstHalfDuration) + ' / ' + this.formatTime(this.duration);
        }
        else if (this.status === 'half_time') {
            displayTime = 'Mi-temps';
        }
        else {
            displayTime = this.formatTime(totalSeconds) + ' / ' + this.formatTime(this.duration);
        }

        this.timerElement.textContent = displayTime;
    }

    /**
     * Formate un nombre de secondes en minutes:secondes
     * @param {number} totalSeconds - Nombre total de secondes
     * @returns {string} Temps formaté (ex: "45:00")
     */
    formatTime(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = Math.floor(totalSeconds % 60);
        return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }
}
