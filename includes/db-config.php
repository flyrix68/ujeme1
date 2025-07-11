<?php
// Configuration for Railway production and local development
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Parse Railway-style DATABASE_URL (mysql://user:pass@host:port/dbname)
    $url = parse_url($dbUrl);
    $host = $url['host'];
    $port = $url['port'] ?? 3306;
    $dbname = ltrim($url['path'] ?? '', '/');
    $username = $url['user'];
    $password = $url['pass'];

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
} else {
    // Check for individual environment variables (Railway alternative format)
    $host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'db';
    $port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;
    $dbname = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'app_db';
    $username = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'app_user';
    $password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: 'userpassword';

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);

    // Test connection
    $pdo->query('SELECT 1');

} catch (PDOException $e) {
    error_log("[".date('Y-m-d H:i:s')."] DB Connection Error: ".$e->getMessage());
    error_log("[".date('Y-m-d H:i:s')."] DSN: ".$dsn);
    error_log("[".date('Y-m-d H:i:s')."] Username: ".$username);

    header('HTTP/1.1 503 Service Temporarily Unavailable');
    die('Erreur de connexion à la base de données. Veuillez vérifier la configuration.');
}
?>
