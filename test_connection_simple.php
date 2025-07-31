<?php
// Simple database connection test script

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'yamanote.proxy.rlwy.net';
$port = '58372';
$dbname = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$caCertPath = __DIR__ . '/includes/cacert.pem';

// Test 1: Basic connection without SSL
try {
    echo "=== Test 1: Basic connection without SSL ===\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
    $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Connected successfully!\n";
    echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
    echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
    
    // List tables to verify connection
    $tables = $pdo->query('SHOW TABLES LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
    echo "- Tables: " . implode(', ', $tables) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}

// Test 2: Connection with SSL options
if (file_exists($caCertPath)) {
    try {
        echo "\n=== Test 2: Connection with SSL options ===\n";
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_SSL_CA => $caCertPath,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Check SSL status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Connected with SSL options!\n";
        echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
        
    } catch (PDOException $e) {
        echo "❌ SSL connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n⚠️ CA certificate not found at: $caCertPath\n";
}

// Test 3: Check server SSL capabilities
try {
    echo "\n=== Server SSL Information ===\n";
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check SSL variables
    $sslVars = [
        'have_ssl', 'have_openssl', 'ssl_cert', 'ssl_key', 'ssl_ca',
        'tls_version', 'version_ssl_library'
    ];
    
    foreach ($sslVars as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "- {$result['Variable_name']} = {$result['Value']}\n";
        }
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
