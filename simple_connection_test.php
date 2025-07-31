<?php
// Simple database connection test with detailed error reporting

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'yamanote.proxy.rlwy.net';
$port = '58372';
$dbname = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';

// Basic connection test
try {
    echo "=== Testing Basic Connection ===\n";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Get server info
    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $sslCipher = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC)['Value'];
    $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC)['Value'];
    
    echo "✅ Connected successfully!\n";
    echo "- MySQL Version: $version\n";
    echo "- SSL Cipher: " . ($sslCipher ?: 'Not in use') . "\n";
    echo "- SSL Version: " . ($sslVersion ?: 'Not in use') . "\n";
    
    // List tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "\n=== Database Tables (" . count($tables) . ") ===\n";
    foreach (array_slice($tables, 0, 10) as $table) {
        echo "- $table\n";
    }
    if (count($tables) > 10) {
        echo "... and " . (count($tables) - 10) . " more\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Show more detailed error information
    echo "\n=== Error Details ===\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
