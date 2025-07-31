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
    error_log("Tentative d'accès non autorisée à add_goal.php");
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
$minute = filter_input(INPUT_POST, 'minute', FILTER_VALIDATE_INT);
$isOwnGoal = isset($_POST['own_goal']) && $_POST['own_goal'] === '1';
$isPenalty = isset($_POST['penalty']) && $_POST['penalty'] === '1';

// Valider les données
if ($matchId === false || !in_array($team, ['home', 'away']) || empty($player) || 
    $minute === false || $minute < 1 || $minute > 120) {
    
    error_log("Données de but invalides reçues: match_id=$matchId, team=$team, player=$player, minute=$minute");
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'success' => false, 
        'message' => 'Données de but invalides. Vérifiez les informations saisies.'
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
        throw new Exception("Impossible d'ajouter un but à un match qui n'est pas en cours.");
    }
    
    // Déterminer l'équipe qui marque et qui encaisse
    $scoringTeam = $team === 'home' ? $match['team_home'] : $match['team_away'];
    $concedingTeam = $team === 'home' ? $match['team_away'] : $match['team_home'];
    
    // Si c'est un but contre son camp, inverser les équipes
    if ($isOwnGoal) {
        list($scoringTeam, $concedingTeam) = [$concedingTeam, $scoringTeam];
    }
    
    // Ajouter le but dans la base de données
    $insertStmt = $pdo->prepare("
        INSERT INTO goals 
        (match_id, team_scored, team_conceded, player, minute, is_own_goal, is_penalty, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $insertStmt->execute([
        $matchId,
        $scoringTeam,
        $concedingTeam,
        $player,
        $minute,
        $isOwnGoal ? 1 : 0,
        $isPenalty ? 1 : 0
    ]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'ajout du but.");
    }
    
    // Mettre à jour le score dans la table des matchs
    $scoreField = $team === 'home' ? 'score_home' : 'score_away';
    // Si c'est un but contre son camp, on incrémente l'autre équipe
    if ($isOwnGoal) {
        $scoreField = $team === 'home' ? 'score_away' : 'score_home';
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE matches 
        SET {$scoreField} = {$scoreField} + 1, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    $updateStmt->execute([$matchId]);
    
    // Journaliser l'action
    $goalType = [];
    if ($isOwnGoal) $goalType[] = 'contre son camp';
    if ($isPenalty) $goalType[] = 'sur penalty';
    $goalTypeStr = !empty($goalType) ? ' (' . implode(', ', $goalType) . ')' : '';
    
    logMatchAction($pdo, $matchId, 'ADD_GOAL', 
        "But de {$player} pour {$scoringTeam} à la {$minute}e minute{$goalTypeStr}");
    
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Le but a été ajouté avec succès.',
        'redirect' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur dans add_goal.php: " . $e->getMessage());
    
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
