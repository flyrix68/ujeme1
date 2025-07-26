<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST D'AFFICHAGE DES ERREURS PHP ===\n";

// Tester si PHP fonctionne
$test_var = "Le script PHP fonctionne correctement!";
echo "1. Test PHP de base: $test_var\n";

// Tester l'inclusion du fichier de configuration
echo "2. Test d'inclusion du fichier de configuration... ";
if (file_exists('includes/db-config.php')) {
    require 'includes/db-config.php';
    echo "OK\n";
    
    // Tester la connexion à la base de données
    echo "3. Test de connexion à la base de données... ";
    try {
        $pdo = DatabaseConfig::getConnection();
        echo "CONNEXION RÉUSSIE!\n";
        
        // Tester une requête simple
        echo "4. Test d'une requête simple... ";
        $result = $pdo->query("SELECT 1 as test")->fetch(PDO::FETCH_ASSOC);
        echo "RÉUSSI! Résultat: " . $result['test'] . "\n";
        
        // Vérifier si la table classement existe
        echo "5. Vérification de la table 'classement'... ";
        $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
        echo $tableExists ? "EXISTE\n" : "N'EXISTE PAS\n";
        
        if ($tableExists) {
            // Afficher la structure de la table
            echo "6. Structure de la table 'classement':\n";
            $stmt = $pdo->query("DESCRIBE classement");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
            
            // Compter les enregistrements
            $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
            echo "\n7. Nombre d'enregistrements dans 'classement': $count\n";
        }
        
    } catch (Exception $e) {
        echo "ÉCHEC: " . $e->getMessage() . "\n";
        echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    }
    
} else {
    echo "ERREUR: Le fichier includes/db-config.php est introuvable.\n";
}

echo "\n=== FIN DU TEST ===\n";
