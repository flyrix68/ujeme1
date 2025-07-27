<?php
require 'admin/admin_header.php';

try {
    // Vérifier si la table matches existe
    $tables = $pdo->query("SHOW TABLES LIKE 'matches'")->rowCount();
    if ($tables > 0) {
        echo "La table 'matches' existe.\n";
        
        // Afficher les colonnes de la table matches
        $stmt = $pdo->query("SHOW COLUMNS FROM matches");
        echo "\nStructure de la table matches :\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] == 'NO' ? 'NOT NULL' : '') . "\n";
        }
        
        // Afficher un exemple de données
        $stmt = $pdo->query("SELECT * FROM matches LIMIT 1");
        $example = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nExemple de données :\n";
        print_r($example);
    } else {
        echo "La table 'matches' n'existe pas.\n";
    }
    
    echo "\n";
    
    // Vérifier si la table teams existe
    $tables = $pdo->query("SHOW TABLES LIKE 'teams'")->rowCount();
    if ($tables > 0) {
        echo "La table 'teams' existe.\n";
        
        // Afficher les colonnes de la table teams
        $stmt = $pdo->query("SHOW COLUMNS FROM teams");
        echo "\nStructure de la table teams :\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] == 'NO' ? 'NOT NULL' : '') . "\n";
        }
        
        // Afficher un exemple de données
        $stmt = $pdo->query("SELECT * FROM teams LIMIT 1");
        $example = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nExemple de données :\n";
        print_r($example);
    } else {
        echo "La table 'teams' n'existe pas.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Détails : " . print_r($pdo->errorInfo(), true) . "\n";
}
