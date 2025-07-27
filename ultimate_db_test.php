<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour tester la connexion avec différents paramètres
function testConnection($host, $port, $dbname, $user, $pass, $options = []) {
    echo "<h3>Test de connexion à $host:$port/$dbname</h3>";
    echo "<pre>";
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Tester une requête simple
        $stmt = $pdo->query('SELECT 1 as test_value');
        $result = $stmt->fetch();
        
        echo "✓ Connexion réussie !\n";
        echo "Résultat: " . print_r($result, true) . "\n\n";
        
        // Vérifier les tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables disponibles: " . implode(', ', $tables) . "\n\n";
        
        if (in_array('matches', $tables)) {
            $count = $pdo->query('SELECT COUNT(*) as count FROM matches')->fetch();
            echo "✓ Table 'matches' trouvée avec " . $count['count'] . " entrées.\n";
            
            // Afficher les 2 derniers matchs
            $matches = $pdo->query('SELECT * FROM matches ORDER BY id DESC LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
            echo "Derniers matchs:\n";
            foreach ($matches as $match) {
                echo "- " . $match['team_home'] . " vs " . $match['team_away'] . " (" . $match['match_date'] . ") - " . $match['status'] . "\n";
            }
        } else {
            echo "✗ Table 'matches' non trouvée.\n";
        }
        
        return true;
        
    } catch (PDOException $e) {
        echo "✗ Échec de la connexion: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "</pre>";
}

// Démarrer le HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Ultimate DB Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Test Ultime de Connexion à la Base de Données</h1>
";

// Paramètres de connexion
$dbHost = 'yamanote.proxy.rlwy.net';
$dbPort = 58372;
$dbName = 'railway';
$dbUser = 'root';
$dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';

// Options de connexion
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 5
];

// Test 1: Connexion de base
echo "<h2>Test 1: Connexion de base</h2>";
testConnection($dbHost, $dbPort, $dbName, $dbUser, $dbPass, $options);

// Test 2: Connexion avec SSL
$optionsWithSSL = $options + [
    PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/includes/cacert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
];

echo "<h2>Test 2: Connexion avec SSL</h2>";
testConnection($dbHost, $dbPort, $dbName, $dbUser, $dbPass, $optionsWithSSL);

// Test 3: Connexion sans base de données spécifiée
echo "<h2>Test 3: Connexion sans base de données</h2>";
testConnection($dbHost, $dbPort, '', $dbUser, $dbPass, $options);

// Afficher les informations système
echo "<h2>Informations Système</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Extensions chargées: " . implode(', ', get_loaded_extensions()) . "\n";
echo "</pre>";

// Fin du HTML
echo "</body>
</html>";
?>
