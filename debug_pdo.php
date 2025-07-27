<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour afficher les messages
echo "<h1>Test de connexion PDO simplifié</h1>";

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
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
];

// DSN de connexion
$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";

echo "<h2>Test de connexion à la base de données</h2>";

try {
    // Essai de connexion
    echo "<p>Connexion à la base de données en cours...</p>";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    // Si on arrive ici, la connexion est établie
    echo "<p style='color: green;'>✓ Connexion réussie !</p>";
    
    // Tester une requête simple
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch();
    
    echo "<p>Résultat de la requête de test :</p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    // Vérifier si la table matches existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'matches'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color: green;'>✓ La table 'matches' existe.</p>";
        
        // Compter le nombre de matchs
        $count = $pdo->query("SELECT COUNT(*) as count FROM matches")->fetch();
        echo "<p>Nombre de matchs dans la base de données : " . $count['count'] . "</p>";
        
        // Afficher les 3 derniers matchs
        $matches = $pdo->query("SELECT id, team_home, team_away, match_date, status FROM matches ORDER BY id DESC LIMIT 3")->fetchAll();
        
        if (!empty($matches)) {
            echo "<h3>Derniers matchs :</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date</th><th>Statut</th></tr>";
            
            foreach ($matches as $match) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($match['id']) . "</td>";
                echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
                echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
                echo "<td>" . htmlspecialchars($match['match_date']) . "</td>";
                echo "<td>" . htmlspecialchars($match['status']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>Aucun match trouvé dans la table 'matches'.</p>";
        }
    } else {
        echo "<p style='color: red;'>La table 'matches' n'existe pas dans la base de données.</p>";
    }
    
} catch (PDOException $e) {
    // En cas d'erreur, afficher un message d'erreur détaillé
    echo "<div style='color: red;'>";
    echo "<h3>Erreur de connexion à la base de données :</h3>";
    echo "<p><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code d'erreur :</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>Fichier :</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>";
    echo "</div>";
    
    // Afficher des informations supplémentaires pour le débogage
    echo "<h3>Informations supplémentaires :</h3>";
    echo "<ul>";
    echo "<li><strong>PHP Version :</strong> " . phpversion() . "</li>";
    echo "<li><strong>Système d'exploitation :</strong> " . PHP_OS . "</li>";
    echo "<li><strong>Extensions PDO disponibles :</strong> " . (extension_loaded('pdo') ? 'Oui' : 'Non') . "</li>";
    echo "<li><strong>Extension PDO MySQL disponible :</strong> " . (extension_loaded('pdo_mysql') ? 'Oui' : 'Non') . "</li>";
    echo "<li><strong>Hôte de la base de données :</strong> $dbHost:$dbPort</li>";
    echo "<li><strong>Nom de la base de données :</strong> $dbName</li>";
    echo "<li><strong>Nom d'utilisateur :</strong> $dbUser</li>";
    echo "</ul>";
    
    // Essayer d'afficher plus d'informations sur l'erreur
    echo "<h3>Détails de l'erreur :</h3>";
    echo "<pre>";
    echo "DSN utilisé : " . $dsn . "\n";
    echo "Options : " . print_r($options, true) . "\n";
    
    // Tester la connexion avec des paramètres simplifiés
    echo "\n=== Test de connexion simplifié ===\n";
    
    try {
        $testPdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "✓ Connexion réussie sans spécifier la base de données.\n";
        
        // Essayer de lister les bases de données
        $databases = $testPdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        echo "Bases de données disponibles : " . implode(', ', $databases) . "\n";
        
        // Vérifier si la base de données existe
        if (in_array($dbName, $databases)) {
            echo "✓ La base de données '$dbName' existe.\n";
            
            // Essayer de se connecter à la base de données spécifique
            try {
                $testPdoDb = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                echo "✓ Connexion réussie à la base de données '$dbName'.\n";
                
                // Vérifier les tables
                $tables = $testPdoDb->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                echo "Tables disponibles : " . implode(', ', $tables) . "\n";
                
            } catch (PDOException $eDb) {
                echo "✗ Erreur lors de la connexion à la base de données '$dbName': " . $eDb->getMessage() . "\n";
            }
            
        } else {
            echo "✗ La base de données '$dbName' n'existe pas.\n";
        }
        
    } catch (PDOException $eSimple) {
        echo "✗ Échec de la connexion de base : " . $eSimple->getMessage() . "\n";
    }
    
    echo "</pre>";
}

echo "<h2>Informations sur le serveur :</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";

// Afficher les extensions PHP chargées
echo "\nExtensions PHP chargées :\n";
$extensions = get_loaded_extensions();
sort($extensions);
$extensionsPerLine = 5;
$count = 0;

foreach ($extensions as $ext) {
    echo str_pad($ext, 20);
    $count++;
    if ($count % $extensionsPerLine === 0) echo "\n";
}

echo "</pre>";
?>
