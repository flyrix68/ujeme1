<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour afficher les résultats de test
function print_test($step, $message, $success = true) {
    $icon = $success ? "✅" : "❌";
    echo "$step. $icon $message\n";
}

// Fonction pour afficher une section
function print_section($title) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "$title\n";
    echo str_repeat("=", 80) . "\n\n";
}

print_section("TEST DE CONNEXION RAILWAY");

// 1. Vérifier si le fichier cacert.pem existe
$cacertPath = __DIR__ . '/includes/cacert.pem';
if (file_exists($cacertPath)) {
    print_test(1, "Le fichier cacert.pem existe.");
    
    // 2. Vérifier les permissions du fichier cacert.pem
    $isReadable = is_readable($cacertPath);
    $isWritable = is_writable($cacertPath);
    print_test(2, "Permissions du fichier cacert.pem - Lisible: " . ($isReadable ? "Oui" : "Non") . ", Écrivable: " . ($isWritable ? "Oui" : "Non"), $isReadable);
    
    // 3. Tester la connexion à la base de données
    try {
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
        
        print_test(3, "Tentative de connexion à la base de données...");
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        
        // Tester la connexion
        $pdo->query('SELECT 1');
        print_test(4, "Connexion à la base de données établie avec succès !");
        
        // Vérifier si la table 'classement' existe
        $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
        
        if ($tableExists) {
            print_test(5, "La table 'classement' existe.");
            
            // Compter les enregistrements
            $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
            print_test(6, "Nombre d'enregistrements dans 'classement': $count", true);
            
            // Afficher les premières lignes si la table n'est pas vide
            if ($count > 0) {
                $rows = $pdo->query("SELECT * FROM classement LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                echo "\n=== PREMIERS ENREGISTREMENTS ===\n";
                foreach ($rows as $row) {
                    print_r($row);
                    echo "\n";
                }
            }
        } else {
            print_test(5, "La table 'classement' n'existe pas.", false);
        }
        
    } catch (PDOException $e) {
        print_test(3, "Erreur de connexion à la base de données: " . $e->getMessage(), false);
        echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
        
        // Afficher plus de détails sur l'erreur
        if (isset($pdo)) {
            $errorInfo = $pdo->errorInfo();
            if (!empty($errorInfo[2])) {
                echo "Détails de l'erreur PDO: " . $errorInfo[2] . "\n";
            }
        }
    }
    
} else {
    print_test(1, "Le fichier cacert.pem est introuvable à l'emplacement: $cacertPath", false);
    echo "Assurez-vous que le fichier existe et que le chemin est correct.\n";
}

// Afficher les informations du serveur
print_section("INFORMATIONS DU SERVEUR");
echo "- Version de PHP: " . phpversion() . "\n";
echo "- Système d'exploitation: " . PHP_OS . "\n";
echo "- Logiciel serveur: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . "\n";
echo "- Chemin du script: " . __FILE__ . "\n";

print_section("FIN DU TEST");
?>
