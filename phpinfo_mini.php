<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Info Mini</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>PHP Info Mini</h1>
    
    <h2>Informations générales</h2>
    <table>
        <tr><th>Paramètre</th><th>Valeur</th></tr>
        <tr><td>Version PHP</td><td><?php echo phpversion(); ?></td></tr>
        <tr><td>Système</td><td><?php echo PHP_OS; ?></td></tr>
        <tr><td>Logiciel serveur</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td></tr>
        <tr><td>Interface SAPI</td><td><?php echo php_sapi_name(); ?></td></tr>
    </table>
    
    <h2>Extensions requises</h2>
    <table>
        <tr><th>Extension</th><th>Statut</th></tr>
        <?php
        $extensions = ['pdo', 'pdo_mysql', 'openssl', 'json', 'mbstring', 'fileinfo'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "<tr>";
            echo "<td>$ext</td>";
            echo "<td class='" . ($loaded ? 'success' : 'error') . "'>" . 
                 ($loaded ? '✓ Installée' : '✗ Manquante') . "</td>";
            echo "</tr>\n";
        }
        ?>
    </table>
    
    <h2>Test de connexion à la base de données</h2>
    <?php
    $dbHost = 'yamanote.proxy.rlwy.net';
    $dbPort = 58372;
    $dbName = 'railway';
    $dbUser = 'root';
    $dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    
    try {
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ];
        
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        echo "<p class='success'>✓ Connexion réussie à la base de données</p>";
        
        // Tester une requête simple
        $stmt = $pdo->query('SELECT 1 as test_value');
        $result = $stmt->fetch();
        echo "<p>Résultat du test : " . print_r($result, true) . "</p>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <h2>Variables d'environnement</h2>
    <table>
        <tr><th>Variable</th><th>Valeur</th></tr>
        <?php
        $env_vars = ['DB_', 'MYSQL_', 'RAILWAY_', 'HOSTNAME', 'HOME'];
        foreach ($_ENV as $key => $value) {
            foreach ($env_vars as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $display_value = (stripos($key, 'PASS') !== false) ? '********' : $value;
                    echo "<tr><td>$key</td><td>$display_value</td></tr>\n";
                    break;
                }
            }
        }
        ?>
    </table>
</body>
</html>
