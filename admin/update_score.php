<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session avec des paramètres cohérents
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Vérifier l'authentification de l'administrateur
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Tentative d'accès non autorisée à update_score.php");
    header('Location: ../index.php');
    exit();
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: dashboard.php');
    exit();
}

// Récupérer et valider les données du formulaire
$match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
$score_home = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT);
$score_away = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT);

// Valider les données
if ($match_id === false || $score_home === false || $score_away === false || 
    $score_home < 0 || $score_away < 0 || $score_home > 50 || $score_away > 50) {
    $_SESSION['error'] = "Scores invalides. Les scores doivent être des nombres entiers positifs inférieurs à 50.";
    header("Location: match_details.php?id=$match_id");
    exit();
}

// Connexion à la base de données
require_once '../includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier que le match existe
    $stmt = $pdo->prepare("SELECT id, status FROM matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match non trouvé.");
    }
    
    // Mettre à jour le score dans la base de données
    $updateStmt = $pdo->prepare("
        UPDATE matches 
        SET score_home = ?, score_away = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$score_home, $score_away, $match_id]);
    
    if (!$result) {
        throw new Exception("Erreur lors de la mise à jour du score.");
    }
    
    // Journaliser l'action
    logAction($pdo, $match_id, 'UPDATE_SCORE', "Score mis à jour : $score_home - $score_away");
    
    $_SESSION['success'] = "Le score a été mis à jour avec succès.";
    
} catch (Exception $e) {
    error_log("Erreur dans update_score.php: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
}

// Rediriger vers la page des détails du match
header("Location: match_details.php?id=$match_id");
exit();

/**
 * Fonction pour journaliser les actions
 */
function logAction($pdo, $matchId, $actionType, $details = null) {
    if (!isset($_SESSION['user_id'])) return;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO match_logs 
            (match_id, user_id, action_type, action_details, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $matchId,
            $_SESSION['user_id'],
            $actionType,
            $details
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors de la journalisation: " . $e->getMessage());
    }
}
?>
