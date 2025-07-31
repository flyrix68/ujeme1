<?php
// Simple database connection test with file output

// Output file
$logFile = __DIR__ . '/db_test.log';

// Database configuration
$config = [
    'host' => 'yamanote.proxy.rlwy.net',
    'db'   => 'railway',
    'user' => 'root',
    'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'port' => '58372'
];

// Function to write to log file
function writeLog($message, $file) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($file, $logMessage, FILE_APPEND);
    echo $logMessage;
}

// Clear previous log
if (file_exists($logFile)) {
    unlink($logFile);
}

writeLog("=== Starting Database Connection Test ===", $logFile);
writeLog("Host: {$config['host']}:{$config['port']}", $logFile);
writeLog("Database: {$config['db']}", $logFile);
writeLog("User: {$config['user']}", $logFile);

// Test connection
try {
    writeLog("Attempting to connect to database...", $logFile);
    
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $start = microtime(true);
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    writeLog("✅ Connected to database in {$time}ms", $logFile);
    
    // Get server version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    writeLog("- MySQL Version: $version", $logFile);
    
    // Check SSL status
    $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
    writeLog("- SSL: " . ($ssl['Value'] ? 'Enabled (' . $ssl['Value'] . ')' : 'Disabled'), $logFile);
    
    // List tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    writeLog("\n=== Tables in database ===", $logFile);
    
    if (empty($tables)) {
        writeLog("No tables found in the database.", $logFile);
    } else {
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            writeLog("- $table ($count rows)", $logFile);
            
            // Show first 3 columns
            $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $sampleColumns = array_slice($columns, 0, 3);
            writeLog("  Columns: " . implode(', ', $sampleColumns) . 
                   (count($columns) > 3 ? "... (" . (count($columns) - 3) . " more)" : ""), $logFile);
        }
    }
    
} catch (PDOException $e) {
    writeLog("❌ Connection failed: " . $e->getMessage(), $logFile);
    writeLog("Error code: " . $e->getCode(), $logFile);
    
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
    writeLog("💡 Suggestion: $solution", $logFile);
}

writeLog("\n=== Test Complete ===\n", $logFile);
echo "\nTest complete. Check $logFile for details.\n";
