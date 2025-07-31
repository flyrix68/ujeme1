<?php
require_once __DIR__ . '/includes/db-ssl.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier si la table standings existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'standings'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'standings' n'existe pas dans la base de données.\n");
    }
    
    // 2. Afficher la structure de la table
    echo "=== STRUCTURE DE LA TABLE 'standings' ===\n";
    $columns = $pdo->query("DESCRIBE standings")->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_pad("Champ", 20) . str_pad("Type", 20) . "Null\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($columns as $col) {
        echo str_pad($col['Field'], 20) . 
             str_pad($col['Type'], 20) . 
             $col['Null'] . "\n";
    }
    
    // 3. Afficher les statistiques de base
    echo "\n=== STATISTIQUES ===\n";
    
    // Nombre total d'entrées
    $count = $pdo->query("SELECT COUNT(*) as count FROM standings")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Nombre total d'entrées: $count\n";
    
    // Nombre de saisons uniques
    $seasons = $pdo->query("SELECT COUNT(DISTINCT saison) as count FROM standings")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Nombre de saisons uniques: $seasons\n";
    
    // Liste des saisons
    echo "\nListe des saisons:\n";
    $seasons = $pdo->query("SELECT DISTINCT saison FROM standings ORDER BY saison DESC")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($seasons as $season) {
        echo "- $season\n";
    }
    
    // 4. Afficher un exemple de classement pour la dernière saison
    if (!empty($seasons)) {
        $latestSeason = $seasons[0];
        echo "\n=== EXEMPLE DE CLASSEMENT (saison $latestSeason) ===\n";
        
        // Récupérer les compétitions pour cette saison
        $competitions = $pdo->prepare("
            SELECT DISTINCT competition 
            FROM standings 
            WHERE saison = ? 
            ORDER BY competition
        ")->execute([$latestSeason]);
        
        $competitions = $pdo->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($competitions as $competition) {
            echo "\nCompétition: $competition\n";
            
            // Récupérer les poules pour cette compétition
            $poules = $pdo->prepare("
                SELECT DISTINCT poule_id 
                FROM standings 
                WHERE saison = ? AND competition = ?
                ORDER BY poule_id
            ")->execute([$latestSeason, $competition]);
            
            $poules = $pdo->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($poules as $poule) {
                echo "\nPoule $poule:\n";
                
                // Récupérer le classement
                $standings = $pdo->prepare("
                    SELECT * 
                    FROM standings 
                    WHERE saison = ? AND competition = ? AND poule_id = ?
                    ORDER BY position ASC
                ")->execute([$latestSeason, $competition, $poule]);
                
                $standings = $pdo->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($standings)) {
                    // Afficher les en-têtes
                    $headers = array_keys($standings[0]);
                    foreach ($headers as $header) {
                        echo str_pad($header, 15) . " | ";
                    }
                    echo "\n" . str_repeat("-", 15 * count($headers) + 10) . "\n";
                    
                    // Afficher les données
                    foreach ($standings as $row) {
                        foreach ($row as $value) {
                            $display = strlen($value) > 12 ? substr($value, 0, 12) . '...' : $value;
                            echo str_pad($display, 15) . " | ";
                        }
                        echo "\n";
                    }
                } else {
                    echo "Aucune donnée pour cette poule.\n";
                }
            }
        }
    }
    
    // 5. Vérifier la relation avec la table classement
    echo "\n=== RELATION AVEC LA TABLE 'classement' ===\n";
    
    // Vérifier si la table classement existe
    $classementExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if ($classementExists) {
        echo "La table 'classement' existe. Vérification des correspondances...\n";
        
        // Vérifier les équipes dans standings mais pas dans classement
        $missingTeams = $pdo->query("
            SELECT DISTINCT s.nom_equipe 
            FROM standings s
            LEFT JOIN classement c ON s.nom_equipe = c.nom_equipe
            WHERE c.nom_equipe IS NULL
            LIMIT 5
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($missingTeams)) {
            echo "\nÉquipes dans 'standings' mais pas dans 'classement' (exemples):\n";
            foreach ($missingTeams as $team) {
                echo "- $team\n";
            }
        } else {
            echo "Toutes les équipes de 'standings' sont présentes dans 'classement'.\n";
        }
    } else {
        echo "La table 'classement' n'existe pas.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
    echo "Code d'erreur: " . $e->getCode() . "\n";
    echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "Trace d'appel: " . $e->getTraceAsString() . "\n";
}
?>
