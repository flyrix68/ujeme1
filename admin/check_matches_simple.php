<?php
require_once 'includes/db.php';

// Vérifier la structure de la table matches
try {
    $structure = $pdo->query("SHOW COLUMNS FROM matches")->fetchAll(PDO::FETCH_ASSOC);
    echo "=== Structure de la table matches ===\n";
    print_r($structure);
    
    // Vérifier les statuts des matchs
    $statusCounts = $pdo->query("SELECT status, COUNT(*) as count FROM matches GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== Nombre de matchs par statut ===\n";
    print_r($statusCounts);
    
    // Vérifier les 5 derniers matchs terminés
    $finishedMatches = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status = 'termine' ORDER BY match_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== 5 derniers matchs terminés ===\n";
    print_r($finishedMatches);
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
