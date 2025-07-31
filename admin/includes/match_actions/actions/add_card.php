<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session avec des paramètres cohérents
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Inclure la configuration de la base de données
require_once '../../../includes/db-config.php';

// Vérifier l'authentification de l'administrateur
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Tentative d'accès non autorisée à add_card.php");
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

// Récupérer et valider les données du formulaire
$matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
$team = filter_input(INPUT_POST, 'team', FILTER_SANITIZE_STRING);
$player = filter_input(INPUT_POST, 'player', FILTER_SANITIZE_STRING);
$cardType = filter_input(INPUT_POST, 'card_type', FILTER_SANITIZE_STRING);
$minute = filter_input(INPUT_POST, 'minute', FILTER_VALIDATE_INT);
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);

// Valider les données
if ($matchId === false || !in_array($team, ['home', 'away']) || empty($player) || 
    !in_array($cardType, ['yellow', 'red', 'blue']) || $minute === false || $minute < 1 || $minute > 120) {
    
    error_log("Données de carton invalides reçues: match_id=$matchId, team=$team, player=$player, card_type=$cardType, minute=$minute");
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'success' => false, 
        'message' => 'Données de carton invalides. Vérifiez les informations saisies.'
    ]);
    exit();
}

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    $pdo->beginTransaction();
    
    // Vérifier que le match existe et est en cours
    $stmt = $pdo->prepare("SELECT id, status, team_home, team_away FROM matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match non trouvé.");
    }
    
    if ($match['status'] !== 'ongoing') {
        throw new Exception("Impossible d'ajouter un carton à un match qui n'est pas en cours.");
    }
    
    // Déterminer l'équipe du joueur
    $playerTeam = $team === 'home' ? $match['team_home'] : $match['team_away'];
    
    // Vérifier si le joueur a déjà un carton jaune
    if ($cardType === 'red') {
        $checkYellowStmt = $pdo->prepare("
            SELECT id FROM cards 
            WHERE match_id = ? AND player = ? AND card_type = 'yellow' AND team = ?
        ");
        $checkYellowStmt->execute([$matchId, $player, $playerTeam]);
        $hasYellow = $checkYellowStmt->fetch();
        
        // Si le joueur a déjà un jaune, on le transforme en rouge au lieu d'ajouter un nouveau carton
        if ($hasYellow) {
            $updateStmt = $pdo->prepare("
                UPDATE cards 
                SET card_type = 'red', 
                    reason = CONCAT(IFNULL(reason, ''), ' | Second carton jaune, expulsion. ', ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$reason, $hasYellow['id']]);
            
            // Journaliser l'action
            logMatchAction($pdo, $matchId, 'UPDATE_CARD', 
                "Second carton jaune pour {$player} de {$playerTeam} à la {$minute}e minute. Carton transformé en rouge. Raison: {$reason}");
            
            $pdo->commit();
            
            // Réponse de succès
            echo json_encode([
                'success' => true,
                'message' => 'Le carton jaune a été transformé en carton rouge avec succès.',
                'redirect' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php'
            ]);
            exit();
        }
    }
    
    // Ajouter le carton dans la base de données
    $insertStmt = $pdo->prepare("
        INSERT INTO cards 
        (match_id, team, player, card_type, minute, reason, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $insertStmt->execute([
        $matchId,
        $playerTeam,
        $player,
        $cardType,
        $minute,
        $reason
    ]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'ajout du carton.");
    }
    
    // Journaliser l'action
    $cardTypeFr = $cardType === 'yellow' ? 'jaune' : 'rouge';
    logMatchAction($pdo, $matchId, 'ADD_CARD', 
        "Carton {$cardTypeFr} pour {$player} de {$playerTeam} à la {$minute}e minute. Raison: {$reason}");
    
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Le carton a été ajouté avec succès.',
        'redirect' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur dans add_card.php: " . $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue : ' . $e->getMessage()
    ]);
}

/**
 * Journalise une action sur un match
 * @param PDO $pdo Instance PDO
 * @param int $matchId ID du match
 * @param string $actionType Type d'action
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
?>
