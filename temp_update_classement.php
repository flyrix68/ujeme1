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
    try {
        // Vérification des données requises
        $requiredFields = ['saison', 'competition', 'poule_id', 'team_home', 'team_away', 'score_home', 'score_away'];
        foreach ($requiredFields as $field) {
            if (!isset($match[$field]) || (is_string($match[$field]) && trim($match[$field]) === '')) {
                throw new Exception("Champ manquant ou invalide: $field");
            }
        }

        // Validation des scores
        $match['score_home'] = (int)$match['score_home'];
        $match['score_away'] = (int)$match['score_away'];
        
        if ($match['score_home'] < 0 || $match['score_away'] < 0) {
            throw new Exception("Les scores ne peuvent pas être négatifs");
        }

        // Mise à jour de l'équipe à domicile
        updateTeamStats(
            $pdo,
            $match['saison'],
            $match['competition'],
            $match['poule_id'],
            $match['team_home'],
            $match['score_home'],
            $match['score_away']
        );

        // Mise à jour de l'équipe à l'extérieur
        updateTeamStats(
            $pdo,
            $match['saison'],
            $match['competition'],
            $match['poule_id'],
            $match['team_away'],
            $match['score_away'],
            $match['score_home']
        );

        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du classement: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour les statistiques d'une équipe dans le classement
 */
function updateTeamStats($pdo, $saison, $competition, $poule_id, $team, $goalsFor, $goalsAgainst) {
    // Calcul des points et du résultat
    $points = ($goalsFor > $goalsAgainst) ? 3 : (($goalsFor == $goalsAgainst) ? 1 : 0);
    $result = ($points == 3) ? 'V' : (($points == 1) ? 'N' : 'D');
    
    // Calcul des statistiques à mettre à jour
    $matchesPlayed = 1;
    $wins = ($points == 3) ? 1 : 0;
    $draws = ($points == 1) ? 1 : 0;
    $losses = ($points == 0) ? 1 : 0;
    $goalDifference = $goalsFor - $goalsAgainst;
    
    // Requête pour insérer ou mettre à jour le classement
    $stmt = $pdo->prepare("
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
    ");
    
    $stmt->execute([
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
    ]);
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
