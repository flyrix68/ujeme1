<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../includes/db-config.php';

// Récupérer l'ID du match depuis les paramètres GET ou POST
$matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT) ?: 
           filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);

if (!$matchId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de match invalide']);
    exit();
}

try {
    // Récupérer les informations du match
    $stmt = $pdo->prepare("
        SELECT 
            m.*, 
            UNIX_TIMESTAMP(m.timer_start) as timer_start_unix,
            m.timer_elapsed, 
            m.timer_status, 
            m.timer_duration,
            m.first_half_duration, 
            m.first_half_extra,
            m.second_half_duration,
            m.second_half_extra, 
            m.timer_paused,
            m.score_home,
            m.score_away,
            (SELECT COUNT(*) FROM goals WHERE match_id = m.id AND team_id = m.team_home_id) as goals_home,
            (SELECT COUNT(*) FROM goals WHERE match_id = m.id AND team_id = m.team_away_id) as goals_away,
            (SELECT COUNT(*) FROM cards WHERE match_id = m.id AND team_id = m.team_home_id AND card_type = 'yellow') as yellow_cards_home,
            (SELECT COUNT(*) FROM cards WHERE match_id = m.id AND team_id = m.team_away_id AND card_type = 'yellow') as yellow_cards_away,
            (SELECT COUNT(*) FROM cards WHERE match_id = m.id AND team_id = m.team_home_id AND card_type = 'red') as red_cards_home,
            (SELECT COUNT(*) FROM cards WHERE match_id = m.id AND team_id = m.team_away_id AND card_type = 'red') as red_cards_away,
            (SELECT COUNT(*) FROM cards WHERE match_id = m.id AND team_id = m.team_home_id AND card_type = 'blue') as blue_cards_home,
            (SELECT COUNT(*) FROM cards WHERE match_id = m.id AND team_id = m.team_away_id AND card_type = 'blue') as blue_cards_away
        FROM matches m
        WHERE m.id = ?
    ");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Match non trouvé']);
        exit();
    }

    // Calculer le temps écoulé
    $elapsed = (int)$match['timer_elapsed'];
    $isOngoing = $match['status'] === 'ongoing' && $match['timer_status'] !== 'ended';
    
    if ($match['timer_start_unix'] && !$match['timer_paused'] && $isOngoing) {
        $elapsed = $match['timer_elapsed'] + (time() - $match['timer_start_unix']);
    }

    // Calculer la durée totale en fonction de la mi-temps
    $totalDuration = (int)$match['timer_duration'];
    $firstHalfDuration = (int)$match['first_half_duration'] + (int)($match['first_half_extra'] ?? 0);
    
    if ($match['timer_status'] === 'second_half') {
        $totalDuration = $firstHalfDuration + (int)$match['second_half_duration'] + (int)($match['second_half_extra'] ?? 0);
    } elseif ($match['timer_status'] === 'first_half') {
        $totalDuration = $firstHalfDuration;
    }

    // Formater le temps pour l'affichage
    $displayTime = gmdate('i:s', $elapsed);
    $totalDurationFormatted = gmdate('i:s', $totalDuration);

    // Préparer la réponse
    $response = [
        'success' => true,
        'display_time' => "$displayTime / $totalDurationFormatted",
        'timer_value' => $elapsed,
        'total_duration' => $totalDurationFormatted,
        'is_ongoing' => $isOngoing,
        'timer_status' => $match['timer_status'],
        'score_home' => (int)$match['score_home'],
        'score_away' => (int)$match['score_away'],
        'goals_home' => (int)$match['goals_home'],
        'goals_away' => (int)$match['goals_away'],
        'yellow_cards_home' => (int)$match['yellow_cards_home'],
        'yellow_cards_away' => (int)$match['yellow_cards_away'],
        'red_cards_home' => (int)$match['red_cards_home'],
        'red_cards_away' => (int)$match['red_cards_away'],
        'blue_cards_home' => (int)$match['blue_cards_home'],
        'blue_cards_away' => (int)$match['blue_cards_away'],
        'server_time' => time()
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Erreur dans update_timer.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données',
        'details' => $e->getMessage()
    ]);
}
