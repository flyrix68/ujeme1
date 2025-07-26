<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtenir la structure de la table matches
    $stmt = $pdo->query("DESCRIBE matches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table 'matches':\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-15s %-10s %-5s %-10s %-10s\n", 
           'Champ', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-20s %-15s %-10s %-5s %-10s %-10s\n",
               $column['Field'],
               $column['Type'],
               $column['Null'],
               $column['Key'],
               $column['Default'] ?? 'NULL',
               $column['Extra']);
    }
    
    // Vérifier les contraintes de clé étrangère
    echo "\nContraintes de clé étrangère :\n";
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'matches'
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($foreignKeys) > 0) {
        foreach ($foreignKeys as $fk) {
            printf("Colonne '%s' référence %s(%s) (contrainte: %s)\n",
                   $fk['COLUMN_NAME'],
                   $fk['REFERENCED_TABLE_NAME'],
                   $fk['REFERENCED_COLUMN_NAME'],
                   $fk['CONSTRAINT_NAME']);
        }
    } else {
        echo "Aucune contrainte de clé étrangère trouvée.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
