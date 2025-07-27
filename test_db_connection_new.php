<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration
require_once 'includes/db-config.php';

try {
    // Get database connection
    $pdo = DatabaseConfig::getConnection();
    
    // Test query to count matches
    $stmt = $pdo->query('SELECT COUNT(*) as match_count FROM matches');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Successfully connected to the database.\n";
    echo "Number of matches in the database: " . ($result['match_count'] ?? '0') . "\n";
    
    // Test query to get current matches
    $currentMatches = $pdo->query("
        SELECT m.*, t1.nom as team_home_name, t2.nom as team_away_name, c.nom as competition_name
        FROM matches m
        LEFT JOIN equipes t1 ON m.team_home_id = t1.id
        LEFT JOIN equipes t2 ON m.team_away_id = t2.id
        LEFT JOIN competitions c ON m.competition_id = c.id
        WHERE m.status = 'en_cours'
        ORDER BY m.match_date ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent matches:\n";
    foreach ($currentMatches as $match) {
        echo sprintf(
            "%s vs %s - %s (%s)\n",
            $match['team_home_name'] ?? '?',
            $match['team_away_name'] ?? '?',
            $match['competition_name'] ?? '?',
            $match['status'] ?? '?'
        );
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
