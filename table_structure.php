<?php
// Script to show the structure of all tables in the database

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
        foreach ($tables as $table) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "TABLE: $table\n";
            echo str_repeat("=", 80) . "\n\n";
            
            // Get table structure
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
            
            // Show sample data (first 2 rows)
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            if ($count > 0) {
                echo "\nSAMPLE DATA (first 2 rows):\n";
                echo str_repeat("-", 80) . "\n";
                
                $sampleData = $pdo->query("SELECT * FROM `$table` LIMIT 2")->fetchAll();
                
                // Print headers
                if (!empty($sampleData)) {
                    $headers = array_keys($sampleData[0]);
                    foreach ($headers as $header) {
                        printf("%-15s ", substr($header, 0, 15));
                    }
                    echo "\n" . str_repeat("-", 15 * count($headers)) . "\n";
                    
                    // Print data
                    foreach ($sampleData as $row) {
                        foreach ($row as $value) {
                            $display = is_string($value) ? substr($value, 0, 15) : $value;
                            printf("%-15s ", $display);
                        }
                        echo "\n";
                    }
                }
            } else {
                echo "\nNo data in table.\n";
            }
            
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
