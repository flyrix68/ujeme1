<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'includes/db-config.php';

try {
    // Connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Vérifier si la table 'classement' existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'classement' n'existe pas dans la base de données.\n");
    }
    
    // Afficher la structure de la table
    echo "=== STRUCTURE DE LA TABLE 'classement' ===\n";
    $stmt = $pdo->query("DESCRIBE classement");
    echo str_pad("Champ", 20) . str_pad("Type", 20) . "Null\n";
    echo str_repeat("-", 50) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 20) . 
             $row['Null'] . "\n";
    }
    
    // Compter les enregistrements
    $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nNombre d'enregistrements : $count\n";
    
    // Afficher les enregistrements si la table n'est pas vide
    if ($count > 0) {
        echo "\n=== ENREGISTREMENTS DANS 'classement' ===\n";
        $teams = $pdo->query("SELECT * FROM classement ORDER BY points DESC, difference_buts DESC, buts_pour DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo str_pad("Position", 10) . str_pad("Équipe", 30) . str_pad("MJ", 5) . 
             str_pad("G", 5) . str_pad("N", 5) . str_pad("P", 5) . 
             str_pad("BP", 5) . str_pad("BC", 5) . str_pad("Diff", 7) . 
             str_pad("Pts", 5) . "Forme\n";
        echo str_repeat("-", 85) . "\n";
        
        $position = 1;
        foreach ($teams as $team) {
            echo str_pad($position++, 10) . 
                 str_pad($team['nom_equipe'], 30) . 
                 str_pad($team['matchs_joues'], 5) . 
                 str_pad($team['matchs_gagnes'], 5) . 
                 str_pad($team['matchs_nuls'], 5) . 
                 str_pad($team['matchs_perdus'], 5) . 
                 str_pad($team['buts_pour'], 5) . 
                 str_pad($team['buts_contre'], 5) . 
                 str_pad($team['difference_buts'], 7) . 
                 str_pad($team['points'], 5) . 
                 $team['forme'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    
    if (isset($pdo) && $pdo->errorInfo()) {
        $error = $pdo->errorInfo();
        echo "Erreur PDO: " . ($error[2] ?? 'Inconnue') . "\n";
    }
}
?>
