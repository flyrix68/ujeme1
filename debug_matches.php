<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Obtenir la connexion à la base de données
try {
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier la structure de la table matches
    echo "<h2>Structure de la table matches</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM matches");
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
    
    // 2. Vérifier les valeurs possibles pour la colonne status
    echo "<h2>Valeurs uniques pour la colonne 'status'</h2>";
    $stmt = $pdo->query("SELECT DISTINCT status FROM matches");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($statuses) > 0) {
        echo "<p>Valeurs trouvées pour 'status': " . implode(", ", array_map('htmlspecialchars', $statuses)) . "</p>";
    } else {
        echo "<p>Aucune valeur trouvée pour la colonne 'status'.</p>";
    }
    
    // 3. Vérifier les 5 derniers matchs
    echo "<h2>5 derniers matchs</h2>";
    $stmt = $pdo->query("SELECT id, team_home, team_away, match_date, status FROM matches ORDER BY id DESC LIMIT 5");
    $recentMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recentMatches) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date</th><th>Statut</th></tr>";
        
        foreach ($recentMatches as $match) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($match['id']) . "</td>";
            echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
            echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
            echo "<td>" . htmlspecialchars($match['match_date']) . "</td>";
            echo "<td>" . htmlspecialchars($match['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun match trouvé dans la base de données.</p>";
    }
    
    // 4. Vérifier les matchs avec status 'ongoing' ou 'pending'
    echo "<h2>Matchs avec status 'ongoing' ou 'pending'</h2>";
    $stmt = $pdo->query("SELECT id, team_home, team_away, match_date, status FROM matches WHERE status IN ('ongoing', 'pending') ORDER BY id DESC");
    $currentMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($currentMatches) > 0) {
        echo "<p>Nombre de matchs trouvés: " . count($currentMatches) . "</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date</th><th>Statut</th></tr>";
        
        foreach ($currentMatches as $match) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($match['id']) . "</td>";
            echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
            echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
            echo "<td>" . htmlspecialchars($match['match_date']) . "</td>";
            echo "<td>" . htmlspecialchars($match['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun match avec status 'ongoing' ou 'pending' trouvé.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données</h2>";
    echo "<p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Code d'erreur:</strong> " . htmlspecialchars($e->errorInfo[0]) . "</p>";
        echo "<p><strong>Message SQL:</strong> " . htmlspecialchars($e->errorInfo[2]) . "</p>";
    }
    
    // Afficher la trace complète pour le débogage
    echo "<h3>Stack trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
