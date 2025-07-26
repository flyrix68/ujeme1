<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier si la table standings existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'standings'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "La table 'standings' existe. Voici sa structure :\n";
        $columns = $pdo->query('DESCRIBE standings')->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
        
        // Afficher un exemple de données
        echo "\nExemple de données dans la table 'standings':\n";
        $data = $pdo->query('SELECT * FROM standings LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        print_r($data);
    } else {
        echo "La table 'standings' n'existe pas.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
    echo "Code d'erreur: " . $e->getCode() . "\n";
    echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "Trace d'appel: " . $e->getTraceAsString() . "\n";
}
