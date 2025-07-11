<?php
// Configuration for both Railway production and local development
$dbUrl = getenv('DATABASE_URL');
if ($dbUrl) {
    // Parse Railway-style DATABASE_URL
    $url = parse_url($dbUrl);
    $host = $url['host'];
    $port = $url['port'] ?? 3306;
    $dbname = ltrim($url['path'] ?? '', '/');
    $username = $url['user'];
    $password = $url['pass'];
    
    // Use MySQL for Railway
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
} else {
    // Fallback local development config
    $host = 'db';
    $dbname = 'app_db';
    $username = 'app_user'; 
    $password = 'userpassword';
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO(
        getenv('DB_TYPE') === 'mysql' 
            ? "mysql:host=$host;dbname=$dbname;charset=utf8mb4"
            : "pgsql:host=$host;dbname=$dbname", 
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    error_log("[".date('Y-m-d H:i:s')."] DB Error: ".$e->getMessage());
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    die('Erreur de connexion à la base de données. Veuillez vérifier que le conteneur MySQL est démarré.');
}
?>
