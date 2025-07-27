<?php
require 'admin/admin_header.php';

try {
    // Récupérer les matchs avec les statuts 'ongoing' ou 'pending'
    $stmt = $pdo->query("SELECT id, team_home, team_away, status, timer_status, timer_elapsed, timer_duration, score_home, score_away FROM matches WHERE status IN ('ongoing', 'pending') ORDER BY match_date DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Matchs en cours ou en attente</h2>";
    echo "<pre>";
    print_r($matches);
    echo "</pre>";
    
    // Vérifier si la table goals existe et contient des données
    echo "<h2>Contenu de la table goals</h2>";
    try {
        $goals = $pdo->query("SELECT * FROM goals LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($goals);
        echo "</pre>";
    } catch (PDOException $e) {
        echo "Erreur lors de la récupération des buts: " . $e->getMessage();
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
