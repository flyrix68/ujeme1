<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour afficher une section avec un titre
function print_section($title) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "$title\n";
    echo str_repeat("=", 80) . "\n\n";
}

print_section("DÉBUT DU TEST DE CONNEXION SIMPLIFIÉ");

// 1. Vérifier si le fichier de configuration existe
echo "1. Vérification du fichier de configuration...\n";
if (file_exists('includes/db-config.php')) {
    echo "   ✅ Le fichier 'includes/db-config.php' existe.\n\n";
    
    // 2. Inclure le fichier de configuration
    try {
        require 'includes/db-config.php';
        echo "2. Le fichier de configuration a été inclus avec succès.\n\n";
        
        // 3. Tester la connexion à la base de données
        echo "3. Tentative de connexion à la base de données...\n";
        try {
            $pdo = DatabaseConfig::getConnection();
            echo "   ✅ Connexion à la base de données réussie !\n\n";
            
            // 4. Tester une requête simple
            echo "4. Exécution d'une requête de test...\n";
            $result = $pdo->query("SELECT 'Test réussi !' as message")->fetch(PDO::FETCH_ASSOC);
            echo "   ✅ Requête exécutée avec succès. Résultat: " . $result['message'] . "\n\n";
            
            // 5. Vérifier si la table 'classement' existe
            echo "5. Vérification de l'existence de la table 'classement'...\n";
            $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
            
            if ($tableExists) {
                echo "   ✅ La table 'classement' existe.\n\n";
                
                // 6. Compter les enregistrements dans la table 'classement'
                $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
                echo "6. Nombre d'enregistrements dans 'classement': $count\n";
                
                if ($count > 0) {
                    echo "   🏆 Données trouvées dans la table 'classement'.\n";
                } else {
                    echo "   ℹ️ La table 'classement' est vide.\n";
                }
                
            } else {
                echo "   ❌ La table 'classement' n'existe pas.\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Erreur lors de la connexion à la base de données: " . $e->getMessage() . "\n";
            echo "      Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
            
            if (isset($pdo) && $pdo->errorInfo()) {
                $error = $pdo->errorInfo();
                echo "      Détails de l'erreur PDO: " . ($error[2] ?? 'Inconnue') . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Erreur lors de l'inclusion du fichier de configuration: " . $e->getMessage() . "\n";
        echo "      Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    }
    
} else {
    echo "   ❌ Le fichier 'includes/db-config.php' est introuvable.\n";
}

print_section("FIN DU TEST DE CONNEXION SIMPLIFIÉ");

// Afficher les informations du serveur
echo "\nInformations du serveur :\n";
echo "- Version de PHP : " . phpversion() . "\n";
echo "- Système d'exploitation : " . PHP_OS . "\n";
echo "- Logiciel serveur : " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . "\n";

echo "\nPour plus d'informations, consultez les fichiers de logs d'erreurs PHP.\n";
?>
