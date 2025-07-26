<?php
require 'includes/db-config.php';

echo "=== TEST DE CONNEXION À LA BASE DE DONNÉES ===\n";

try {
    // 1. Tester la connexion
    echo "1. Connexion à la base de données... ";
    $pdo = DatabaseConfig::getConnection();
    echo "✅ Réussi!\n";
    
    // 2. Vérifier si la table classement existe
    echo "2. Vérification de l'existence de la table 'classement'... ";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ La table existe.\n";
        
        // Afficher la structure de la table
        echo "\n=== STRUCTURE DE LA TABLE 'classement' ===\n";
        $stmt = $pdo->query("DESCRIBE classement");
        echo str_pad("Champ", 25) . str_pad("Type", 20) . "Null\n";
        echo str_repeat("-", 50) . "\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo str_pad($row['Field'], 25) . 
                 str_pad($row['Type'], 20) . 
                 $row['Null'] . "\n";
        }
        
        // Compter les enregistrements
        $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\nNombre d'enregistrements : $count\n";
        
        // Afficher les 5 premiers enregistrements
        if ($count > 0) {
            echo "\n=== PREMIERS ENREGISTREMENTS ===\n";
            $rows = $pdo->query("SELECT * FROM classement LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            print_r($rows);
        }
    } else {
        echo "❌ La table 'classement' n'existe pas.\n";
        
        // Tenter de créer la table
        echo "\nTentative de création de la table 'classement'... ";
        $sql = "CREATE TABLE IF NOT EXISTS classement (
            id INT AUTO_INCREMENT PRIMARY KEY,
            saison VARCHAR(20) NOT NULL,
            competition VARCHAR(50) NOT NULL,
            poule_id INT NOT NULL,
            nom_equipe VARCHAR(100) NOT NULL,
            matchs_joues INT DEFAULT 0,
            matchs_gagnes INT DEFAULT 0,
            matchs_nuls INT DEFAULT 0,
            matchs_perdus INT DEFAULT 0,
            buts_pour INT DEFAULT 0,
            buts_contre INT DEFAULT 0,
            difference_buts INT DEFAULT 0,
            points INT DEFAULT 0,
            forme VARCHAR(5) DEFAULT '',
            logo VARCHAR(255) DEFAULT '',
            UNIQUE KEY unique_team (saison, competition, poule_id, nom_equipe)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $result = $pdo->exec($sql);
        
        if ($result !== false) {
            echo "✅ Table créée avec succès!\n";
            
            // Insérer des données de test
            echo "\nInsertion de données de test...\n";
            $testData = [
                ['saison' => '2024-2025', 'competition' => 'tournoi', 'poule_id' => 1, 'nom_equipe' => 'ABOUTOU 01 FC', 'points' => 4],
                ['saison' => '2024-2025', 'competition' => 'tournoi', 'poule_id' => 1, 'nom_equipe' => 'NGALWA FC', 'points' => 1]
            ];
            
            $inserted = 0;
            foreach ($testData as $data) {
                $sql = "INSERT INTO classement (saison, competition, poule_id, nom_equipe, points) 
                        VALUES (:saison, :competition, :poule_id, :nom_equipe, :points)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($data)) {
                    $inserted++;
                }
            }
            
            echo "Données de test insérées : $inserted/" . count($testData) . "\n";
            
            // Afficher les données insérées
            $rows = $pdo->query("SELECT * FROM classement")->fetchAll(PDO::FETCH_ASSOC);
            echo "\n=== DONNÉES DANS LA TABLE 'classement' ===\n";
            print_r($rows);
            
        } else {
            echo "❌ Échec de la création de la table.\n";
            $error = $pdo->errorInfo();
            echo "Erreur SQL: " . ($error[2] ?? 'Inconnue') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    
    if (isset($pdo) && $pdo->errorInfo()) {
        $error = $pdo->errorInfo();
        echo "Erreur PDO: " . ($error[2] ?? 'Inconnue') . "\n";
    }
}

echo "\n=== FIN DU TEST ===\n";
