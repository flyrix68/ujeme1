&lt;<?php
require 'includes/db-config.php';

function initStandings() {
    try {
        echo "[DEBUG] Connexion à la base de données...\n";
        $pdo = DatabaseConfig::getConnection();
        
        // Ensure tables exist
        echo "[DEBUG] Vérification/Création de la table match_processed...\n";
        $result = $pdo->exec("CREATE TABLE IF NOT EXISTS match_processed (
            id INT AUTO_INCREMENT PRIMARY KEY,
            match_id INT NOT NULL UNIQUE,
            processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "[DEBUG] Résultat de la création de la table: " . ($result === false ? 'échec' : 'succès') . "\n";
        
        // Initialize standings with current match data
        echo "[DEBUG] Début de la transaction...\n";
        $pdo->beginTransaction();
        
        // Clear existing data
        echo "[DEBUG] Vidage des tables existantes...\n";
        $pdo->exec("TRUNCATE TABLE IF EXISTS classement");
        echo "[DEBUG] Table classement vidée.\n";
        $pdo->exec("TRUNCATE TABLE IF EXISTS match_processed");
        echo "[DEBUG] Table match_processed vidée.\n";
        
        // Process Match 25: ABOUTOU 01 FC 2-1 NGALWA FC
        echo "[DEBUG] Insertion des données du match 25...\n";
        $sql = "INSERT INTO classement 
            (saison, competition, poule_id, nom_equipe, 
             matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus,
             buts_pour, buts_contre, difference_buts, points, forme)
            VALUES 
            ('2024-2025', 'tournoi', 1, 'ABOUTOU 01 FC', 1, 1, 0, 0, 2, 1, 1, 3, 'V'),
            ('2024-2025', 'tournoi', 1, 'NGALWA FC', 1, 0, 0, 1, 1, 2, -1, 0, 'D')";
        
        $result = $pdo->exec($sql);
        echo "[DEBUG] Insertion des équipes du match 25: " . ($result === false ? 'échec' : 'succès') . " (lignes affectées: $result)\n";
        
        // Vérifier si les données ont été insérées
        $count = $pdo->query("SELECT COUNT(*) as count FROM classement")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "[DEBUG] Nombre d'équipes dans classement après insertion: $count\n";
        
        // Mark match 25 as processed
        echo "[DEBUG] Marquage du match 25 comme traité...\n";
        $result = $pdo->exec("INSERT INTO match_processed (match_id) VALUES (25)");
        echo "[DEBUG] Marquage du match 25: " . ($result === false ? 'échec' : 'succès') . " (lignes affectées: $result)\n";
        
        // Process Match 26: ABOUTOU 01 FC 0-0 NGALWA FC  
        echo "[DEBUG] Mise à jour pour le match nul (match 26)...\n";
        $result = $pdo->exec("UPDATE classement SET
            matchs_joues = matchs_joues + 1,
            matchs_nuls = matchs_nuls + 1,
            points = points + 1,
            forme = CONCAT(SUBSTRING(forme, 2, 4), 'N')
            WHERE nom_equipe IN ('ABOUTOU 01 FC', 'NGALWA FC')");
            
        echo "[DEBUG] Mise à jour des statistiques pour le match 26: " . ($result === false ? 'échec' : 'succès') . " (lignes affectées: $result)\n";
        
        // Mark match 26 as processed
        echo "[DEBUG] Marquage du match 26 comme traité...\n";
        $result = $pdo->exec("INSERT INTO match_processed (match_id) VALUES (26)");
        echo "[DEBUG] Marquage du match 26: " . ($result === false ? 'échec' : 'succès') . " (lignes affectées: $result)\n";
        
        // Vérifier les données avant commit
        $teams = $pdo->query("SELECT nom_equipe, points, matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus FROM classement")->fetchAll(PDO::FETCH_ASSOC);
        echo "[DEBUG] Données avant commit:\n";
        foreach ($teams as $team) {
            echo "- {$team['nom_equipe']}: {$team['points']} pts (MJ:{$team['matchs_joues']} G:{$team['matchs_gagnes']} N:{$team['matchs_nuls']} P:{$team['matchs_perdus']})\n";
        }
        
        echo "[DEBUG] Validation de la transaction...\n";
        $pdo->commit();
        echo "[DEBUG] Transaction validée avec succès.\n";
        
        return true;
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            echo "[ERREUR] Annulation de la transaction...\n";
            $pdo->rollBack();
        }
        echo "[ERREUR] " . $e->getMessage() . "\n";
        echo "Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
        error_log("Error initializing standings: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        return false;
    }
}

// Execute the fix
echo "=== DÉBUT DE L'INITIALISATION DU CLASSEMENT ===\n";
$startTime = microtime(true);

if (initStandings()) {
    echo "\n=== RÉSULTAT ===\n";
    echo "✅ Le classement a été initialisé avec succès.\n";
    echo "- ABOUTOU 01 FC devrait avoir 4 points (1V 1N)\n"; 
    echo "- NGALWA FC devrait avoir 1 point (1N 1D)\n";
} else {
    echo "\n=== ERREUR ===\n";
    echo "❌ L'initialisation du classement a échoué.\n";
}

$endTime = microtime(true);
printf("\nTemps d'exécution: %.2f secondes\n", $endTime - $startTime);
