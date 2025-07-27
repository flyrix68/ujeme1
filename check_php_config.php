<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour vérifier une extension PHP
function check_extension($name, $required = true) {
    $loaded = extension_loaded($name);
    $status = $loaded ? "<span style='color: green;'>✓ Installée</span>" : "<span style='color: red;'>✗ Non installée</span>";
    echo "<tr>";
    echo "<td>$name" . ($required ? " <span style='color: red;'>*</span>" : "") . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
    return $loaded || !$required;
}

// Fonction pour vérifier un paramètre de configuration PHP
function check_php_setting($setting, $expected, $type = '===') {
    $current = ini_get($setting);
    $result = false;
    
    switch ($type) {
        case '>=':
            $result = version_compare($current, $expected, '>=');
            break;
        case '===':
        default:
            $result = ($current === $expected);
            break;
    }
    
    $status = $result ? "<span style='color: green;'>✓ OK ($current)</span>" : "<span style='color: red;'>✗ Actuel: $current (Attendu: $expected)</span>";
    echo "<tr>">
    echo "<td>$setting</td>";
    echo "<td>$status</td>";
    echo "</tr>";
    
    return $result;
}

// Démarrer le contenu HTML
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Vérification de la configuration PHP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Vérification de la configuration PHP</h1>
    
    <h2>Informations générales</h2>
    <table>
        <tr><th>Paramètre</th><th>Valeur</th></tr>";

echo "<tr><td>Version PHP</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>Système d'exploitation</td><td>" . PHP_OS . "</td></tr>";
echo "<tr><td>Logiciel serveur</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Interface SAPI</td><td>" . php_sapi_name() . "</td></tr>";

echo "</table>

<h2>Extensions PHP requises</h2>
<table>
    <tr><th>Extension</th><th>Statut</th></tr>";

// Vérifier les extensions requises
$required_extensions = [
    'pdo' => true,
    'pdo_mysql' => true,
    'openssl' => true,
    'json' => true,
    'mbstring' => true,
    'fileinfo' => true,
    'gd' => false,
    'zip' => false
];

$all_ok = true;
foreach ($required_extensions as $ext => $required) {
    if (!check_extension($ext, $required) && $required) {
        $all_ok = false;
    }
}

echo "</table>

<h2>Paramètres de configuration PHP</h2>
<table>
    <tr><th>Paramètre</th><th>Statut</th></tr>";

// Vérifier les paramètres PHP importants
$settings = [
    ['display_errors', '1', '==='],
    ['error_reporting', (string)E_ALL, '==='],
    ['max_execution_time', '30', '>='],
    ['memory_limit', '128M', '>='],
    ['post_max_size', '8M', '>='],
    ['upload_max_filesize', '2M', '>='],
    ['max_input_time', '60', '>='],
    ['max_input_vars', '1000', '>='],
    ['allow_url_fopen', '1', '==='],
    ['allow_url_include', '0', '==='],
    ['file_uploads', '1', '==='],
    ['session.cookie_httponly', '1', '==='],
    ['session.cookie_secure', '1', '==='],
    ['session.use_strict_mode', '1', '==='],
    ['session.use_only_cookies', '1', '==='],
    ['session.cookie_samesite', 'Strict', '==='],
    ['session.gc_maxlifetime', '1440', '>='],
    ['session.cookie_lifetime', '0', '>=']
];

foreach ($settings as $setting) {
    if (!check_php_setting($setting[0], $setting[1], $setting[2])) {
        $all_ok = false;
    }
}

echo "</table>

<h2>Permissions des répertoires</h2>
<table>
    <tr><th>Répertoire</th><th>Statut</th></tr>";

// Vérifier les permissions des répertoires importants
$directories = [
    '/',
    '/uploads',
    '/includes',
    '/admin',
    '/assets',
    '/assets/img',
    '/assets/img/teams',
    '/assets/img/players',
    '/assets/img/tournaments'
];

