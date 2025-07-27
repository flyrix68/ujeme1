<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour vérifier une extension
function check_extension($name) {
    return extension_loaded($name) ? "<span style='color:green;'>✓ $name</span>" : "<span style='color:red;'>✗ $name</span>";
}

// Démarrer le HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Vérification PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Vérification de l'environnement PHP</h1>
    
    <h2>Informations système</h2>
    <ul>
        <li>Version PHP: " . phpversion() . "</li>
        <li>Système d'exploitation: " . PHP_OS . "</li>
        <li>Logiciel serveur: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</li>
        <li>Interface SAPI: " . php_sapi_name() . "</li>
    </ul>
    
    <h2>Extensions PHP chargées</h2>
    <div style='column-count: 3;'>
        " . implode("<br>", array_map('check_extension', get_loaded_extensions())) . "
    </div>
    
    <h2>Test de base de données</h2>
    <pre>";

// Tester la connexion à la base de données
try {
    $dbHost = 'yamanote.proxy.rlwy.net';
    $dbPort = 58372;
    $dbName = 'railway';
    $dbUser = 'root';
    $dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    
    echo "Tentative de connexion à la base de données...\n";
    echo "DSN: mysql:host=$dbHost;port=$dbPort;dbname=$dbName\n";
    
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );
    
    echo "✓ Connexion réussie !\n\n";
    
    // Tester une requête simple
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch();
    echo "Résultat du test: " . print_r($result, true) . "\n\n";
    
    // Vérifier les tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables disponibles: " . implode(', ', $tables) . "\n";
    
    if (in_array('matches', $tables)) {
        $count = $pdo->query('SELECT COUNT(*) as count FROM matches')->fetch();
        echo "✓ Table 'matches' trouvée avec " . $count['count'] . " entrées.\n";
    } else {
        echo "✗ Table 'matches' non trouvée.\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Erreur de connexion: " . $e->getMessage() . "\n";
    
    // Afficher plus de détails sur l'erreur
    echo "\nDétails de l'erreur:\n";
    echo "- Code d'erreur: " . $e->getCode() . "\n";
    echo "- Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    
    // Vérifier les extensions requises
    echo "\nExtensions requises:\n";
    foreach (['pdo', 'pdo_mysql', 'openssl'] as $ext) {
        echo "- $ext: " . (extension_loaded($ext) ? '✓ Chargée' : '✗ Manquante') . "\n";
    }
}

// Fin du HTML
echo "    </pre>
</body>
</html>";
?>
