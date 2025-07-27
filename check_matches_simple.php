<?php
require 'admin/admin_header.php';

// Vérifier la connexion à la base de données
echo "<h2>Connexion à la base de données</h2>";
try {
    $pdo->query("SELECT 1");
    echo "<p style='color: green;'>Connexion à la base de données réussie !</p>";
} catch (PDOException $e) {
    die("<p style='color: red;'>Erreur de connexion à la base de données: " . $e->getMessage() . "</p>");
}

// Vérifier si la table matches existe et compter les enregistrements
try {
    $count = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
    echo "<h2>Table 'matches'</h2>";
    echo "<p>Nombre total de matchs: " . $count . "</p>";
    
    // Afficher les 5 premiers matchs
    $matches = $pdo->query("SELECT * FROM matches ORDER BY match_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>5 derniers matchs:</h3>";
    echo "<pre>";
    print_r($matches);
    echo "</pre>";
    
    // Vérifier les matchs en cours ou en attente
    echo "<h3>Matchs en cours ou en attente:</h3>";
    $currentMatches = $pdo->query("SELECT * FROM matches WHERE status IN ('ongoing', 'pending') ORDER BY match_date DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Nombre de matchs en cours ou en attente: " . count($currentMatches) . "</p>";
    echo "<pre>";
    print_r($currentMatches);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de la requête: " . $e->getMessage() . "</p>";
}

// Vérifier les tables existantes
try {
    echo "<h2>Tables de la base de données:</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>";
    print_r($tables);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de la récupération des tables: " . $e->getMessage() . "</p>";
}
?>
