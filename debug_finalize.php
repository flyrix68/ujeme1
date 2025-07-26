<?php
// Activer l'affichage des erreurs PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Vérifier si un ID de match est fourni
$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;

if (!$matchId) {
    die("Erreur: Aucun ID de match spécifié. Utilisation: debug_finalize.php?match_id=ID_DU_MATCH");
}

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    echo "<h1>Débogage de la finalisation du match #$matchId</h1>";
    
    // 1. Vérifier si le match existe
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Le match #$matchId n'existe pas.");
    }
    
    echo "<h2>1. Informations du match #$matchId</h2>";
    echo "<pre>" . print_r($match, true) . "</pre>";
    
    // 2. Vérifier les données requises pour la finalisation
    $requiredFields = ['saison', 'competition', 'poule_id', 'score_home', 'score_away'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($match[$field]) || (is_string($match[$field]) && trim($match[$field]) === '')) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw new Exception("Champs manquants dans les données du match: " . implode(', ', $missingFields));
    }
    
    echo "<div style='color: green;'>✓ Tous les champs requis sont présents.</div>";
    
    // 3. Mettre à jour le statut du match
    $oldStatus = $match['status'];
    $stmt = $pdo->prepare("UPDATE matches SET status = 'completed', timer_start = NULL, timer_status = 'ended' WHERE id = ?");
    $updateResult = $stmt->execute([$matchId]);
    
    if (!$updateResult) {
        throw new Exception("Échec de la mise à jour du statut du match.");
    }
    
    echo "<h2>2. Statut du match mis à jour</h2>";
    echo "<p>Ancien statut: $oldStatus → Nouveau statut: completed</p>";
    
    // 4. Mettre à jour le classement
    echo "<h2>3. Mise à jour du classement</h2>";
    
    // Inclure la fonction de mise à jour du classement
    require_once 'temp_update_classement.php';
    
    // Préparer les données pour la mise à jour du classement
    $classementData = [
        'saison' => $match['saison'],
        'competition' => $match['competition'],
        'poule_id' => $match['poule_id'],
        'team_home' => $match['team_home'],
        'team_away' => $match['team_away'],
        'score_home' => (int)$match['score_home'],
        'score_away' => (int)$match['score_away']
    ];
    
    echo "<h3>Données transmises à updateClassementForMatch:</h3>";
    echo "<pre>" . print_r($classementData, true) . "</pre>";
    
    // Mettre à jour le classement avec les nouvelles données
    $updateResult = updateClassementForMatch($pdo, $classementData);
    
    if (!$updateResult) {
        throw new Exception("Échec de la mise à jour du classement via updateClassementForMatch.");
    }
    
    echo "<div style='color: green;'>✓ Mise à jour du classement réussie via updateClassementForMatch.</div>";
    
    // 5. Mettre à jour l'ancien système de classement pour compatibilité
    if (function_exists('updateStandings')) {
        echo "<h3>Mise à jour de l'ancien système de classement (updateStandings)</h3>";
        updateStandings($matchId, $pdo);
        echo "<div style='color: green;'>✓ Mise à jour de l'ancien système de classement réussie.</div>";
    } else {
        echo "<div style='color: orange;'>⚠ La fonction updateStandings n'est pas disponible.</div>";
    }
    
    // 6. Valider la transaction
    $pdo->commit();
    
    // Afficher un message de succès
    echo "<h2 style='color: green;'>✓ Finalisation du match #$matchId réussie !</h2>";
    echo "<p>Le match a été marqué comme terminé et le classement a été mis à jour avec succès.</p>";
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Afficher l'erreur
    echo "<h2 style='color: red;'>Erreur lors de la finalisation du match #$matchId</h2>";
    echo "<div style='background-color: #ffdddd; border-left: 6px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur SQL:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
    
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>";
    echo "<p><strong>Trace d'appel:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

// Afficher un lien pour retourner au tableau de bord
echo "<p><a href='admin/dashboard.php'>&larr; Retour au tableau de bord</a></p>";
?>
