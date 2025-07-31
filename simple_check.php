<?php
// Very simple database connection test

// Database config
$host = 'yamanote.proxy.rlwy.net';
$db   = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$port = '58372';

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // If we get here, connection was successful
    echo "✅ Connected to database successfully!\n";
    
    // Show tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "- $table ($count rows)\n";
    }
    
} catch (PDOException $e) {
    // Connection failed
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
