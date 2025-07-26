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
echo "=== TEST DE LA CONFIGURATION DE LA BASE DE DONNÉES ===\n\n";

// 1. Vérifier si le fichier cacert.pem existe
$cacertPath = __DIR__ . '/includes/cacert.pem';
if (file_exists($cacertPath)) {
    show_message("1. Le fichier cacert.pem existe à l'emplacement: $cacertPath");
    
    // Vérifier les permissions du fichier
    $isReadable = is_readable($cacertPath);
    $isWritable = is_writable($cacertPath);
    show_message("   - Lisible: " . ($isReadable ? "Oui" : "Non"));
    show_message("   - Écrivable: " . ($isWritable ? "Oui" : "Non"), !$isReadable);
    
    if (!$isReadable) {
        show_message("   ❌ Le fichier cacert.pem n'est pas lisible. Vérifiez les permissions.", true);
        exit(1);
    }
    
} else {
    show_message("1. ❌ Le fichier cacert.pem est introuvable à l'emplacement: $cacertPath", true);
    exit(1);
}

// 2. Tester la connexion avec les mêmes paramètres que db-config.php
try {
    show_message("\n2. Tentative de connexion avec les paramètres de db-config.php...");
    
    $dbHost = 'yamanote.proxy.rlwy.net';
    $dbPort = 58372;
    $dbUser = 'root';
    $dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    $dbName = 'railway';
    
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_CA => $cacertPath,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    
    show_message("   - DSN: $dsn");
    show_message("   - Options: " . json_encode($options, JSON_PRETTY_PRINT));
    
    // Tenter la connexion
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    show_message("   ✅ Connexion établie avec succès !");
    
    // Tester une requête simple
    $result = $pdo->query("SELECT 'Test réussi !' as message")->fetch();
    show_message("   ✅ Requête test : " . $result['message']);
    
    // Vérifier si la table 'classement' existe
    show_message("\n3. Vérification de la table 'classement'...");
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if ($tableExists) {
        show_message("   ✅ La table 'classement' existe.");
        
        // Compter les enregistrements
        $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch()['count'];
        show_message("   - Nombre d'enregistrements : $count");
        
        // Afficher les premières lignes si la table n'est pas vide
        if ($count > 0) {
            $rows = $pdo->query("SELECT * FROM classement LIMIT 5")->fetchAll();
            show_message("   - Premiers enregistrements :");
            foreach ($rows as $row) {
                print_r($row);
                echo "\n";
            }
        } else {
            show_message("   ℹ️ La table 'classement' est vide.");
        }
        
    } else {
        show_message("   ❌ La table 'classement' n'existe pas.", true);
        
        // Essayer de créer la table si elle n'existe pas
        show_message("\n4. Tentative de création de la table 'classement'...");
        try {
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS classement (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    saison VARCHAR(20) NOT NULL,
                    competition VARCHAR(50) NOT NULL,
                    poule_id INT NOT NULL,
                    nom_equipe VARCHAR(100) NOT NULL,
                    matchs_joues INT DEFAULT 0,
                    matchs_gagnes INT DEFAULT 0,
                    matchs_nuls INT DEFAULT 0,
                    matchs_perdus INT DEFAULT 0,
                    buts_pour INT DEFAULT 0,
                    buts_contre INT DEFAULT 0,
                    difference_buts INT DEFAULT 0,
                    points INT DEFAULT 0,
                    forme VARCHAR(5) DEFAULT '',
                    logo VARCHAR(255) DEFAULT '',
                    UNIQUE KEY unique_team (saison, competition, poule_id, nom_equipe)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $pdo->exec($createTableSQL);
            show_message("   ✅ Table 'classement' créée avec succès !");
            
        } catch (Exception $e) {
            show_message("   ❌ Échec de la création de la table : " . $e->getMessage(), true);
        }
    }
    
} catch (Exception $e) {
    show_message("\n❌ Erreur lors de la connexion à la base de données : " . $e->getMessage(), true);
    
    // Afficher plus de détails sur l'erreur
    echo "\nDétails de l'erreur :\n";
    echo "- Message : " . $e->getMessage() . "\n";
    echo "- Fichier : " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    
    if (isset($pdo) && $pdo->errorInfo()) {
        $errorInfo = $pdo->errorInfo();
        echo "- Code d'erreur PDO : " . ($errorInfo[0] ?? 'Inconnu') . "\n";
        echo "- Code d'erreur SQL : " . ($errorInfo[1] ?? 'Inconnu') . "\n";
        echo "- Message d'erreur SQL : " . ($errorInfo[2] ?? 'Aucun message d\'erreur') . "\n";
    }
    
    // Essayer sans SSL pour voir si c'est un problème de certificat
    try {
        show_message("\nTentative de connexion SANS SSL...");
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        show_message("   ✅ Connexion SANS SSL réussie !");
        
    } catch (Exception $e) {
        show_message("   ❌ Échec de la connexion SANS SSL : " . $e->getMessage(), true);
    }
}

// Afficher les informations du serveur
echo "\n=== INFORMATIONS DU SERVEUR ===\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- OS: " . PHP_OS . "\n";
echo "- Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . "\n";
echo "- Chemin du script: " . __FILE__ . "\n";

echo "\n=== FIN DU TEST ===\n";
