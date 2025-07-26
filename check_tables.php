<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Fonction pour afficher les informations d'une table
    function showTableInfo($pdo, $tableName) {
        echo "\n=== Table: $tableName ===\n";
        
        // Vérifier si la table existe
        $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
        
        if (!$tableExists) {
            echo "La table n'existe pas.\n";
            return;
        }
        
        // Afficher la structure
        echo "Structure:\n";
        $stmt = $pdo->query("DESCRIBE $tableName");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        
        // Compter le nombre d'enregistrements
        $count = $pdo->query("SELECT COUNT(*) as count FROM $tableName")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\nNombre d'enregistrements: $count\n";
        
        // Afficher quelques exemples
        if ($count > 0) {
            $limit = min(3, $count);
            echo "\nExemples ($limit premiers enregistrements):\n";
            
            $rows = $pdo->query("SELECT * FROM $tableName LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                print_r($row);
                echo "\n";
            }
        }
    }
    
    // Vérifier les tables
    showTableInfo($pdo, 'teams');
    showTableInfo($pdo, 'classement');
    
    // Vérifier la correspondance des noms
    echo "\n=== Vérification des correspondances ===\n";
    
    // Récupérer quelques équipes de chaque table
    $teams = $pdo->query("SELECT team_name FROM teams LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    $classementTeams = $pdo->query("SELECT DISTINCT nom_equipe FROM classement LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Quelques équipes dans 'teams':\n";
    foreach ($teams as $team) {
        echo "- $team\n";
    }
    
    echo "\nQuelques équipes dans 'classement':\n";
    foreach ($classementTeams as $team) {
        echo "- $team\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
