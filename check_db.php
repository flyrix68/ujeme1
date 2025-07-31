<?php
// Simple database connection test with direct output

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'host' => 'yamanote.proxy.rlwy.net',
    'db'   => 'railway',
    'user' => 'root',
    'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'port' => '58372'
];

// Function to test connection
function testConnection($config) {
    echo "Testing connection to: {$config['user']}@{$config['host']}:{$config['port']}/{$config['db']}\n";
    
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        echo "✅ Successfully connected in {$time}ms\n";
        
        // Get server version
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "- MySQL Version: $version\n";
        
        // Check SSL status
        $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
        echo "- SSL: " . ($ssl['Value'] ? 'Enabled (' . $ssl['Value'] . ')' : 'Disabled') . "\n";
        
        // Get list of tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "- Tables found: " . count($tables) . "\n";
        
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "  - $table ($count rows)\n";
        }
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n\n";
        
        // Common error codes and solutions
        $solutions = [
            '2002' => "Cannot connect to database server. Check if MySQL is running and the host/port are correct.",
            '1045' => "Access denied. Verify username and password.",
            '1044' => "Access denied for user to database. Check database permissions.",
            '1049' => "Database does not exist. Check database name.",
            '2006' => "MySQL server has gone away. The server might have crashed or been restarted.",
            '2013' => "Lost connection to MySQL server. Check network connectivity.",
            'default' => "Check your database configuration and server status."
        ];
        
        $errorCode = (string)$e->getCode();
        $solution = $solutions[$errorCode] ?? $solutions['default'];
        echo "💡 Suggestion: $solution\n";
        
        return false;
    }
}

// Run the test
echo "=== Database Connection Test ===\n\n";
testConnection($config);

echo "\n=== Test Complete ===\n";
