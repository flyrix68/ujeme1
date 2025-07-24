class LiveMatchUpdater {
    constructor() {
        this.socket = null;
        this.watchedMatches = new Set();
        this.retryCount = 0;
        this.maxRetries = 5;
        this.reconnectDelay = 1000;
    }

    init() {
        this.connectWebSocket();
        return this;
    }

    connectWebSocket() {
        if (this.socket?.readyState === WebSocket.OPEN) return;

        const socketUrl = `ws://${window.location.hostname}:8080`;
        this.socket = new WebSocket(socketUrl);

        this.socket.onopen = () => {
            console.log('WebSocket connection established');
            this.retryCount = 0;
            this.reconnectDelay = 1000;
            this.resubscribeToMatches();
        };

        this.socket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data.type === 'match_update') {
                    this.updateMatchUI(data.match);
                }
            } catch (e) {
                console.error('Error parsing message:', e);
            }
        };

        this.socket.onclose = () => this.handleDisconnect();
        this.socket.onerror = (error) => console.error('WebSocket error:', error);
    }

    resubscribeToMatches() {
        this.watchedMatches.forEach(matchId => {
            this.socket.send(JSON.stringify({
                type: 'subscribe',
                match_id: matchId
            }));
        });
    }

    handleDisconnect() {
        if (this.retryCount < this.maxRetries) {
            console.log(`Reconnecting in ${this.reconnectDelay}ms...`);
            setTimeout(() => this.connectWebSocket(), this.reconnectDelay);
            this.retryCount++;
            this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000);
        }
    }

    subscribeToMatch(matchId) {
        if (!this.watchedMatches.has(matchId)) {
            this.watchedMatches.add(matchId);
            
            if (this.socket?.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify({
                    type: 'subscribe',
                    match_id: matchId
                }));
            }
        }
    }

    unsubscribeFromMatch(matchId) {
        this.watchedMatches.delete(matchId);
        
        if (this.socket?.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify({
                type: 'unsubscribe',
                match_id: matchId
            }));
        }
    }

    updateMatchUI(matchData) {
        // Find the match element in the DOM and update it
        const matchElement = document.querySelector(`[data-match-id="${matchData.id}"]`);
        if (matchElement) {
            // Update the match score and other details
            const homeScoreEl = matchElement.querySelector('.match-score-home');
            const awayScoreEl = matchElement.querySelector('.match-score-away');
            const matchStatus = matchElement.querySelector('.match-status');
            
            if (homeScoreEl) homeScoreEl.textContent = matchData.home_score || '0';
            if (awayScoreEl) awayScoreEl.textContent = matchData.away_score || '0';
            if (matchStatus) matchStatus.textContent = matchData.status || '';
            
            // Add a visual indicator that the score was updated
            matchElement.classList.add('score-updated');
            setTimeout(() => matchElement.classList.remove('score-updated'), 1000);
        }
    }
