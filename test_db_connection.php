&lt;?php
require 'includes/db-config.php';
try {
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful\n";
    print_r($pdo->query("SELECT 1")->fetchAll());
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
