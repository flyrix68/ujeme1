<?php
// Test script to verify different SSL connection modes

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'host' => getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net',
    'port' => getenv('DB_PORT') ?: '58372',
    'dbname' => getenv('DB_DATABASE') ?: 'railway',
    'user' => getenv('DB_USERNAME') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'ssl_ca' => getenv('DB_SSL_CA') ?: 'includes/cacert.pem'
];

// Test different SSL modes
$sslModes = [
    'DISABLED' => [
        'options' => [
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    ],
    'PREFERRED' => [
        'dsn_extra' => ';sslmode=PREFERRED',
        'options' => [
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    ],
    'REQUIRED' => [
        'dsn_extra' => ';sslmode=REQUIRED',
        'options' => [
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    ],
    'VERIFY_CA' => [
        'dsn_extra' => ';sslmode=VERIFY_CA',
        'options' => [
            PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/' . ltrim($config['ssl_ca'], '/'),
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ]
    ],
    'VERIFY_IDENTITY' => [
        'dsn_extra' => ';sslmode=VERIFY_IDENTITY',
        'options' => [
            PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/' . ltrim($config['ssl_ca'], '/'),
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ]
    ]
];

// Common PDO options
$commonOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Test each SSL mode
foreach ($sslModes as $mode => $settings) {
    echo "\n=== Testing SSL Mode: $mode ===\n";
    
    try {
        // Build DSN
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $settings['dsn_extra'] ?? ''
        );
        
        // Merge options
        $options = array_merge($commonOptions, $settings['options']);
        
        // Connect to database
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        // Check connection status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Connection successful in {$time}ms\n";
        echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
        
        // List a few tables to verify the connection is working
        $tables = $pdo->query('SHOW TABLES LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
        echo "- Sample tables: " . implode(', ', $tables) . "\n";
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
    }
    
    echo str_repeat("-", 50) . "\n";
}

echo "\n=== SSL Test Complete ===\n";
