<?php
require_once 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    echo "Connexion réussie à la base de données Railway\n";
    
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Test de requête réussi: " . $result['test'] . "\n";
    
} catch (Exception $e) {
    echo "ERREUR DE CONNEXION:\n";
    echo $e->getMessage() . "\n";
    error_log("Database connection error: " . $e->getMessage());
}
?>
