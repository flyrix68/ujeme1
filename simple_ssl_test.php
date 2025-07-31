<?php
// Simple SSL test script

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'yamanote.proxy.rlwy.net';
$port = '58372';
$dbname = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';

// Test connection without SSL first
try {
    echo "=== Testing connection WITHOUT SSL ===\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Connected without SSL\n";
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
    echo "SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test with SSL
try {
    echo "=== Testing connection WITH SSL ===\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Connected with SSL (unverified)\n";
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
    echo "SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
    
} catch (PDOException $e) {
    echo "❌ SSL connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
