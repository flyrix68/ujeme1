<?php
// Configuration de base pour afficher les erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Informations de connexion - À ADAPTER selon votre configuration
$host = 'localhost';
$dbname = 'ujem';
$username = 'root';
$password = '';

// Message de débogage
echo "<h1>Vérification de la base de données</h1>";

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>Connexion à la base de données réussie !</p>";
    
    // Vérifier si la table matches existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'matches'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p>La table 'matches' existe.</p>";
        
        // Compter le nombre de matchs
        $count = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
        echo "<p>Nombre total de matchs: $count</p>";
        
        // Afficher les 5 premiers matchs
        $matches = $pdo->query("SELECT * FROM matches ORDER BY match_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>5 derniers matchs:</h2>";
        echo "<pre>";
        print_r($matches);
        echo "</pre>";
        
        // Vérifier les matchs en cours ou en attente
        $currentMatches = $pdo->query("SELECT * FROM matches WHERE status IN ('ongoing', 'pending') ORDER BY match_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>Matchs en cours ou en attente:</h2>";
        echo "<p>Nombre: " . count($currentMatches) . "</p>";
        echo "<pre>";
        print_r($currentMatches);
        echo "</pre>";
        
        // Vérifier les statuts uniques
        $statuses = $pdo->query("SELECT DISTINCT status FROM matches")->fetchAll(PDO::FETCH_COLUMN);
        echo "<h2>Statuts de match existants:</h2>";
        echo "<pre>";
        print_r($statuses);
        echo "</pre>";
        
        // Vérifier si la table goals existe et contient des données
        $goalsExist = $pdo->query("SHOW TABLES LIKE 'goals'")->rowCount() > 0;
        if ($goalsExist) {
            $goalCount = $pdo->query("SELECT COUNT(*) FROM goals")->fetchColumn();
            echo "<p>Nombre total de buts enregistrés: $goalCount</p>";
            
            if ($goalCount > 0) {
                $goals = $pdo->query("SELECT * FROM goals LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                echo "<h2>5 derniers buts:</h2>";
                echo "<pre>";
                print_r($goals);
                echo "</pre>";
            }
        } else {
            echo "<p style='color: orange;'>La table 'goals' n'existe pas.</p>";
        }
    } else {
        echo "<p style='color: red;'>La table 'matches' n'existe pas dans la base de données.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// Afficher les informations sur PHP pour le débogage
echo "<h2>Informations sur PHP</h2>";
phpinfo();
?>
