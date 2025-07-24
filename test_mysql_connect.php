<?php
try {
    $pdo = new PDO(
        'mysql:host=yamanote.proxy.rlwy.net;port=58372;dbname=railway',
        'root',
        'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_CA => __DIR__.'/includes/cacert.pem',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=60"
        ]
    );
    echo "Connexion MySQL réussie!\n";
    echo "Bases de données disponibles:\n";
    foreach ($pdo->query("SHOW DATABASES") as $row) {
        print_r($row);
    }
} catch (PDOException $e) {
    echo "Erreur de connexion: " . $e->getMessage();
}
