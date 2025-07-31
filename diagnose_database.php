<?php
require_once __DIR__ . '/includes/db-ssl.php';

function checkTable($pdo, $tableName) {
    echo "\n=== Vérification de la table '$tableName' ===\n";
    
    // Vérifier si la table existe
    $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "La table '$tableName' n'existe pas.\n";
        return false;
    }
    
    echo "- La table existe.\n";
    
    // Afficher la structure
    echo "\nStructure de la table :\n";
    $stmt = $pdo->query("DESCRIBE $tableName");
    echo str_pad("Champ", 20) . str_pad("Type", 20) . "Null\n";
    echo str_repeat("-", 50) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 20) . 
             $row['Null'] . "\n";
    }
    
    // Compter les enregistrements
    $count = $pdo->query("SELECT COUNT(*) as count FROM $tableName")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nNombre d'enregistrements : $count\n";
    
    // Afficher quelques exemples si la table n'est pas vide
    if ($count > 0) {
        $limit = min(3, $count);
        echo "\nExemple d'enregistrements ($limit premiers) :\n";
        $rows = $pdo->query("SELECT * FROM $tableName LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            print_r($row);
            echo "\n";
        }
    }
    
    return true;
}

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    echo "=== DIAGNOSTIC DE LA BASE DE DONNÉES ===\n";
    
    // Vérifier les tables importantes
    checkTable($pdo, 'classement');
    checkTable($pdo, 'standings');
    checkTable($pdo, 'matches');
    checkTable($pdo, 'teams');
    checkTable($pdo, 'match_processed');
    
    // Vérifier les matchs marqués comme traités
    $processedMatches = $pdo->query("SELECT * FROM match_processed")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== MATCHS DÉJÀ TRAITÉS ===\n";
    
    if (empty($processedMatches)) {
        echo "Aucun match n'a encore été traité.\n";
    } else {
        echo "Matchs déjà traités (" . count($processedMatches) . ") : ";
        $matchIds = array_column($processedMatches, 'match_id');
        echo implode(", ", $matchIds) . "\n";
    }
    
    // Vérifier les matchs terminés non traités
    $unprocessedMatches = $pdo->query("
        SELECT m.* 
        FROM matches m
        LEFT JOIN match_processed mp ON m.id = mp.match_id
        WHERE m.status = 'completed' 
        AND mp.match_id IS NULL
        ORDER BY m.match_date, m.match_time
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== MATCHS TERMINÉS NON TRAITÉS ===\n";
    
    if (empty($unprocessedMatches)) {
        echo "Tous les matchs terminés ont été traités.\n";
    } else {
        echo "Matchs terminés non traités (" . count($unprocessedMatches) . ") :\n";
        foreach ($unprocessedMatches as $match) {
            echo "- Match #{$match['id']}: {$match['team_home']} {$match['score_home']}-{$match['score_away']} {$match['team_away']} ({$match['match_date']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "\nERREUR : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "Trace d'appel : " . $e->getTraceAsString() . "\n";
}
?>
