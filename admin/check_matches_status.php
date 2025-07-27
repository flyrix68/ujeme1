<?php
require_once 'includes/db.php';

// Vérifier la structure de la table matches
echo "=== Structure de la table matches ===<br>";
$structure = $pdo->query("SHOW COLUMNS FROM matches")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($structure);
echo "</pre>";

// Vérifier les valeurs uniques du champ status
echo "<br>=== Valeurs uniques du champ status ===<br>";
$statusValues = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM matches GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($statusValues);
echo "</pre>";

// Vérifier les 5 derniers matchs terminés avec différentes variantes de statut
echo "<br>=== 5 derniers matchs avec différents statuts ===<br>";
$testQueries = [
    "SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status = 'termine' ORDER BY match_date DESC LIMIT 5",
    "SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status = 'completed' ORDER BY match_date DESC LIMIT 5",
    "SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status = 'finished' ORDER BY match_date DESC LIMIT 5",
    "SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status LIKE '%termine%' OR status LIKE '%complete%' OR status LIKE '%finish%' ORDER BY match_date DESC LIMIT 5"
];

foreach ($testQueries as $i => $query) {
    echo "<br>Test $i: " . htmlspecialchars($query) . "<br>";
    try {
        $results = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    } catch (PDOException $e) {
        echo "Erreur: " . $e->getMessage() . "<br>";
    }
}

// Vérifier s'il y a des matchs avec des scores mais sans statut 'termine'
echo "<br>=== Matchs avec des scores mais sans statut 'termine' ===<br>";
$query = "
    SELECT id, team_home, team_away, score_home, score_away, status 
    FROM matches 
    WHERE (score_home > 0 OR score_away > 0) 
    AND status != 'termine' 
    ORDER BY match_date DESC 
    LIMIT 5
";
try {
    $results = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($results);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "<br>";
}
?>
