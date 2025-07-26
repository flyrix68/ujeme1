<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour afficher un message avec un style
function show_message($message, $is_error = false) {
    $prefix = $is_error ? "[ERREUR] " : "[INFO] ";
    $color = $is_error ? "\033[0;31m" : "\033[0;32m";
    $reset = "\033[0m";
    echo $color . $prefix . $message . $reset . "\n";
}

// Afficher les informations de base
echo "=== TEST DE CONNEXION DIRECTE À LA BASE DE DONNÉES ===\n\n";

// 1. Définir les informations de connexion
$dbHost = 'yamanote.proxy.rlwy.net';
$dbPort = 58372;
$dbUser = 'root';
$dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$dbName = 'railway';

// 2. Tester la connexion sans SSL (pour voir si c'est un problème de certificat)
try {
    show_message("1. Tentative de connexion SANS SSL...");
    
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    // Tester la connexion
    $pdo->query('SELECT 1');
    show_message("   ✅ Connexion SANS SSL réussie !");
    
} catch (Exception $e) {
    show_message("   ❌ Échec de la connexion SANS SSL: " . $e->getMessage(), true);
}

// 3. Tester la connexion avec SSL (comme dans la configuration actuelle)
try {
    show_message("\n2. Tentative de connexion AVEC SSL...");
    
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/includes/cacert.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    // Tester la connexion
    $pdo->query('SELECT 1');
    show_message("   ✅ Connexion AVEC SSL réussie !");
    
} catch (Exception $e) {
    show_message("   ❌ Échec de la connexion AVEC SSL: " . $e->getMessage(), true);
}

// 4. Tester la connexion avec SSL et vérifier la table 'classement'
try {
    show_message("\n3. Vérification de la table 'classement'...");
    
    // Utiliser la connexion sans SSL si disponible, sinon essayer avec SSL
    try {
        $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", 
                       $dbUser, $dbPass, [
                           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                           PDO::ATTR_TIMEOUT => 10
                       ]);
    } catch (Exception $e) {
        // Si la connexion sans SSL échoue, essayer avec SSL
        $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", 
                       $dbUser, $dbPass, [
                           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                           PDO::ATTR_TIMEOUT => 10,
                           PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/includes/cacert.pem',
                           PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
                       ]);
    }
    
    // Vérifier si la table 'classement' existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if ($tableExists) {
        show_message("   ✅ La table 'classement' existe.");
        
        // Compter les enregistrements
        $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
        show_message("   Nombre d'enregistrements dans 'classement': $count");
        
        // Afficher les premières lignes si la table n'est pas vide
        if ($count > 0) {
            $rows = $pdo->query("SELECT * FROM classement LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            show_message("   Premiers enregistrements :");
            foreach ($rows as $row) {
                print_r($row);
                echo "\n";
            }
        }
    } else {
        show_message("   ❌ La table 'classement' n'existe pas.", true);
    }
    
} catch (Exception $e) {
    show_message("   ❌ Erreur lors de la vérification de la table 'classement': " . $e->getMessage(), true);
}

// Afficher les informations du serveur
echo "\n=== INFORMATIONS DU SERVEUR ===\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- OS: " . PHP_OS . "\n";
echo "- Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . "\n";
echo "- Chemin du script: " . __FILE__ . "\n";

echo "\n=== FIN DU TEST ===\n";
