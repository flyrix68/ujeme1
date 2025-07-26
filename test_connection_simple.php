<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour afficher un message avec un formatage
function show_message($message, $is_error = false) {
    $prefix = $is_error ? "[ERREUR] " : "[INFO] ";
    $color = $is_error ? "\033[0;31m" : "\033[0;32m";
    $reset = "\033[0m";
    echo $color . $prefix . $message . $reset . "\n";
}

// Afficher les informations de base
echo "=== TEST DE CONNEXION SIMPLIFIÉ ===\n";

// 1. Vérifier si le fichier de configuration existe
$configFile = __DIR__ . '/includes/db-config.php';
if (file_exists($configFile)) {
    show_message("1. Fichier de configuration trouvé: $configFile");
    
    // 2. Essayer d'inclure le fichier de configuration
    try {
        require $configFile;
        show_message("2. Fichier de configuration inclus avec succès");
        
        // 3. Tester la connexion à la base de données
        try {
            show_message("3. Tentative de connexion à la base de données...");
            $pdo = DatabaseConfig::getConnection();
            show_message("   ✅ Connexion réussie !");
            
            // 4. Tester une requête simple
            $result = $pdo->query("SELECT 'Test réussi !' as message")->fetch(PDO::FETCH_ASSOC);
            show_message("4. Requête de test exécutée avec succès: " . $result['message']);
            
        } catch (Exception $e) {
            show_message("3. Échec de la connexion à la base de données: " . $e->getMessage(), true);
        }
        
    } catch (Exception $e) {
        show_message("2. Erreur lors de l'inclusion du fichier de configuration: " . $e->getMessage(), true);
    }
    
} else {
    show_message("1. Fichier de configuration introuvable: $configFile", true);
}

// Afficher les informations du serveur
echo "\n=== INFORMATIONS DU SERVEUR ===\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- OS: " . PHP_OS . "\n";
echo "- Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . "\n";

echo "\n=== FIN DU TEST ===\n";
