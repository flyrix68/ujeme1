<?php
// List tables in the database

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
    echo "Tables in database '$db':\n\n";
    
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        foreach ($tables as $table) {
            // Get row count for each table
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "- $table ($count rows)\n";
            
            // Show first 3 column names
            $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $sampleColumns = array_slice($columns, 0, 3);
            echo "  Columns: " . implode(', ', $sampleColumns);
            if (count($columns) > 3) {
                echo "... (" . (count($columns) - 3) . " more)";
            }
            echo "\n\n";
        }
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
