<?php
// Simple script to check database SSL status

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'yamanote.proxy.rlwy.net';
$port = '58372';
$dbname = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';

// Function to test connection
function testConnection($dsn, $user, $pass, $options, $testName) {
    echo "\n=== $testName ===\n";
    
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get SSL status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Connected successfully!\n";
        echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
        
        // List a table to verify the connection
        $tables = $pdo->query('SHOW TABLES LIMIT 1')->fetch(PDO::FETCH_COLUMN);
        echo "- First table: " . ($tables ?: 'No tables found') . "\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test 1: Basic connection without SSL
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
testConnection($dsn, $user, $pass, [], "Test 1: Basic connection without SSL");

// Test 2: Connection with SSL options but no verification
$options = [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
];
testConnection($dsn, $user, $pass, $options, "Test 2: With SSL options (no verification)");

// Test 3: Try with sslmode in DSN
$dsn_ssl = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";
testConnection($dsn_ssl, $user, $pass, [], "Test 3: With sslmode=REQUIRED in DSN");

// Test 4: Check server SSL capabilities
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "\n=== Server SSL Capabilities ===\n";
    
    // Check SSL related variables
    $sslVars = [
        'have_ssl', 'have_openssl', 'ssl_cert', 'ssl_key', 'ssl_ca',
        'tls_version', 'version_ssl_library', 'version_compile_os'
    ];
    
    $results = [];
    foreach ($sslVars as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $results[] = $result;
        }
    }
    
    echo "SSL-related server variables:\n";
    foreach ($results as $row) {
        echo "- {$row['Variable_name']} = {$row['Value']}\n";
    }
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS WHERE Variable_name LIKE "Ssl_%"')->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "\nSSL Status:\n";
    foreach ($sslStatus as $key => $value) {
        if (!empty($value)) {
            echo "- $key = $value\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Failed to check server SSL capabilities: " . $e->getMessage() . "\n";
}
