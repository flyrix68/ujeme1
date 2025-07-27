<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database test...\n";

// Include the database configuration
require_once __DIR__ . '/includes/db-config.php';

try {
    echo "Getting database connection...\n";
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful!\n";
    
    // Test a simple query
    echo "\nTesting simple query...\n";
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Query result: " . print_r($result, true) . "\n";
    
    // Check if matches table exists
    echo "\nChecking if 'matches' table exists...\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'matches'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('matches', $tables)) {
        echo "✓ 'matches' table exists\n";
        
        // Count matches
        $count = $pdo->query("SELECT COUNT(*) as count FROM matches")->fetch(PDO::FETCH_ASSOC);
        echo "- Total matches in database: " . $count['count'] . "\n";
        
        // Get some sample data
        echo "\nSample match data (latest 3):\n";
        $sampleData = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, match_date, status FROM matches ORDER BY match_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        print_r($sampleData);
    } else {
        echo "✗ 'matches' table does not exist in the database\n";
        
        // List all tables for debugging
        echo "\nListing all tables in the database:\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        print_r($tables);
    }
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    
    if (isset($pdo)) {
        echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}

echo "\nTest completed.\n";
?>
