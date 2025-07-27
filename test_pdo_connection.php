<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour afficher les messages
echo "<h1>Test de connexion PDO directe</h1>";

// Paramètres de connexion
$dbHost = 'yamanote.proxy.rlwy.net';
$dbPort = 58372;
$dbName = 'railway';
$dbUser = 'root';
$dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';

// Options de connexion
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

echo "<h2>Essai de connexion à la base de données...</h2>";

try {
    // Essai de connexion sans SSL
    echo "<h3>Essai sans SSL</h3>";
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    // Tester la connexion
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch();
    
    echo "<div style='color: green;'>✓ Connexion réussie sans SSL!</div>";
    echo "Résultat du test: " . print_r($result, true);
    
    // Tester une requête sur la table matches
    try {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM matches');
        $count = $stmt->fetch();
        echo "<div style='color: green;'>✓ Table 'matches' trouvée avec " . $count['count'] . " enregistrements.</div>";
        
        // Afficher quelques matchs
        $stmt = $pdo->query('SELECT id, team_home, team_away, match_date, status FROM matches ORDER BY id DESC LIMIT 3');
        $matches = $stmt->fetchAll();
        
        echo "<h3>Derniers matchs :</h3>";
        echo "<pre>" . print_r($matches, true) . "</pre>";
        
    } catch (Exception $e) {
        echo "<div style='color: orange;'>Erreur lors de la requête sur la table matches: " . $e->getMessage() . "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>Échec de la connexion sans SSL: " . $e->getMessage() . "</div>";
    
    // Essayer avec SSL
    try {
        echo "<h3>Essai avec SSL</h3>";
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/includes/cacert.pem';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        
        // Tester la connexion
        $stmt = $pdo->query('SELECT 1 as test_value');
        $result = $stmt->fetch();
        
        echo "<div style='color: green;'>✓ Connexion réussie avec SSL!</div>";
        echo "Résultat du test: " . print_r($result, true);
        
        // Tester une requête sur la table matches
        try {
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM matches');
            $count = $stmt->fetch();
            echo "<div style='color: green;'>✓ Table 'matches' trouvée avec " . $count['count'] . " enregistrements.</div>";
            
            // Afficher quelques matchs
            $stmt = $pdo->query('SELECT id, team_home, team_away, match_date, status FROM matches ORDER BY id DESC LIMIT 3');
            $matches = $stmt->fetchAll();
            
            echo "<h3>Derniers matchs :</h3>";
            echo "<pre>" . print_r($matches, true) . "</pre>";
            
        } catch (Exception $e) {
            echo "<div style='color: orange;'>Erreur lors de la requête sur la table matches: " . $e->getMessage() . "</div>";
        }
        
    } catch (PDOException $e2) {
        echo "<div style='color: red;'>Échec de la connexion avec SSL: " . $e2->getMessage() . "</div>";
    }
}

echo "<h2>Informations sur le serveur :</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";

echo "\nExtensions PHP chargées :\n";
$extensions = get_loaded_extensions();
sort($extensions);
echo implode(", ", $extensions);

echo "</pre>";
?>
