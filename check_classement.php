<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier si la table classement existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'classement' n'existe pas dans la base de données.\n");
    }
    
    // Afficher la structure de la table
    echo "=== STRUCTURE DE LA TABLE 'classement' ===\n";
    $stmt = $pdo->query("DESCRIBE classement");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ") " . 
             "NULL: " . $row['Null'] . " | " .
             "DEFAULT: " . ($row['Default'] ?? 'NULL') . "\n";
    }
    
    // Afficher le contenu de la table
    echo "\n=== CONTENU DE LA TABLE 'classement' ===\n";
    $teams = $pdo->query("SELECT * FROM classement ORDER BY points DESC, difference_buts DESC, buts_pour DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($teams)) {
        echo "La table 'classement' est vide.\n";
    } else {
        echo str_pad("Position", 10) . str_pad("Équipe", 30) . str_pad("MJ", 5) . 
             str_pad("G", 5) . str_pad("N", 5) . str_pad("P", 5) . 
             str_pad("BP", 5) . str_pad("BC", 5) . str_pad("Diff", 7) . 
             str_pad("Pts", 5) . "Forme\n";
        echo str_repeat("-", 85) . "\n";
        
        $position = 1;
        foreach ($teams as $team) {
            echo str_pad($position++, 10) . 
                 str_pad($team['nom_equipe'], 30) . 
                 str_pad($team['matchs_joues'], 5) . 
                 str_pad($team['matchs_gagnes'], 5) . 
                 str_pad($team['matchs_nuls'], 5) . 
                 str_pad($team['matchs_perdus'], 5) . 
                 str_pad($team['buts_pour'], 5) . 
                 str_pad($team['buts_contre'], 5) . 
                 str_pad($team['difference_buts'], 7) . 
                 str_pad($team['points'], 5) . 
                 $team['forme'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
