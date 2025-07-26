<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Afficher les informations PHP
phpinfo();

// Tester l'écriture dans un fichier
$testFile = __DIR__ . '/test_write.txt';
$writeResult = file_put_contents($testFile, 'Test d\'écriture le ' . date('Y-m-d H:i:s'));

echo "\n\n=== TEST D'ÉCRITURE ===\n";
if ($writeResult !== false) {
    echo "✅ Écriture réussie dans $testFile\n";
    echo "Contenu du fichier : " . file_get_contents($testFile) . "\n";
} else {
    echo "❌ Échec de l'écriture dans $testFile\n";
    echo "Vérifiez les permissions du répertoire : " . __DIR__ . "\n";
}

// Tester la connexion à la base de données
echo "\n=== TEST DE CONNEXION À LA BASE DE DONNÉES ===\n";
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
        PDO::ATTR_TIMEOUT => 5,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    echo "Tentative de connexion à $dbHost:$dbPort...\n";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "✅ Connexion réussie !\n";
    
    // Tester une requête simple
    $result = $pdo->query("SELECT 'Test réussi !' as message")->fetch();
    echo "✅ Requête test : " . $result['message'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
}

// Tester l'accès à la table classement
if (isset($pdo)) {
    echo "\n=== TEST D'ACCÈS À LA TABLE 'classement' ===\n";
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
        
        if ($tableExists) {
            echo "✅ La table 'classement' existe.\n";
            
            // Compter les enregistrements
            $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch()['count'];
            echo "   Nombre d'enregistrements : $count\n";
            
            // Afficher les premières lignes si la table n'est pas vide
            if ($count > 0) {
                $rows = $pdo->query("SELECT * FROM classement LIMIT 5")->fetchAll();
                echo "   Premiers enregistrements :\n";
                foreach ($rows as $row) {
                    print_r($row);
                    echo "\n";
                }
            }
        } else {
            echo "❌ La table 'classement' n'existe pas.\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur lors de l'accès à la table 'classement' : " . $e->getMessage() . "\n";
    }
}
?>
