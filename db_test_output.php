<?php
// Simple database test with direct output

// Set headers for plain text output
header('Content-Type: text/plain');

// Database configuration
$host = 'yamanote.proxy.rlwy.net';
$db   = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$port = '58372';

// Function to test connection
function testConnection($host, $db, $user, $pass, $port) {
    try {
        // Connect to database
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $start = microtime(true);
        $pdo = new PDO($dsn, $user, $pass, $options);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        echo "✅ Connected to database in {$time}ms\n";
        
        // Get server version
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "- MySQL Version: $version\n";
        
        // Check SSL status
        $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
        echo "- SSL: " . ($ssl['Value'] ? 'Enabled (' . $ssl['Value'] . ')' : 'Disabled') . "\n";
        
        // List tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "\n=== Tables in database ===\n";
        
        if (empty($tables)) {
            echo "No tables found in the database.\n";
        } else {
            foreach ($tables as $i => $table) {
                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                echo sprintf("%2d. %-30s (%d rows)\n", $i + 1, $table, $count);
                
                // Show first 2 columns of the table
                $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
                echo "   Columns: " . implode(', ', array_slice($columns, 0, 5));
                if (count($columns) > 5) {
                    echo "... (" . (count($columns) - 5) . " more)";
                }
                echo "\n\n";
            }
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
echo "=== Database Connection Test ===\n";
echo "Host: $host\n";
echo "Database: $db\n";
echo "User: $user\n";
echo "Port: $port\n\n";

testConnection($host, $db, $user, $pass, $port);

echo "\n=== Test Complete ===\n";
