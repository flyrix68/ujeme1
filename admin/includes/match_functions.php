<?php
/**
 * Fonctions utilitaires pour la gestion des matchs
 */

/**
 * Récupère les détails d'un match avec les logos des équipes
 * @param PDO $pdo Instance PDO
 * @param int $matchId ID du match
 * @return array|false Les données du match ou false si non trouvé
 */
function getMatchDetails($pdo, $matchId) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   t1.logo as home_logo, 
                   t2.logo as away_logo
            FROM matches m
            LEFT JOIN teams t1 ON m.team_home = t1.team_name
            LEFT JOIN teams t2 ON m.team_away = t2.team_name
            WHERE m.id = ?
        ");
        $stmt->execute([$matchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getMatchDetails: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les buts d'un match
 * @param PDO $pdo Instance PDO
 * @param int $matchId ID du match
 * @return array Liste des buts
 */
function getMatchGoals($pdo, $matchId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
        $stmt->execute([$matchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getMatchGoals: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les cartons d'un match
 * @param PDO $pdo Instance PDO
 * @param int $matchId ID du match
 * @return array Liste des cartons
 */
function getMatchCards($pdo, $matchId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
        $stmt->execute([$matchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getMatchCards: " . $e->getMessage());
        return [];
    }
}

/**
 * Vérifie si l'utilisateur est administrateur
 * @return bool True si administrateur, false sinon
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && 
           !empty($_SESSION['user_id']) && 
           ($_SESSION['role'] ?? 'membre') === 'admin';
}

/**
 * Journalise une action sur un match
 * @param PDO $pdo Instance PDO
 * @param int $matchId ID du match
 * @param string $actionType Type d'action (ex: 'ADD_GOAL', 'ADD_CARD', 'UPDATE_SCORE')
 * @param string|null $details Détails supplémentaires
 * @return bool Succès de l'opération
 */
function logMatchAction($pdo, $matchId, $actionType, $details = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO match_logs 
            (match_id, user_id, action_type, action_details, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $matchId,
            $_SESSION['user_id'],
            $actionType,
            $details
        ]);
    } catch (PDOException $e) {
        error_log("Erreur dans logMatchAction: " . $e->getMessage());
        return false;
    }
}

/**
 * Formate une durée en secondes en format MM:SS
 * @param int $seconds Durée en secondes
 * @return string Durée formatée
 */
function formatMatchTime($seconds) {
    if ($seconds < 0) return '00:00';
    
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $seconds);
}

/**
 * Vérifie si un match est en cours
 * @param array $match Données du match
 * @return bool True si le match est en cours
 */
function isMatchOngoing($match) {
    return ($match['status'] ?? '') === 'ongoing' || 
           ($match['timer_status'] ?? '') === 'running';
}

/**
 * Récupère le chemin du logo d'une équipe
 * @param string $teamName Nom de l'équipe
 * @return string Chemin du logo
 */
function getTeamLogoPath($teamName) {
    $logoPath = '../assets/img/teams/' . strtolower(str_replace(' ', '-', $teamName)) . '.png';
    return file_exists($logoPath) ? $logoPath : '../assets/img/teams/default.png';
}
?>
