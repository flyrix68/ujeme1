<?php
// Database Analyzer Script

try {
    // Database configuration
    $config = [
        'host' => 'yamanote.proxy.rlwy.net',
        'db'   => 'railway',
        'user' => 'root',
        'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
        'port' => '58372',
        'charset' => 'utf8mb4'
    ];
    
    // Connect to database
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    
    // Get database information
    echo "=== Database Information ===\n";
    echo "Host: {$config['host']}:{$config['port']}\n";
    echo "Database: {$config['db']}\n";
    echo "User: {$config['user']}\n";
    
    // Get server version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "MySQL Version: $version\n\n";
    
    // Get list of tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        echo "=== Tables in database '{$config['db']}' ===\n\n";
        
        foreach ($tables as $table) {
            echo str_repeat("=", 80) . "\n";
            echo "TABLE: $table\n";
            echo str_repeat("=", 80) . "\n\n";
            
            // Get row count
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "ROWS: $count\n\n";
            
            // Get table structure
            echo "COLUMNS:\n";
            $columns = $pdo->query("SHOW FULL COLUMNS FROM `$table`")->fetchAll();
            
            // Display column information
            echo str_repeat("-", 100) . "\n";
            printf("%-20s %-15s %-10s %-8s %-10s %-30s\n", 
                   'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
            echo str_repeat("-", 100) . "\n";
            
            foreach ($columns as $col) {
                printf("%-20s %-15s %-10s %-8s %-10s %-30s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'],
                    $col['Key'],
                    $col['Default'] ?? 'NULL',
                    $col['Extra']
                );
            }
            echo "\n";
            
            // Show sample data (first 3 rows)
            if ($count > 0) {
                echo "SAMPLE DATA (first 3 rows):\n";
                echo str_repeat("-", 100) . "\n";
                
                $sampleData = $pdo->query("SELECT * FROM `$table` LIMIT 3")->fetchAll();
                
                if (!empty($sampleData)) {
                    // Print headers
                    $headers = array_keys($sampleData[0]);
                    foreach ($headers as $header) {
                        printf("%-20s ", substr($header, 0, 20));
                    }
                    echo "\n" . str_repeat("-", 20 * count($headers)) . "\n";
                    
                    // Print data
                    foreach ($sampleData as $row) {
                        foreach ($row as $value) {
                            $display = is_string($value) ? substr($value, 0, 20) : $value;
                            printf("%-20s ", $display);
                        }
                        echo "\n";
                    }
                }
                echo "\n";
            }
            
            // Show indexes
            echo "INDEXES:\n";
            $indexes = $pdo->query("SHOW INDEX FROM `$table`")->fetchAll();
            
            if (empty($indexes)) {
                echo "No indexes found.\n";
            } else {
                $currentIndex = '';
                foreach ($indexes as $index) {
                    if ($currentIndex !== $index['Key_name']) {
                        if ($currentIndex !== '') echo "\n";
                        $currentIndex = $index['Key_name'];
                        $unique = $index['Non_unique'] ? 'NO' : 'YES';
                        echo "- {$index['Key_name']} (Unique: $unique, Type: {$index['Index_type']})\n";
                        echo "  Columns: ";
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
            
            // Show foreign keys if any
            try {
                $fks = $pdo->query("
                    SELECT 
                        COLUMN_NAME, 
                        REFERENCED_TABLE_NAME, 
                        REFERENCED_COLUMN_NAME,
                        CONSTRAINT_NAME
                    FROM 
                        INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE 
                        TABLE_SCHEMA = '{$config['db']}' 
                        AND TABLE_NAME = '$table'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                ")->fetchAll();
                
                if (!empty($fks)) {
                    echo "\nFOREIGN KEYS:\n";
                    foreach ($fks as $fk) {
                        echo "- {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
                    }
                }
            } catch (Exception $e) {
                // Ignore if we can't get foreign key info
            }
            
            echo "\n\n";
        }
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
