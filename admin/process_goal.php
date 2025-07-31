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
    error_log("Tentative d'accès non autorisée à process_goal.php");
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
$team = filter_input(INPUT_POST, 'team', FILTER_SANITIZE_STRING);
$player = filter_input(INPUT_POST, 'player', FILTER_SANITIZE_STRING);
$minute = filter_input(INPUT_POST, 'minute', FILTER_VALIDATE_INT);

// Valider les données
if (!$match_id || !in_array($team, ['home', 'away']) || empty($player) || $minute === false || $minute < 1 || $minute > 120) {
    $_SESSION['error'] = "Données du formulaire invalides.";
    header("Location: match_details.php?id=$match_id");
    exit();
}

// Connexion à la base de données
require_once __DIR__ . '/includes/db-ssl.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    // Vérifier que le match existe et est en cours
    $stmt = $pdo->prepare("SELECT id, status FROM matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match non trouvé.");
    }
    
    if ($match['status'] !== 'ongoing') {
        throw new Exception("Les buts ne peuvent être ajoutés que pour les matchs en cours.");
    }
    
    // Insérer le but dans la base de données
    $insertStmt = $pdo->prepare("
        INSERT INTO goals (match_id, team, player, minute, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $insertStmt->execute([$match_id, $team, $player, $minute]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'ajout du but.");
    }
    
    // Mettre à jour le score dans la table des matchs
    $scoreField = $team === 'home' ? 'score_home' : 'score_away';
    $updateStmt = $pdo->prepare("
        UPDATE matches 
        SET $scoreField = COALESCE($scoreField, 0) + 1 
        WHERE id = ?
    ");
    
    $updateStmt->execute([$match_id]);
    
    // Journaliser l'action
    logAction($pdo, $match_id, 'ADD_GOAL', "But marqué par $player à la $minute' pour l'équipe $team");
    
    $_SESSION['success'] = "Le but a été ajouté avec succès.";
    
} catch (Exception $e) {
    error_log("Erreur dans process_goal.php: " . $e->getMessage());
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
