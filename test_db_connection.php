&lt;?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database connection test...\n\n";

// Test if db-config.php exists and can be included
if (!@include('includes/db-config.php')) {
    die("ERROR: Could not include db-config.php. Please check the file exists and has the correct permissions.\n");
}

echo "✓ db-config.php loaded successfully\n";

// Test if DatabaseConfig class exists
if (!class_exists('DatabaseConfig')) {
    die("ERROR: DatabaseConfig class not found in db-config.php\n");
}

echo "✓ DatabaseConfig class found\n";

try {
    echo "\nAttempting to get database connection...\n";
    $pdo = DatabaseConfig::getConnection();
    echo "✓ Database connection established successfully\n\n";
    
    // Test a simple query
    echo "Testing simple query...\n";
    $result = $pdo->query("SELECT 1 as test_value")->fetch(PDO::FETCH_ASSOC);
    echo "✓ Simple query executed successfully\n";
    echo "Result: " . print_r($result, true) . "\n\n";
    
    // Check if matches table exists
    echo "Checking if 'matches' table exists...\n";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'matches'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ 'matches' table exists\n";
        
        // Count matches
        $count = $pdo->query("SELECT COUNT(*) as count FROM matches")->fetch(PDO::FETCH_ASSOC);
        echo "- Total matches in database: " . $count['count'] . "\n";
        
        // Get some sample data
        echo "\nSample match data:\n";
        $sampleData = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, match_date, status FROM matches ORDER BY match_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        print_r($sampleData);
    } else {
        echo "✗ 'matches' table does not exist in the database\n";
    }
    
} catch(PDOException $e) {
    echo "\nERROR: Database connection failed\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Try to get more specific error information
    if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        echo "\nSQL State: " . $e->getCode() . "\n";
    }
    
    // Check common connection issues
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\nPossible issue: Incorrect database credentials\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "\nPossible issue: Database does not exist\n";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "\nPossible issue: Database server is not running or not accessible\n";
    }
    
    // Try to get the database configuration (without sensitive data)
    echo "\nDatabase configuration:\n";
    $config = [
        'db_host' => defined('DB_HOST') ? DB_HOST : 'Not defined',
        'db_name' => defined('DB_NAME') ? DB_NAME : 'Not defined',
        'db_user' => defined('DB_USER') ? DB_USER : 'Not defined',
        'db_port' => defined('DB_PORT') ? DB_PORT : 'Not defined',
    ];
    
    echo "- Host: " . $config['db_host'] . "\n";
    echo "- Database: " . $config['db_name'] . "\n";
    echo "- User: " . $config['db_user'] . "\n";
    echo "- Port: " . $config['db_port'] . "\n";
}
