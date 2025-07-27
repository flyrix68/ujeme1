<?php
// Test database connection and configuration
require_once __DIR__ . '/includes/db-config.php';

echo "Testing database connection...\n";

try {
    // Try to get database connection
    $pdo = DatabaseConfig::getConnection();
    echo "✓ Successfully connected to the database\n";
    
    // Test a simple query
    $testQuery = $pdo->query("SELECT 1");
    if ($testQuery->fetchColumn() == 1) {
        echo "✓ Database query test successful\n";
    } else {
        echo "✗ Database query test failed\n";
    }
    
    // Check if matches table exists and has data
    try {
        $matchCount = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
        echo "✓ Matches table exists with $matchCount records\n";
    } catch (PDOException $e) {
        echo "✗ Error accessing matches table: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    
    // Output database configuration (without sensitive data)
    echo "\nDatabase Configuration:\n";
    echo "Host: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "\n";
    echo "Database: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "\n";
    echo "SSL Certificate: " . (file_exists(__DIR__ . '/includes/cacert.pem') ? 'Found' : 'Not found') . "\n";
}

echo "\nPHP Version: " . phpversion() . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
?>
