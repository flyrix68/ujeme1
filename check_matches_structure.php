<?php
require 'admin/admin_header.php';

try {
    // Afficher les colonnes de la table matches
    $stmt = $pdo->query("SHOW COLUMNS FROM matches");
    echo "<h2>Structure de la table matches :</h2>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Afficher les 5 premiers enregistrements pour référence
    echo "<h2>5 premiers enregistrements :</h2>";
    $stmt = $pdo->query("SELECT * FROM matches LIMIT 5");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h2>Erreur :</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    
    // Afficher les erreurs PDO si disponibles
    echo "<h2>Erreurs PDO :</h2>";
    echo "<pre>";
    print_r($pdo->errorInfo());
    echo "</pre>";
}
