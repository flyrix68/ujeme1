<?php
// Simple script to list all tables in the database

try {
    // Database configuration
    $host = 'yamanote.proxy.rlwy.net';
    $db   = 'railway';
    $user = 'root';
    $pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    $port = '58372';
    
    // Connect to database
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Get list of tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        echo "Tables in database '$db':\n";
        echo "------------------------\n";
        
        foreach ($tables as $i => $table) {
            // Get row count for each table
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo sprintf("%2d. %-30s (%d rows)\n", $i + 1, $table, $count);
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
