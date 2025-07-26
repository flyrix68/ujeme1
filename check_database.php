<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier les tables existantes
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables dans la base de données: " . implode(", ", $tables) . "\n\n";
    
    // Vérifier si la table classement existe
    if (in_array('classement', $tables)) {
        echo "La table 'classement' existe. Voici sa structure :\n";
        $columns = $pdo->query('DESCRIBE classement')->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
        
        // Vérifier les contraintes de clé primaire
        $stmt = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'classement' 
            AND CONSTRAINT_NAME = 'PRIMARY'
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "\nColonnes de clé primaire: " . implode(", ", $stmt) . "\n";
    } else {
        echo "La table 'classement' n'existe pas.\n";
    }
    
    // Vérifier la table matches
    if (in_array('matches', $tables)) {
        echo "\nStructure de la table 'matches':\n";
        $columns = $pdo->query('DESCRIBE matches')->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
    echo "Code d'erreur: " . $e->getCode() . "\n";
    echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "Trace d'appel: " . $e->getTraceAsString() . "\n";
}
