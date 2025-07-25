<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier si la table existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'classement' n'existe pas dans la base de données");
    }

    // Afficher la structure de la table
    $stmt = $pdo->query("DESCRIBE classement");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table 'classement':\n";
    print_r($structure);

    // Vérifier le contenu
    $stmt = $pdo->query("SELECT COUNT(*) FROM classement");
    $count = $stmt->fetchColumn();
    
    echo "\nNombre d'entrées dans le classement: " . $count . "\n";

    if ($count == 0) {
        echo "\nAucune équipe dans le classement actuellement\n";
    }

} catch(Exception $e) {
    echo "ERREUR: " . $e->getMessage();
}
?>
