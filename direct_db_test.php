<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting direct database connection test...\n";

// Database credentials from db-config.php
$dbHost = 'yamanote.proxy.rlwy.net';
$dbPort = 58372;
$dbUser = 'root';
$dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$dbName = 'railway';

try {
    echo "Connecting to database...\n";
    
    // Try to connect without SSL first
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    // Try without SSL first
    echo "Attempting connection without SSL...\n";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    echo "✓ Connected to database successfully!\n";
    
    // Test a simple query
    echo "\nTesting simple query...\n";
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Query result: " . print_r($result, true) . "\n";
    
    // Check if matches table exists
    echo "\nChecking if 'matches' table exists...\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'matches'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('matches', $tables)) {
        echo "✓ 'matches' table exists\n";
        
        // Count matches
        $count = $pdo->query("SELECT COUNT(*) as count FROM matches")->fetch(PDO::FETCH_ASSOC);
        echo "- Total matches in database: " . $count['count'] . "\n";
        
        // Get some sample data
        echo "\nSample match data (latest 3):\n";
        $sampleData = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, match_date, status FROM matches ORDER BY match_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        print_r($sampleData);
    } else {
        echo "✗ 'matches' table does not exist in the database\n";
        
        // List all tables for debugging
        echo "\nListing all tables in the database:\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        print_r($tables);
    }
    
} catch (PDOException $e) {
    echo "\nERROR: Database connection failed\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Additional debugging info
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\nPossible issue: Incorrect database credentials\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "\nPossible issue: Database does not exist\n";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "\nPossible issue: Database server is not running or not accessible\n";
        echo "Trying to ping the database server...\n";
        system("ping -n 4 " . escapeshellarg($dbHost));
    } elseif (strpos($e->getMessage(), 'SSL') !== false) {
        echo "\nPossible SSL connection issue. Trying without SSL...\n";
        // Try again without SSL
        try {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            echo "✓ Connected to database successfully without SSL verification!\n";
        } catch (PDOException $e2) {
            echo "Still unable to connect: " . $e2->getMessage() . "\n";
        }
    }
}

echo "\nTest completed.\n";
?>
