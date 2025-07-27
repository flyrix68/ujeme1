<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Débogage de la base de données</h1>";

// Fonction pour afficher les messages formatés
function print_message($message, $type = 'info') {
    $colors = [
        'success' => 'green',
        'error' => 'red',
        'info' => 'blue',
        'warning' => 'orange'
    ];
    $color = $colors[$type] ?? 'black';
    echo "<div style='color: $color; margin: 10px 0; padding: 10px; border: 1px solid $color; border-radius: 4px;'>";
    echo htmlspecialchars($message);
    echo "</div>\n";
}

try {
    // Inclure la configuration de la base de données
    require 'includes/db-config.php';
    print_message("Fichier de configuration de la base de données chargé", 'success');
    
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    print_message("Connexion à la base de données établie avec succès", 'success');
    
    // Vérifier si la table matches existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'matches'");
    if ($stmt->rowCount() > 0) {
        print_message("La table 'matches' existe dans la base de données", 'success');
        
        // Afficher la structure de la table
        echo "<h2>Structure de la table 'matches' :</h2>";
        $stmt = $pdo->query("DESCRIBE matches");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Compter le nombre de matchs par statut
        echo "<h2>Nombre de matchs par statut :</h2>";
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM matches GROUP BY status");
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($status_counts) > 0) {
            echo "<ul>";
            foreach ($status_counts as $row) {
                echo "<li>" . htmlspecialchars($row['status']) . ": " . $row['count'] . " match(s)</li>";
            }
            echo "</ul>";
            
            // Afficher les 5 derniers matchs
            echo "<h2>5 derniers matchs :</h2>";
            $stmt = $pdo->query("SELECT id, team_home, team_away, match_date, match_time, status FROM matches ORDER BY id DESC LIMIT 5");
            $recent_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($recent_matches) > 0) {
                echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date/Heure</th><th>Statut</th></tr>";
                
                foreach ($recent_matches as $match) {
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
                print_message("Aucun match trouvé dans la table 'matches'", 'warning');
            }
        } else {
            print_message("Aucun match trouvé dans la table 'matches'", 'warning');
        }
    } else {
        print_message("La table 'matches' n'existe pas dans la base de données", 'error');
    }
    
} catch (Exception $e) {
    print_message("Erreur: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Informations sur le serveur :</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "</pre>";
?>
