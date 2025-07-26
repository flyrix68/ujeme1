<?php
require 'includes/db-config.php';

/**
 * Met à jour le classement pour un match donné
 * 
 * @param PDO $pdo Instance PDO
 * @param array $match Tableau contenant les données du match
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateClassementForMatch($pdo, $match) {
    error_log("=== DÉBUT updateClassementForMatch ===");
    error_log("Données reçues: " . json_encode($match));
    
    try {
        // Vérification des données requises
        $requiredFields = ['saison', 'competition', 'poule_id', 'team_home', 'team_away', 'score_home', 'score_away'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($match[$field]) || (is_string($match[$field]) && trim($match[$field]) === '')) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            $errorMsg = "Champs manquants ou invalides: " . implode(', ', $missingFields);
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }

        // Validation des scores
        $match['score_home'] = (int)$match['score_home'];
        $match['score_away'] = (int)$match['score_away'];
        
        if ($match['score_home'] < 0 || $match['score_away'] < 0) {
            throw new Exception("Les scores ne peuvent pas être négatifs");
        }

        error_log("Mise à jour des statistiques de l'équipe à domicile: " . $match['team_home']);
        $homeResult = updateTeamStats(
            $pdo,
            $match['saison'],
            $match['competition'],
            $match['poule_id'],
            $match['team_home'],
            $match['score_home'],
            $match['score_away']
        );
        error_log("Résultat de la mise à jour de l'équipe à domicile: " . ($homeResult ? 'succès' : 'échec'));

        error_log("Mise à jour des statistiques de l'équipe à l'extérieur: " . $match['team_away']);
        $awayResult = updateTeamStats(
            $pdo,
            $match['saison'],
            $match['competition'],
            $match['poule_id'],
            $match['team_away'],
            $match['score_away'],
            $match['score_home']
        );
        error_log("Résultat de la mise à jour de l'équipe à l'extérieur: " . ($awayResult ? 'succès' : 'échec'));
        
        if (!$homeResult || !$awayResult) {
            throw new Exception("Échec de la mise à jour des statistiques d'une ou plusieurs équipes");
        }

        error_log("=== FIN updateClassementForMatch avec succès ===");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Erreur lors de la mise à jour du classement: " . $e->getMessage();
        error_log($errorMsg);
        error_log("=== FIN updateClassementForMatch avec erreur ===");
        return false;
    }
}

/**
 * Met à jour les statistiques d'une équipe dans le classement
 */
function updateTeamStats($pdo, $saison, $competition, $poule_id, $team, $goalsFor, $goalsAgainst) {
    error_log("=== DÉBUT updateTeamStats ===");
    error_log(sprintf("Mise à jour stats - Équipe: %s, Saison: %s, Compétition: %s, Poule: %s, Buts pour: %d, Buts contre: %d", 
        $team, $saison, $competition, $poule_id, $goalsFor, $goalsAgainst));
    
    try {
        // Calcul des points et du résultat
        $points = ($goalsFor > $goalsAgainst) ? 3 : (($goalsFor == $goalsAgainst) ? 1 : 0);
        $result = ($points == 3) ? 'V' : (($points == 1) ? 'N' : 'D');
        error_log("Résultat du match: $result ($points points)");
    
        // Calcul des statistiques à mettre à jour
        $matchesPlayed = 1;
        $wins = ($points == 3) ? 1 : 0;
        $draws = ($points == 1) ? 1 : 0;
        $losses = ($points == 0) ? 1 : 0;
        $goalDifference = $goalsFor - $goalsAgainst;
        
        error_log("Statistiques calculées - J: $matchesPlayed, G: $wins, N: $draws, P: $losses, Diff: $goalDifference");
    
        // Vérifier si la table classement existe
        $tableExists = $pdo->query("SHOW TABLES LIKE 'classement'")->rowCount() > 0;
        if (!$tableExists) {
            error_log("ERREUR: La table 'classement' n'existe pas dans la base de données");
            throw new Exception("La table 'classement' n'existe pas");
        }
        
        // Vérifier si l'équipe existe déjà dans le classement
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM classement WHERE saison = ? AND competition = ? AND poule_id = ? AND nom_equipe = ?");
        $checkStmt->execute([$saison, $competition, $poule_id, $team]);
        $teamExists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        error_log("L'équipe existe déjà dans le classement : " . ($teamExists ? 'oui' : 'non'));
        
        // Requête pour insérer ou mettre à jour le classement
        $sql = "
            INSERT INTO classement (
                saison, competition, poule_id, nom_equipe,
                matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus,
                buts_pour, buts_contre, difference_buts, points, forme
            ) VALUES (
                :saison, :competition, :poule_id, :team,
                :matches_played, :wins, :draws, :losses,
                :goals_for, :goals_against, :goal_difference, :points, :form
            )
            ON DUPLICATE KEY UPDATE
                matchs_joues = matchs_joues + VALUES(matchs_joues),
                matchs_gagnes = matchs_gagnes + VALUES(matchs_gagnes),
                matchs_nuls = matchs_nuls + VALUES(matchs_nuls),
                matchs_perdus = matchs_perdus + VALUES(matchs_perdus),
                buts_pour = buts_pour + VALUES(buts_pour),
                buts_contre = buts_contre + VALUES(buts_contre),
                difference_buts = difference_buts + VALUES(difference_buts),
                points = points + VALUES(points),
                forme = CONCAT(IF(LENGTH(forme) >= 4, SUBSTRING(forme, 2, 4), ''), :form)
        ";
        
        error_log("Exécution de la requête SQL: " . str_replace(["\n", "\s+/"], [" ", " "], $sql));
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':saison' => $saison,
            ':competition' => $competition,
            ':poule_id' => $poule_id,
            ':team' => $team,
            ':matches_played' => $matchesPlayed,
            ':wins' => $wins,
            ':draws' => $draws,
            ':losses' => $losses,
            ':goals_for' => $goalsFor,
            ':goals_against' => $goalsAgainst,
            ':goal_difference' => $goalDifference,
            ':points' => $points,
            ':form' => $result
        ];
        
        error_log("Paramètres de la requête: " . json_encode($params));
        
        $result = $stmt->execute($params);
        
        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            error_log("Erreur SQL: " . ($errorInfo[2] ?? 'Inconnue'));
            throw new Exception("Erreur lors de la mise à jour du classement: " . ($errorInfo[2] ?? 'Erreur inconnue'));
        }
        
        $rowCount = $stmt->rowCount();
        error_log("Requête exécutée avec succès. Lignes affectées: $rowCount");
        
        return true;
    } catch (Exception $e) {
        error_log("ERREUR dans updateTeamStats: " . $e->getMessage());
        error_log("=== FIN updateTeamStats avec erreur ===");
        return false;
    }
    
    error_log("=== FIN updateTeamStats avec succès ===");
    return true;
}

// Exemple d'utilisation :
/*
$pdo = DatabaseConfig::getConnection();

$match = [
    'saison' => '2024-2025',
    'competition' => 'championnat',
    'poule_id' => 1,
    'team_home' => 'Equipe A',
    'team_away' => 'Equipe B',
    'score_home' => 2,
    'score_away' => 1
];

$result = updateClassementForMatch($pdo, $match);
if ($result) {
    echo "Classement mis à jour avec succès";
} else {
    echo "Erreur lors de la mise à jour du classement";
}
*/
