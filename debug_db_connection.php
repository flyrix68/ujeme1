<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de connexion à la base de données</h1>";

// 1. Vérifier si le fichier de configuration existe
$configFile = __DIR__ . '/includes/db-config.php';
if (!file_exists($configFile)) {
    die("<div style='color: red;'>Erreur: Le fichier de configuration de la base de données est introuvable.</div>");
}

echo "<div style='color: green;'>✓ Fichier de configuration trouvé: $configFile</div>";

// 2. Inclure le fichier de configuration
try {
    require_once $configFile;
    echo "<div style='color: green;'>✓ Fichier de configuration chargé avec succès.</div>";
} catch (Exception $e) {
    die("<div style='color: red;'>Erreur lors du chargement du fichier de configuration: " . $e->getMessage() . "</div>");
}

// 3. Vérifier si la classe DatabaseConfig existe
if (!class_exists('DatabaseConfig')) {
    die("<div style='color: red;'>Erreur: La classe DatabaseConfig n'existe pas dans le fichier de configuration.</div>");
}

echo "<div style='color: green;'>✓ Classe DatabaseConfig trouvée.</div>";

// 4. Tester la connexion à la base de données
try {
    echo "<div style='margin: 10px 0;'>Tentative de connexion à la base de données...</div>";
    
    // Utiliser la méthode de connexion de la classe DatabaseConfig
    $pdo = DatabaseConfig::getConnection();
    
    // Si on arrive ici, la connexion a réussi
    echo "<div style='color: green;'>✓ Connexion à la base de données réussie !</div>";
    
    // Tester une requête simple
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='margin: 10px 0;'>Résultat de la requête de test: " . print_r($result, true) . "</div>";
    
    // Vérifier si la table matches existe
    $tables = $pdo->query("SHOW TABLES LIKE 'matches'")->fetchAll();
    
    if (count($tables) > 0) {
        echo "<div style='color: green;'>✓ La table 'matches' existe.</div>";
        
        // Afficher les 5 derniers matchs
        $matches = $pdo->query("SELECT id, team_home, team_away, match_date, match_time, status FROM matches ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($matches) > 0) {
            echo "<h3>5 derniers matchs :</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date/Heure</th><th>Statut</th></tr>";
            
            foreach ($matches as $match) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($match['id']) . "</td>";
                echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
                echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
                echo "<td>" . htmlspecialchars($match['match_date'] . ' ' . $match['match_time']) . "</td>";
                echo "<td>" . htmlspecialchars($match['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div style='color: orange;'>Aucun match trouvé dans la table 'matches'.</div>";
        }
    } else {
        echo "<div style='color: red;'>La table 'matches' n'existe pas dans la base de données.</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>ERREUR: " . $e->getMessage() . "</div>";
    
    // Afficher plus d'informations sur l'erreur
    echo "<h3>Détails de l'erreur :</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Informations sur le serveur :</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "</pre>
</body>
</html>";
?>
