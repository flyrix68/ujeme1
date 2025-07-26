<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier la structure de la table classement
    $stmt = $pdo->query("DESCRIBE classement");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table 'classement':\n";
    echo str_pad("Champ", 20) . str_pad("Type", 20) . "Null\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($columns as $col) {
        echo str_pad($col['Field'], 20) . 
             str_pad($col['Type'], 20) . 
             $col['Null'] . "\n";
    }
    
    // Vérifier si la colonne logo existe
    $logoExists = false;
    foreach ($columns as $col) {
        if (strtolower($col['Field']) === 'logo') {
            $logoExists = true;
            break;
        }
    }
    
    if (!$logoExists) {
        echo "\nLa colonne 'logo' n'existe pas dans la table 'classement'.\n";
        
        // Essayer d'ajouter la colonne
        try {
            $pdo->exec("ALTER TABLE classement ADD COLUMN logo VARCHAR(255) DEFAULT NULL AFTER nom_equipe");
            echo "Colonne 'logo' ajoutée avec succès.\n";
            $logoAdded = true;
        } catch (PDOException $e) {
            echo "Erreur lors de l'ajout de la colonne 'logo': " . $e->getMessage() . "\n";
        }
    } else {
        echo "\nLa colonne 'logo' existe déjà dans la table 'classement'.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
