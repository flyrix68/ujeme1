<?php
// Test script to check database connection and matches table
require_once __DIR__ . '/includes/db-config.php';

header('Content-Type: text/plain');

echo "Testing database connection...\n";

try {
    // Get database connection
    $pdo = DatabaseConfig::getConnection();
    echo "✓ Connected to database successfully\n";
    
    // Check if matches table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'matches'")->fetchAll();
    if (empty($tables)) {
        echo "✗ Error: Matches table does not exist\n";
        exit(1);
    }
    
    echo "✓ Matches table exists\n";
    
    // Count matches
    $count = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
    echo "✓ Found $count matches in the database\n";
    
    // Get some match data
    $matches = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, status FROM matches LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample matches:\n";
    foreach ($matches as $match) {
        printf("%s %d - %d %s [%s]\n", 
            $match['team_home'], 
            $match['score_home'] ?? 0, 
            $match['score_away'] ?? 0, 
            $match['team_away'],
            $match['status']
        );
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    
    // Output database configuration (without password)
    $config = [
        'host' => 'yamanote.proxy.rlwy.net',
        'port' => 58372,
        'dbname' => 'railway',
        'user' => 'root',
        'ssl_cert' => file_exists(__DIR__ . '/includes/cacert.pem') ? 'Found' : 'Not found'
    ];
    
    echo "\nDatabase configuration:\n";
    print_r($config);
    
    // Check if we can connect without SSL
    try {
        $testDsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
        $testPdo = new PDO($testDsn, $config['user'], '*****', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]);
        echo "\n✓ Successfully connected without SSL\n";
    } catch (PDOException $e) {
        echo "\n✗ Connection without SSL also failed: " . $e->getMessage() . "\n";
    }
}

echo "\nPHP Version: " . phpversion() . "\n";
?>
