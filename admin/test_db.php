<?php
require_once 'includes/db.php';

try {
    // Vérifier la structure de la table matches
    echo "=== Structure de la table matches ===<br>";
    $structure = $pdo->query("DESCRIBE matches")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // Vérifier les statuts des matchs
    echo "<br>=== Nombre de matchs par statut ===<br>";
    $statusCounts = $pdo->query("SELECT status, COUNT(*) as count FROM matches GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($statusCounts);
    echo "</pre>";
    
    // Vérifier les 5 derniers matchs terminés
    echo "<br>=== 5 derniers matchs terminés ===<br>";
    $finishedMatches = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, status FROM matches WHERE status = 'termine' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($finishedMatches);
    echo "</pre>";
    
    // Vérifier la requête complète
    echo "<br>=== Test de la requête complète ===<br>";
    $query = "
        SELECT m.*, 
               t1.name as team_home, 
               t2.name as team_away,
               c.name as competition
        FROM matches m
        LEFT JOIN teams t1 ON m.team_home_id = t1.id
        LEFT JOIN teams t2 ON m.team_away_id = t2.id
        LEFT JOIN competitions c ON m.competition_id = c.id
        WHERE m.status = 'termine'
        ORDER BY m.match_date DESC
        LIMIT 5
    ";
    
    try {
        $testQuery = $pdo->query($query);
        $results = $testQuery->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>Résultats de la requête : ";
        print_r($results);
        echo "</pre>";
    } catch (PDOException $e) {
        echo "Erreur: " . $e->getMessage() . "<br>";
        echo "Requête: " . htmlspecialchars($query) . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données: " . $e->getMessage();
}
?>
