<?php
// Test script for the simple database connection

// Include the database configuration
require_once __DIR__ . '/includes/db-simple.php';

echo "=== Testing Database Connection ===\n\n";

try {
    // Get database instance
    $db = Database::getInstance();
    echo "✅ Successfully connected to the database!\n";
    
    // Get server version
    $version = $db->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
    echo "- MySQL Server Version: $version\n";
    
    // Check SSL status
    $sslStatus = $db->getRow("SHOW STATUS LIKE 'Ssl_cipher'");
    $sslVersion = $db->getRow("SHOW STATUS LIKE 'Ssl_version'");
    
    echo "- SSL Cipher: " . ($sslStatus['Value'] ?? 'Not in use') . "\n";
    echo "- SSL Version: " . ($sslVersion['Value'] ?? 'Not in use') . "\n\n";
    
    // List all tables
    echo "=== Database Tables ===\n";
    $tables = $db->getRows("SHOW TABLES");
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        foreach ($tables as $table) {
            $tableName = reset($table); // Get the first value
            echo "- $tableName\n";
        }
    }
    
    // Example: Count users (if users table exists)
    try {
        $userCount = $db->getRow("SELECT COUNT(*) as count FROM users");
        echo "\nTotal users: " . ($userCount['count'] ?? 0) . "\n";
    } catch (Exception $e) {
        // Table might not exist, ignore
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Show detailed error in development
    if (in_array(ini_get('display_errors'), ['1', 'On', 'true', 1, true])) {
        echo "\nDebug Info:\n";
        echo "- Error Code: " . $e->getCode() . "\n";
        echo "- File: " . $e->getFile() . "\n";
        echo "- Line: " . $e->getLine() . "\n";
        echo "- Trace: " . $e->getTraceAsString() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
