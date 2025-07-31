<?php
// Test script to verify SSL database connection

// Include the database configuration
require_once __DIR__ . '/includes/db-config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to test database connection
function testDatabaseConnection() {
    echo "=== Testing Database Connection with SSL ===\n\n";
    
    try {
        // Get database connection with retry
        $pdo = DatabaseConfig::getConnection(3, 1);
        
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
        $result = $pdo->query("SELECT 1 as test")->fetch(PDO::FETCH_ASSOC);
        echo "- Test query result: " . ($result ? 'Success' : 'Failed') . "\n";
        
        // List all tables
        echo "\n=== Database Tables ===\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "No tables found in the database.\n";
        } else {
            echo "Found " . count($tables) . " tables.\n";
            foreach (array_slice($tables, 0, 5) as $table) {
                echo "- $table\n";
            }
            if (count($tables) > 5) {
                echo "... and " . (count($tables) - 5) . " more\n";
            }
        }
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        
        // Show detailed error in development
        if (in_array(ini_get('display_errors'), ['1', 'On', 'true', 1, true])) {
            echo "\nDebug Info:\n";
            echo "- File: " . $e->getFile() . "\n";
            echo "- Line: " . $e->getLine() . "\n";
            echo "- Trace: " . $e->getTraceAsString() . "\n";
        }
        
        return false;
    }
}

// Run the test
testDatabaseConnection();

echo "\n=== Test Complete ===\n";
