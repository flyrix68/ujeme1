<?php
// Test script for SSL database connection

// Include the database configuration
require_once __DIR__ . '/includes/db-ssl.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Database Connection with SSL ===\n\n";

try {
    // Get database instance
    $db = DatabaseSSL::getInstance();
    $pdo = $db->getConnection();
    
    // Get server info
    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    echo "✅ Successfully connected to the database!\n";
    echo "- MySQL Version: $version\n";
    
    // Check SSL status
    $sslStatus = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_ASSOC);
    $sslVersion = $pdo->query("SHOW STATUS LIKE 'Ssl_version'")->fetch(PDO::FETCH_ASSOC);
    
    echo "- SSL Cipher: " . ($sslStatus['Value'] ?? 'Not in use') . "\n";
    echo "- SSL Version: " . ($sslVersion['Value'] ?? 'Not in use') . "\n\n";
    
    // Test a simple query
    $result = $db->getValue("SELECT 1 as test");
    echo "- Test query result: " . ($result ? 'Success' : 'Failed') . "\n";
    
    // List all tables
    echo "\n=== Database Tables ===\n";
    $tables = $db->getRows("SHOW TABLES");
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        echo "Found " . count($tables) . " tables.\n";
        foreach (array_slice($tables, 0, 5) as $table) {
            $tableName = reset($table); // Get the first value
            echo "- $tableName\n";
        }
        if (count($tables) > 5) {
            echo "... and " . (count($tables) - 5) . " more\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Show detailed error in development
    if (in_array(ini_get('display_errors'), ['1', 'On', 'true', 1, true])) {
        echo "\nDebug Info:\n";
        echo "- File: " . $e->getFile() . "\n";
        echo "- Line: " . $e->getLine() . "\n";
        if ($e->getPrevious()) {
            echo "- Previous: " . $e->getPrevious()->getMessage() . "\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
