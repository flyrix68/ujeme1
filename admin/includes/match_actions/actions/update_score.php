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
require_once '../modals/score_modal.php';

// Vérifier l'authentification de l'administrateur
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Tentative d'accès non autorisée à update_score.php");
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
$scoreHome = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT);
$scoreAway = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT);

// Valider les données
if ($matchId === false || $scoreHome === false || $scoreAway === false || 
    $scoreHome < 0 || $scoreAway < 0 || $scoreHome > 50 || $scoreAway > 50) {
    error_log("Données de score invalides reçues: match_id=$matchId, score_home=$scoreHome, score_away=$scoreAway");
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'success' => false, 
        'message' => 'Scores invalides. Les scores doivent être des nombres entiers positifs inférieurs à 50.'
    ]);
    exit();
}

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    $pdo->beginTransaction();
    
    // Vérifier que le match existe et est en cours
    $stmt = $pdo->prepare("SELECT id, status FROM matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match non trouvé.");
    }
    
    // Récupérer l'ancien score pour le journal
    $oldHomeScore = $match['score_home'] ?? 0;
    $oldAwayScore = $match['score_away'] ?? 0;
    
    // Mettre à jour le score dans la base de données
    $updateStmt = $pdo->prepare("
        UPDATE matches 
        SET score_home = ?, score_away = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$scoreHome, $scoreAway, $matchId]);
    
    if (!$result) {
        throw new Exception("Erreur lors de la mise à jour du score.");
    }
    
    // Journaliser l'action
    logMatchAction($pdo, $matchId, 'UPDATE_SCORE', 
        "Score mis à jour : $oldHomeScore-$oldAwayScore → $scoreHome-$scoreAway");
    
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Le score a été mis à jour avec succès.',
        'redirect' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur dans update_score.php: " . $e->getMessage());
    
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
