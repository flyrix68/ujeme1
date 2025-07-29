<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require_once 'db-config.php';

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Migration : Ajout de la colonne 'dernier_match_traite' à la table 'classement'</h2>";
    echo "<div style='font-family: Arial, sans-serif; line-height: 1.6;'>";
    
    // Vérifier si la colonne 'dernier_match_traite' existe déjà
    $checkColumnSQL = "
        SELECT COUNT(*) as column_exists 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'classement' 
        AND COLUMN_NAME = 'dernier_match_traite'
    ";
    
    $result = $pdo->query($checkColumnSQL)->fetch(PDO::FETCH_ASSOC);
    
    if ($result['column_exists'] == 0) {
        // La colonne n'existe pas, on l'ajoute
        $alterTableSQL = "
            ALTER TABLE `classement`
            ADD COLUMN `dernier_match_traite` TIMESTAMP NULL DEFAULT NULL
            AFTER `forme`,
            ADD INDEX `idx_dernier_match_traite` (`dernier_match_traite`);
        ";
        
        $pdo->exec($alterTableSQL);
        echo "<div style='color: green;'>✓ La colonne 'dernier_match_traite' a été ajoutée avec succès à la table 'classement'.</div>";
        
        // Mettre à jour les enregistrements existants avec la date actuelle
        $updateSQL = "
            UPDATE `classement` 
            SET `dernier_match_traite` = NOW() 
            WHERE `dernier_match_traite` IS NULL
        ";
        $updated = $pdo->exec($updateSQL);
        echo "<div style='color: green;'>✓ Mise à jour de $updated enregistrements avec la date actuelle pour 'dernier_match_traite'.</div>";
    } else {
        echo "<div style='color: orange;'>La colonne 'dernier_match_traite' existe déjà dans la table 'classement'. Aucune modification nécessaire.</div>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur lors de la migration</h2>";
    echo "<p><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Info erreur :</strong> " . htmlspecialchars(print_r($e->errorInfo, true)) . "</p>";
    }
    
    echo "<p><strong>Fichier :</strong> " . htmlspecialchars($e->getFile()) . " (ligne " . $e->getLine() . ")</p>";
    echo "<p><strong>Trace :</strong> <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
}
?>
