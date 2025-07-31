<?php
// Test script to verify SSL enforcement in database connection

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration
require_once __DIR__ . '/includes/db-config.php';

// Test the database connection with SSL
function testSslConnection() {
    try {
        echo "=== Testing Database Connection with SSL Enforcement ===\n";
        
        // Get a database connection
        $pdo = DatabaseSSL::getInstance()->getConnection();
        
        // Check if we're actually using SSL
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Connection successful!\n";
        echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
        
        // Check if SSL is being used
        if (empty($sslStatus['Value'])) {
            echo "❌ WARNING: Connection is not using SSL!\n";
        } else {
            echo "✅ Connection is secured with SSL\n";
        }
        
        // List tables to verify the connection is working
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "\nTables in database (" . count($tables) . "):\n";
        foreach (array_slice($tables, 0, 5) as $table) {
            echo "- $table\n";
        }
        if (count($tables) > 5) {
            echo "... and " . (count($tables) - 5) . " more\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        
        // Common error codes and solutions
        $solutions = [
            '2002' => "Cannot connect to database server. Check if MySQL is running and the host/port are correct.",
            '1045' => "Access denied. Verify username and password.",
            '1044' => "Access denied for user to database. Check database permissions.",
            '1049' => "Database does not exist. Check database name.",
            '2006' => "MySQL server has gone away. The server might have crashed or been restarted.",
            '2013' => "Lost connection to MySQL server. Check network connectivity.",
            '2026' => "SSL connection error. Check SSL configuration and certificate.",
            'default' => "Check your database configuration and server status."
        ];
        
        $errorCode = (string)$e->getCode();
        $solution = $solutions[$errorCode] ?? $solutions['default'];
        echo "💡 Suggestion: $solution\n";
    }
}

// Run the test
testSslConnection();
