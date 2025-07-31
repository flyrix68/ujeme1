<?php
// Database Schema Dumper

try {
    // Database configuration
    $config = [
        'host' => 'yamanote.proxy.rlwy.net',
        'db'   => 'railway',
        'user' => 'root',
        'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
        'port' => '58372',
        'output_file' => 'database_schema.txt'
    ];
    
    // Connect to database
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    
    // Start output buffering
    ob_start();
    
    // Output header
    echo "=== DATABASE SCHEMA DUMP ===\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "Database: {$config['db']}@{$config['host']}:{$config['port']}\n\n";
    
    // Get list of tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        foreach ($tables as $table) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "TABLE: $table\n";
            echo str_repeat("=", 80) . "\n\n";
            
            // Get row count
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "ROWS: $count\n\n";
            
            // Get table structure
            echo "COLUMNS:\n";
            $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
            
            // Display column information
            echo str_repeat("-", 120) . "\n";
            printf("%-20s %-20s %-10s %-8s %-10s %-10s\n", 
                   'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
            echo str_repeat("-", 120) . "\n";
            
            foreach ($columns as $col) {
                printf("%-20s %-20s %-10s %-8s %-10s %-10s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'],
                    $col['Key'],
                    $col['Default'] ?? 'NULL',
                    $col['Extra']
                );
            }
            
            // Show indexes
            echo "\nINDEXES:\n";
            $indexes = $pdo->query("SHOW INDEX FROM `$table`")->fetchAll();
            
            if (empty($indexes)) {
                echo "No indexes.\n";
            } else {
                $currentIndex = '';
                foreach ($indexes as $index) {
                    if ($currentIndex !== $index['Key_name']) {
                        if ($currentIndex !== '') echo "\n";
                        $currentIndex = $index['Key_name'];
                        $unique = $index['Non_unique'] ? 'NO' : 'YES';
                        echo "- {$index['Key_name']} (Unique: $unique, Type: {$index['Index_type']}): ";
                    } else {
                        echo ", ";
                    }
                    echo $index['Column_name'];
                    if ($index['Sub_part']) {
                        echo "({$index['Sub_part']})";
                    }
                }
                echo "\n";
            }
        }
    }
    
    // Get the output and write to file
    $output = ob_get_clean();
    file_put_contents($config['output_file'], $output);
    
    echo "Database schema has been saved to {$config['output_file']}\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
