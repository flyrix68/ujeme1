<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la temporisation de sortie
ob_start();

echo "<h1>Test de Connexion à la Base de Données</h1>";

try {
    // 1. Tester l'inclusion du fichier de configuration
    echo "<h2>1. Test d'inclusion du fichier de configuration</h2>";
    if (file_exists('includes/db-config.php')) {
        require 'includes/db-config.php';
        echo "<p style='color:green;'>✅ Le fichier de configuration a été inclus avec succès.</p>";
        
        // 2. Tester la connexion à la base de données
        echo "<h2>2. Test de connexion à la base de données</h2>";
        try {
            $pdo = DatabaseConfig::getConnection();
            echo "<p style='color:green;'>✅ Connexion à la base de données réussie !</p>";
            
            // 3. Tester une requête simple
            echo "<h2>3. Test d'une requête simple</h2>";
            $result = $pdo->query("SELECT 1 as test")->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color:green;'>✅ Requête exécutée avec succès. Résultat: " . $result['test'] . "</p>";
            
            // 4. Vérifier si la table 'classement' existe
            echo "<h2>4. Vérification de la table 'classement'</h2>";
            $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
            
            if ($tableExists) {
                echo "<p style='color:green;'>✅ La table 'classement' existe.</p>";
                
                // Afficher la structure de la table
                echo "<h3>Structure de la table 'classement'</h3>";
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Valeur par défaut</th><th>Extra</th></tr>";
                
                $stmt = $pdo->query("DESCRIBE classement");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Compter les enregistrements
                $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p>Nombre d'enregistrements dans 'classement': <strong>$count</strong></p>";
                
                // Afficher les premiers enregistrements
                if ($count > 0) {
                    echo "<h3>Premiers enregistrements</h3>";
                    $rows = $pdo->query("SELECT * FROM classement LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                    echo "<pre>";
                    print_r($rows);
                    echo "</pre>";
                } else {
                    echo "<p style='color:orange;'>⚠️ La table 'classement' est vide.</p>";
                }
                
            } else {
                echo "<p style='color:orange;'>⚠️ La table 'classement' n'existe pas.</p>";
                
                // Tenter de créer la table
                echo "<h3>Tentative de création de la table 'classement'</h3>";
                $sql = "CREATE TABLE IF NOT EXISTS classement (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $result = $pdo->exec($sql);
                
                if ($result !== false) {
                    echo "<p style='color:green;'>✅ Table 'classement' créée avec succès !</p>";
                } else {
                    $error = $pdo->errorInfo();
                    echo "<p style='color:red;'>❌ Échec de la création de la table. Erreur: " . htmlspecialchars($error[2] ?? 'Inconnue') . "</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Erreur lors de la connexion à la base de données: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Fichier: " . htmlspecialchars($e->getFile()) . " (ligne " . $e->getLine() . ")</p>";
            
            if (isset($pdo) && $pdo->errorInfo()) {
                $error = $pdo->errorInfo();
                echo "<p>Détails de l'erreur PDO: " . htmlspecialchars($error[2] ?? 'Inconnue') . "</p>";
            }
        }
        
    } else {
        echo "<p style='color:red;'>❌ Le fichier 'includes/db-config.php' est introuvable.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Une erreur inattendue s'est produite: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Fichier: " . htmlspecialchars($e->getFile()) . " (ligne " . $e->getLine() . ")</p>";
}

// Afficher les informations du serveur
echo "<h2>Informations du serveur</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . "\n";
echo "</pre>";

// Afficher toutes les erreurs qui auraient pu être générées
$errors = ob_get_clean();
echo $errors;
?>
