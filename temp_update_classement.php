<?php
require 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();

function updateClassementForMatch($pdo, $match) {
    $season = $match['saison'];
    
    // Update home team stats
    $points = ($match['score_home'] > $match['score_away']) ? 3 : 
             ($match['score_home'] == $match['score_away'] ? 1 : 0);
    $form = ($points == 3) ? 'V' : ($points == 1 ? 'N' : 'D');
    
    $stmt = $pdo->prepare("
        INSERT INTO classement (
            saison, competition, poule_id, nom_equipe, 
            matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus, 
            buts_pour, buts_contre, difference_buts, points, forme
        ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            matchs_joues = matchs_joues + 1,
            matchs_gagnes = matchs_gagnes + VALUES(matchs_gagnes),
            matchs_nuls = matchs_nuls + VALUES(matchs_nuls),
            matchs_perdus = matchs_perdus + VALUES(matchs_perdus),
            buts_pour = buts_pour + VALUES(buts_pour),
            buts_contre = buts_contre + VALUES(buts_contre),
            difference_buts = difference_buts + VALUES(difference_buts),
            points = points + VALUES(points),
            forme = CONCAT(SUBSTRING(forme, 2, 4), VALUES(forme))
    ");
    
    $stmt->execute([
        $season, $match['competition'], $match['poule_id'], $match['team_home'],
        ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
        $match['score_home'], $match['score_away'], ($match['score_home'] - $match['score_away']),
        $points, $form
    ]);
    
    // Update away team stats
    $points = ($match['score_away'] > $match['score_home']) ? 3 : 
             ($match['score_away'] == $match['score_home'] ? 1 : 0);
    $form = ($points == 3)
