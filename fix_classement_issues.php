<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier et ajouter la colonne logo si nécessaire
    $stmt = $pdo->query("SHOW COLUMNS FROM classement LIKE 'logo'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE classement ADD COLUMN logo VARCHAR(255) DEFAULT NULL AFTER nom_equipe");
        echo "Colonne 'logo' ajoutée à la table classement.\n";
    }
    
    // 2. Mettre à jour les logos à partir de la table teams
    $teams = $pdo->query("SELECT team_name, logo_path FROM teams WHERE logo_path IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($teams as $team) {
        $stmt = $pdo->prepare("UPDATE classement SET logo = ? WHERE nom_equipe = ?");
        $stmt->execute([$team['logo_path'], $team['team_name']]);
        
        if ($stmt->rowCount() > 0) {
            echo "Logo mis à jour pour {$team['team_name']}: {$team['logo_path']}\n";
        }
    }
    
    // 3. Vérifier et corriger les problèmes de classement
    $duplicates = $pdo->query("
        SELECT saison, competition, poule_id, nom_equipe, COUNT(*) as count 
        FROM classement 
        GROUP BY saison, competition, poule_id, nom_equipe 
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($duplicates as $dup) {
        echo "\nCorrection des doublons pour {$dup['nom_equipe']}...\n";
        
        $pdo->beginTransaction();
        
        try {
            // Récupérer les IDs des doublons
            $ids = $pdo->prepare("
                SELECT id FROM classement 
                WHERE saison = ? AND competition = ? AND poule_id = ? AND nom_equipe = ?
                ORDER BY id
            ");
            $ids->execute([$dup['saison'], $dup['competition'], $dup['poule_id'], $dup['nom_equipe']]);
            $allIds = $ids->fetchAll(PDO::FETCH_COLUMN);
            
            // Garder le premier ID, supprimer les autres
            $keepId = array_shift($allIds);
            
            if (!empty($allIds)) {
                $placeholders = rtrim(str_repeat('?,', count($allIds)), ',');
                $deleteStmt = $pdo->prepare("DELETE FROM classement WHERE id IN ($placeholders)");
                $deleteStmt->execute($allIds);
                echo "- {$deleteStmt->rowCount()} doublons supprimés.\n";
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Erreur lors de la suppression des doublons: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nVérification et correction terminées avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
