<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la table standings existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'standings'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'standings' n'existe pas dans la base de données.\n");
    }
    
    // Afficher la structure de la table
    echo "=== STRUCTURE DE LA TABLE 'standings' ===\n";
    $stmt = $pdo->query("DESCRIBE standings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ") " . 
             "NULL: " . $row['Null'] . " | " .
             "DEFAULT: " . ($row['Default'] ?? 'NULL') . "\n";
    }
    
    // Compter le nombre d'entrées
    $count = $pdo->query("SELECT COUNT(*) as count FROM standings")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nNombre total d'entrées: $count\n";
    
    // Afficher quelques exemples
    if ($count > 0) {
        echo "\nQuelques exemples d'entrées:\n";
        $stmt = $pdo->query("SELECT * FROM standings LIMIT 3");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
    echo "Code d'erreur: " . $e->getCode() . "\n";
    echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
}
?>
