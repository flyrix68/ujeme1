<?php
// Configuration pour serveur XAMPP local
$host = getenv('DB_HOST') ?: 'db'; // Nom du service dans docker-compose
$dbname = getenv('DB_NAME') ?: 'app_db'; // Doit correspondre à MYSQL_DATABASE dans docker-compose
$username = getenv('DB_USER') ?: 'app_user'; // Doit correspondre à MYSQL_USER dans docker-compose
$password = getenv('DB_PASSWORD') ?: 'userpassword'; // Doit correspondre à MYSQL_PASSWORD dans docker-compose

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
