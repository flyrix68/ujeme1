<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

echo "<h1>Vérification de la table standings</h1>";

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier si la table standings existe
    echo "<h2>1. Vérification de l'existence de la table</h2>";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'standings'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("<div style='color: red;'>La table 'standings' n'existe pas dans la base de données.</div>");
    }
    
    echo "<div style='color: green;'>✓ La table 'standings' existe.</div>";
    
    // 2. Vérifier la structure de la table standings
    echo "<h2>2. Structure de la table standings</h2>";
    $stmt = $pdo->query("DESCRIBE standings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    $requiredColumns = [
        'id' => false,
        'saison' => false,
        'competition' => false,
        'poule_id' => false,
        'nom_equipe' => false,
        'matchs_joues' => false,
        'matchs_gagnes' => false,
        'matchs_nuls' => false,
        'matchs_perdus' => false,
        'buts_pour' => false,
        'buts_contre' => false,
        'difference_buts' => false,
        'points' => false,
        'forme' => false,
        'created_at' => false,
        'updated_at' => false
    ];
    
    foreach ($columns as $column) {
        $field = $column['Field'];
        
        // Vérifier si la colonne est requise
        if (array_key_exists($field, $requiredColumns)) {
            $requiredColumns[$field] = true;
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier les colonnes manquantes
    $missingColumns = array_filter($requiredColumns, function($exists) { return !$exists; });
    
    if (count($missingColumns) > 0) {
        echo "<div style='color: orange; margin-top: 10px;'>";
        echo "<strong>Colonnes manquantes dans la table 'standings':</strong><br>";
        echo "- " . implode("<br>- ", array_keys($missingColumns));
        echo "</div>";
    } else {
        echo "<div style='color: green; margin-top: 10px;'>";
        echo "✓ Toutes les colonnes requises sont présentes dans la table 'standings'.";
        echo "</div>";
    }
    
    // 3. Vérifier les contraintes de clé primaire
    echo "<h2>3. Contraintes de clé primaire</h2>";
    $stmt = $pdo->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'standings' 
        AND CONSTRAINT_NAME = 'PRIMARY'
    ");
    
    $primaryKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($primaryKeys) > 0) {
        echo "<p>Clé primaire sur les colonnes: " . implode(", ", $primaryKeys) . "</p>";
        
        // Vérifier si la clé primaire est correcte
        $expectedPrimaryKeys = ['id'];
        $missingKeys = array_diff($expectedPrimaryKeys, $primaryKeys);
        $extraKeys = array_diff($primaryKeys, $expectedPrimaryKeys);
        
        if (empty($missingKeys) && empty($extraKeys)) {
            echo "<div style='color: green;'>✓ La clé primaire est correctement configurée.</div>";
        } else {
            echo "<div style='color: red;'>";
            echo "<strong>Problème avec la clé primaire:</strong><br>";
            
            if (!empty($missingKeys)) {
                echo "- Colonnes manquantes dans la clé primaire: " . implode(", ", $missingKeys) . "<br>";
            }
            
            if (!empty($extraKeys)) {
                echo "- Colonnes en trop dans la clé primaire: " . implode(", ", $extraKeys) . "<br>";
            }
            
            echo "La clé primaire devrait être sur: " . implode(", ", $expectedPrimaryKeys);
            echo "</div>";
        }
    } else {
        echo "<div style='color: red;'>Aucune clé primaire trouvée pour la table 'standings'.</div>";
    }
    
    // 4. Vérifier les index
    echo "<h2>4. Index de la table standings</h2>";
    $stmt = $pdo->query("
        SHOW INDEX FROM standings
    ");
    
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($indexes) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>
                <tr>
                    <th>Index</th>
                    <th>Colonne</th>
                    <th>Type</th>
                    <th>Unique</th>
                </tr>";
        
        $currentIndex = '';
        
        foreach ($indexes as $index) {
            if ($index['Key_name'] !== $currentIndex) {
                if ($currentIndex !== '') {
                    echo "</td></tr>";
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
                echo "<td>" . htmlspecialchars($index['Column_name']);
                $currentIndex = $index['Key_name'];
            } else {
                echo ", " . htmlspecialchars($index['Column_name']);
            }
        }
        
        if ($currentIndex !== '') {
            echo "</td><td>" . htmlspecialchars($indexes[count($indexes)-1]['Index_type']) . "</td>";
            echo "<td>" . ($indexes[count($indexes)-1]['Non_unique'] == 0 ? 'Oui' : 'Non') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun index trouvé pour la table 'standings'.</p>";
    }
    
    // 5. Vérifier les données existantes
    echo "<h2>5. Données existantes</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM standings");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo "<p>La table contient $count entrées.</p>";
        
        // Afficher un échantillon des données
        $stmt = $pdo->query("SELECT * FROM standings LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Exemple de données (5 premières entrées):</h3>";
        echo "<pre>" . print_r($sampleData, true) . "</pre>";
    } else {
        echo "<p>La table 'standings' est vide.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données</h2>";
    echo "<p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur SQL:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
    
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>";
}

// Afficher un lien pour retourner au tableau de bord
echo "<p><a href='admin/dashboard.php'>&larr; Retour au tableau de bord</a></p>";
?>
