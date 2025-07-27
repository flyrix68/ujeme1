<?php
require_once 'includes/db.php';

// Vérifier la structure de la table matches
$structure = $pdo->query("DESCRIBE matches")->fetchAll(PDO::FETCH_ASSOC);
echo "=== Structure de la table matches ===\n";
print_r($structure);

// Vérifier les statuts des matchs existants
$statusCounts = $pdo->query("SELECT status, COUNT(*) as count FROM matches GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== Nombre de matchs par statut ===\n";
print_r($statusCounts);

// Vérifier les 5 derniers matchs terminés
$finishedMatches = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status = 'termine' ORDER BY match_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== 5 derniers matchs terminés ===\n";
print_r($finishedMatches);

// Vérifier la requête complète qui pose problème
$query = "
    SELECT m.*, 
           t1.name as team_home, 
           t2.name as team_away,
           c.name as competition
    FROM matches m
    JOIN teams t1 ON m.team_home_id = t1.id
    JOIN teams t2 ON m.team_away_id = t2.id
    JOIN competitions c ON m.competition_id = c.id
    WHERE m.status = 'termine'
    ORDER BY m.match_date DESC
    LIMIT 5
";

try {
    $testQuery = $pdo->query($query);
    $results = $testQuery->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== Résultats de la requête des matchs terminés ===\n";
    print_r($results);
} catch (PDOException $e) {
    echo "\n=== Erreur lors de l'exécution de la requête ===\n";
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Requête: " . $query . "\n";
}
?>
