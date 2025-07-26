<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier si la table teams existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'teams'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'teams' n'existe pas dans la base de données.\n");
    }
    
    // Afficher la structure de la table teams
    echo "Structure de la table 'teams':\n";
    echo str_pad("Champ", 25) . str_pad("Type", 20) . "Null\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("DESCRIBE teams");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 25) . 
             str_pad($row['Type'], 20) . 
             $row['Null'] . "\n";
    }
    
    // Afficher quelques équipes pour vérifier les données
    echo "\nExemples d'équipes dans la table 'teams':\n";
    $teams = $pdo->query("SELECT team_name, logo_path FROM teams LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($teams as $team) {
        echo "- " . $team['team_name'] . " (Logo: " . ($team['logo_path'] ?? 'Aucun') . ")\n";
    }
    
    // Vérifier la correspondance avec la table classement
    echo "\nVérification des correspondances avec la table 'classement':\n";
    
    $sampleTeams = $pdo->query("SELECT DISTINCT nom_equipe FROM classement LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Quelques équipes dans la table 'classement':\n";
    foreach ($sampleTeams as $team) {
        echo "- $team\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
