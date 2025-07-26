<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

echo "<h1>Vérification de la table matches</h1>";

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier la structure de la table matches
    echo "<h2>1. Structure de la table matches</h2>";
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
    
    // 2. Vérifier les contraintes de clé étrangère
    echo "<h2>2. Contraintes de clé étrangère</h2>";
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'matches' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($foreignKeys) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Table</th><th>Colonne</th><th>Contrainte</th><th>Table référencée</th><th>Colonne référencée</th></tr>";
        
        foreach ($foreignKeys as $fk) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fk['TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($fk['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($fk['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucune contrainte de clé étrangère trouvée pour la table 'matches'.</p>";
    }
    
    // 3. Vérifier les matchs en attente ou en cours
    echo "<h2>3. Matchs en attente ou en cours</h2>";
    $stmt = $pdo->query("
        SELECT id, team_home, team_away, score_home, score_away, status, saison, competition, poule_id
        FROM matches 
        WHERE status = 'pending' OR status = 'ongoing' 
        ORDER BY status, id DESC
    ");
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($matches) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>ID</th><th>Équipe domicile</th><th>Score</th><th>Équipe extérieure</th><th>Statut</th><th>Saison</th><th>Compétition</th><th>Poule</th><th>Actions</th></tr>";
        
        foreach ($matches as $match) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($match['id']) . "</td>";
            echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
            echo "<td>" . htmlspecialchars($match['score_home'] ?? '0') . " - " . htmlspecialchars($match['score_away'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
            echo "<td>" . htmlspecialchars($match['status']) . "</td>";
            echo "<td>" . htmlspecialchars($match['saison'] ?? 'Non défini') . "</td>";
            echo "<td>" . htmlspecialchars($match['competition'] ?? 'Non défini') . "</td>";
            echo "<td>" . htmlspecialchars($match['poule_id'] ?? 'Non défini') . "</td>";
            echo "<td><a href='debug_finalize.php?match_id=" . $match['id'] . "' target='_blank'>Déboguer la finalisation</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun match en attente ou en cours trouvé.</p>";
    }
    
    // 4. Vérifier les valeurs possibles pour le statut
    echo "<h2>4. Valeurs possibles pour le statut</h2>";
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM matches 
        GROUP BY status 
        ORDER BY count DESC
    ");
    
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($statuses) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Statut</th><th>Nombre de matchs</th></tr>";
        
        foreach ($statuses as $status) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($status['status']) . "</td>";
            echo "<td>" . htmlspecialchars($status['count']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun statut trouvé dans la table matches.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données</h2>";
    echo "<p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur SQL:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
    
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . " (ligne " . $e->getLine() . ")</p>";
}

// Afficher un lien pour retourner au tableau de bord
echo "<p><a href='admin/dashboard.php'>&larr; Retour au tableau de bord</a></p>";
?>
