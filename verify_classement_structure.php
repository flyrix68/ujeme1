<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require_once 'db-config.php';

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Vérification de la structure de la table 'classement'</h2>";
    echo "<div style='font-family: Arial, sans-serif; line-height: 1.6;'>";
    
    // Récupérer la structure de la table
    $stmt = $pdo->query("SHOW COLUMNS FROM classement");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Colonnes de la table 'classement' :</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    $hasDernierMatchTraite = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'dernier_match_traite') {
            $hasDernierMatchTraite = true;
        }
    }
    
    echo "</table>";
    
    if ($hasDernierMatchTraite) {
        echo "<div style='color: green; margin-top: 20px;'>✓ La colonne 'dernier_match_traite' existe dans la table 'classement'.</div>";
        
        // Vérifier l'index sur la colonne dernier_match_traite
        $stmt = $pdo->query("SHOW INDEX FROM classement WHERE Column_name = 'dernier_match_traite'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($indexes) > 0) {
            echo "<div style='color: green;'>✓ Un index existe sur la colonne 'dernier_match_traite'.</div>";
        } else {
            echo "<div style='color: orange;'>⚠️ Aucun index trouvé sur la colonne 'dernier_match_traite'. Il est recommandé d'en ajouter un pour les performances.</div>";
        }
    } else {
        echo "<div style='color: red; margin-top: 20px;'>✗ La colonne 'dernier_match_traite' n'existe pas dans la table 'classement'.</div>";
        
        // Proposer d'ajouter la colonne
        echo "<div style='margin-top: 20px;'>";
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='add_column' value='1'>";
        echo "<button type='submit' style='padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
        echo "Ajouter la colonne 'dernier_match_traite'";
        echo "</button>";
        echo "</form>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Traitement du formulaire pour ajouter la colonne
    if (isset($_POST['add_column']) && $_POST['add_column'] == 1) {
        try {
            $pdo->beginTransaction();
            
            // Ajouter la colonne
            $pdo->exec("
                ALTER TABLE `classement`
                ADD COLUMN `dernier_match_traite` TIMESTAMP NULL DEFAULT NULL
                AFTER `forme`
            ");

            // Mettre à jour les enregistrements existants avec la date actuelle
            $pdo->exec("
                UPDATE `classement` 
                SET `dernier_match_traite` = NOW() 
                WHERE `dernier_match_traite` IS NULL
            ");

            // Ajouter un index sur la colonne
            $pdo->exec("
                ALTER TABLE `classement`
                ADD INDEX `idx_dernier_match_traite` (`dernier_match_traite`)
            ");

            $pdo->commit();
            
            echo "<div style='color: green; margin-top: 20px;'>✓ La colonne 'dernier_match_traite' a été ajoutée avec succès.</div>";
            echo "<meta http-equiv='refresh' content='2;url=verify_classement_structure.php'>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='color: red; margin-top: 20px;'>Erreur lors de l'ajout de la colonne : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur lors de la vérification de la structure de la table</h2>";
    echo "<p><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Info erreur :</strong> " . htmlspecialchars(print_r($e->errorInfo, true)) . "</p>";
    }
    
    echo "<p><strong>Fichier :</strong> " . htmlspecialchars($e->getFile()) . " (ligne " . $e->getLine() . ")</p>";
}
?>
