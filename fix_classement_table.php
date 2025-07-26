<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

echo "<h1>Vérification et correction de la table classement</h1>";

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier si la table classement existe
    echo "<h2>1. Vérification de l'existence de la table</h2>";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div style='color: orange;'>La table 'classement' n'existe pas. Création en cours...</div>";
        
        // Créer la table classement
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `classement` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `saison` varchar(50) NOT NULL,
            `competition` varchar(100) NOT NULL,
            `poule_id` int(11) NOT NULL,
            `nom_equipe` varchar(100) NOT NULL,
            `matchs_joues` int(11) NOT NULL DEFAULT '0',
            `matchs_gagnes` int(11) NOT NULL DEFAULT '0',
            `matchs_nuls` int(11) NOT NULL DEFAULT '0',
            `matchs_perdus` int(11) NOT NULL DEFAULT '0',
            `buts_pour` int(11) NOT NULL DEFAULT '0',
            `buts_contre` int(11) NOT NULL DEFAULT '0',
            `difference_buts` int(11) NOT NULL DEFAULT '0',
            `points` int(11) NOT NULL DEFAULT '0',
            `forme` varchar(10) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_classement` (`saison`,`competition`,`poule_id`,`nom_equipe`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($createTableSQL);
        echo "<div style='color: green;'>✓ La table 'classement' a été créée avec succès.</div>";
    } else {
        echo "<div style='color: green;'>✓ La table 'classement' existe déjà.</div>";
    }
    
    // 2. Vérifier et ajouter les colonnes manquantes
    echo "<h2>2. Vérification des colonnes</h2>";
    $requiredColumns = [
        'id' => 'int(11) NOT NULL AUTO_INCREMENT',
        'saison' => 'varchar(50) NOT NULL',
        'competition' => 'varchar(100) NOT NULL',
        'poule_id' => 'int(11) NOT NULL',
        'nom_equipe' => 'varchar(100) NOT NULL',
        'matchs_joues' => 'int(11) NOT NULL DEFAULT 0',
        'matchs_gagnes' => 'int(11) NOT NULL DEFAULT 0',
        'matchs_nuls' => 'int(11) NOT NULL DEFAULT 0',
        'matchs_perdus' => 'int(11) NOT NULL DEFAULT 0',
        'buts_pour' => 'int(11) NOT NULL DEFAULT 0',
        'buts_contre' => 'int(11) NOT NULL DEFAULT 0',
        'difference_buts' => 'int(11) NOT NULL DEFAULT 0',
        'points' => 'int(11) NOT NULL DEFAULT 0',
        'forme' => 'varchar(10) DEFAULT NULL',
        'created_at' => 'timestamp NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    $stmt = $pdo->query("DESCRIBE classement");
    $existingColumns = [];
    
    while ($column = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[$column['Field']] = $column;
    }
    
    $columnsAdded = 0;
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!array_key_exists($columnName, $existingColumns)) {
            $alterSQL = "ALTER TABLE `classement` ADD COLUMN `$columnName` $columnDef";
            
            // Gestion spéciale pour la colonne id qui doit être la clé primaire
            if ($columnName === 'id') {
                $alterSQL .= ", ADD PRIMARY KEY (`id`)";
            }
            
            $pdo->exec($alterSQL);
            echo "<div style='color: green;'>✓ Colonne ajoutée : $columnName ($columnDef)</div>";
            $columnsAdded++;
        } else {
            echo "<div>Colonne existante : $columnName</div>";
        }
    }
    
    if ($columnsAdded > 0) {
        echo "<div style='color: green; margin-top: 10px;'>✓ $columnsAdded colonne(s) ont été ajoutées avec succès.</div>";
    } else {
        echo "<div style='color: green; margin-top: 10px;'>✓ Toutes les colonnes requises sont déjà présentes.</div>";
    }
    
    // 3. Vérifier et ajouter l'index unique
    echo "<h2>3. Vérification des index</h2>";
    $indexExists = false;
    $stmt = $pdo->query("SHOW INDEX FROM classement WHERE Key_name = 'unique_classement'");
    
    if ($stmt->rowCount() === 0) {
        echo "<div style='color: orange;'>L'index unique n'existe pas. Création en cours...</div>";
        $pdo->exec("ALTER TABLE `classement` ADD UNIQUE `unique_classement` (`saison`, `competition`, `poule_id`, `nom_equipe`)");
        echo "<div style='color: green;'>✓ L'index unique a été créé avec succès.</div>";
    } else {
        echo "<div style='color: green;'>✓ L'index unique existe déjà.</div>";
    }
    
    // 4. Vérifier la clé primaire
    echo "<h2>4. Vérification de la clé primaire</h2>";
    $stmt = $pdo->query("SHOW KEYS FROM classement WHERE Key_name = 'PRIMARY'");
    
    if ($stmt->rowCount() === 0) {
        echo "<div style='color: orange;'>Aucune clé primaire trouvée. Ajout en cours...</div>";
        $pdo->exec("ALTER TABLE `classement` ADD PRIMARY KEY (`id`)");
        echo "<div style='color: green;'>✓ La clé primaire a été ajoutée avec succès.</div>";
    } else {
        echo "<div style='color: green;'>✓ La clé primaire existe déjà.</div>";
    }
    
    // 5. Vérifier et ajouter la colonne AUTO_INCREMENT si nécessaire
    $stmt = $pdo->query("SHOW COLUMNS FROM `classement` WHERE `Field` = 'id' AND `Extra` LIKE '%auto_increment%'");
    
    if ($stmt->rowCount() === 0) {
        echo "<div style='color: orange;'>La colonne 'id' n'a pas l'attribut AUTO_INCREMENT. Correction en cours...</div>";
        $pdo->exec("ALTER TABLE `classement` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT");
        echo "<div style='color: green;'>✓ La colonne 'id' a été modifiée avec succès pour inclure AUTO_INCREMENT.</div>";
    } else {
        echo "<div style='color: green;'>✓ La colonne 'id' a déjà l'attribut AUTO_INCREMENT.</div>";
    }
    
    echo "<h2 style='color: green; margin-top: 20px;'>✓ La table 'classement' est correctement configurée.</h2>
    <p><a href='admin/dashboard.php'>Retour au tableau de bord</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur lors de la vérification/correction de la table 'classement'</h2>";
    echo "<p><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur :</strong> " . htmlspecialchars($e->errorInfo[2]) . "</p>";
    }
    
    echo "<p><a href='admin/dashboard.php'>Retour au tableau de bord</a></p>";
}
?>
