<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database test...\n";

// Include the database configuration
require_once 'includes/db-config.php';

try {
    echo "Getting database connection...\n";
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful!\n";
    
    // Test a simple query
    echo "\nTesting simple query...\n";
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Query result: " . print_r($result, true) . "\n";
    
    // Test if tables exist
    echo "\nChecking if tables exist...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Available tables: " . implode(", ", $tables) . "\n";
    
    // Test matches table specifically
    if (in_array('matches', $tables)) {
        echo "\nTesting matches table...\n";
        $count = $pdo->query("SELECT COUNT(*) as count FROM matches")->fetch(PDO::FETCH_ASSOC);
        echo "Total matches: " . $count['count'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "\nPDO Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    if (strpos($e->getMessage(), 'SQLSTATE[') === 0) {
        preg_match('/SQLSTATE\[([^\]]*)\]/', $e->getMessage(), $matches);
        $errorCode = $matches[1] ?? '';
        echo "SQL State: " . $errorCode . "\n";
    }
} catch (Exception $e) {
    echo "\nGeneral Error: " . $e->getMessage() . "\n";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "\nTest completed.\n";