foreach ($directories as $dir) {
    $full_path = __DIR__ . $dir;
    $exists = file_exists($full_path);
    $writable = is_writable($full_path);
    $readable = is_readable($full_path);
    
    $status = [];
    if (!$exists) {
        $status[] = "<span style='color: red;'>N'existe pas</span>";
    } else {
        $status[] = $writable ? "<span style='color: green;'>Inscriptible</span>" : "<span style='color: red;'>Non inscriptible</span>";
        $status[] = $readable ? "<span style='color: green;'>Lisible</span>" : "<span style='color: red;'>Non lisible</span>";
        $status[] = is_dir($full_path) ? "<span style='color: green;'>Est un dossier</span>" : "<span style='color: red;'>N'est pas un dossier</span>";
    }
    
    echo "<tr><td>$full_path</td><td>" . implode(" ", $status) . "</td></tr>";
    
    if ((!$exists || !$writable || !$readable) && in_array($dir, ['/uploads', '/assets/img/teams', '/assets/img/players', '/assets/img/tournaments'])) {
        $all_ok = false;
    }
}

echo "</table>

<h2>Résumé</h2>";

if ($all_ok) {
    echo "<div style='color: green; font-weight: bold;'>✓ Tous les tests de configuration sont réussis !</div>";
} else {
    echo "<div style='color: red; font-weight: bold;'>✗ Certains tests de configuration ont échoué. Veuillez corriger les problèmes signalés ci-dessus.</div>";
    echo "<p>Les éléments marqués d'un astérisque (*) sont obligatoires pour le bon fonctionnement de l'application.</p>";
}

// Tester la connexion à la base de données
echo "<h2>Test de connexion à la base de données</h2>";

try {
    $dbHost = 'yamanote.proxy.rlwy.net';
    $dbPort = 58372;
    $dbName = 'railway';
    $dbUser = 'root';
    $dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    // Essai sans SSL
    echo "<h3>Essai sans SSL</h3>";
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        $stmt = $pdo->query('SELECT 1 as test_value');
        $result = $stmt->fetch();
        echo "<div style='color: green;'>✓ Connexion réussie sans SSL !</div>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } catch (PDOException $e) {
        echo "<div style='color: red;'>✗ Échec de la connexion sans SSL : " . htmlspecialchars($e->getMessage()) . "</div>";
        $all_ok = false;
        
        // Essai avec SSL
        echo "<h3>Essai avec SSL</h3>";
        try {
            $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/includes/cacert.pem';
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            $stmt = $pdo->query('SELECT 1 as test_value');
            $result = $stmt->fetch();
            echo "<div style='color: green;'>✓ Connexion réussie avec SSL !</div>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        } catch (PDOException $e2) {
            echo "<div style='color: red;'>✗ Échec de la connexion avec SSL : " . htmlspecialchars($e2->getMessage()) . "</div>";
            $all_ok = false;
        }
    }
    
    // Si la connexion a réussi, tester l'accès à la table matches
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM matches');
            $count = $stmt->fetch();
            echo "<div style='color: green;'>✓ Table 'matches' trouvée avec " . $count['count'] . " enregistrements.</div>";
            
            // Afficher quelques matchs
            $stmt = $pdo->query('SELECT id, team_home, team_away, match_date, status FROM matches ORDER BY id DESC LIMIT 3');
            $matches = $stmt->fetchAll();
            
            echo "<h3>Derniers matchs :</h3>";
            echo "<pre>" . print_r($matches, true) . "</pre>";
            
        } catch (PDOException $e) {
            echo "<div style='color: orange;'>⚠ Erreur lors de la requête sur la table matches : " . htmlspecialchars($e->getMessage()) . "</div>";
            $all_ok = false;
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Erreur lors du test de connexion à la base de données : " . htmlspecialchars($e->getMessage()) . "</div>";
    $all_ok = false;
}

// Afficher les extensions chargées
echo "<h2>Extensions PHP chargées</h2>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<div style='column-count: 3;'>";
foreach ($extensions as $ext) {
    echo "<div>" . htmlspecialchars($ext) . "</div>";
}
echo "</div>";

// Afficher les variables d'environnement
echo "<h2>Variables d'environnement</h2>";
echo "<pre>";
foreach ($_ENV as $key => $value) {
    if (stripos($key, 'DB_') === 0 || stripos($key, 'MYSQL_') === 0 || stripos($key, 'RAILWAY_') === 0) {
        echo htmlspecialchars("$key=$value\n");
    }
}
echo "</pre>";

echo "</body>
</html>";
?>
