&lt;?php
// Simple local MySQL connection test
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3307;dbname=app_db',
        'root',
        'rootpassword',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "Connected successfully to local MySQL!\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?&gt;
