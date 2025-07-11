<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/db-config.php';

function notifyWebSocket($matchId) {
    $context = new ZMQContext();
    $socket = $context->getSocket(ZMQ::SOCKET_PUSH);
    $socket->connect("tcp://localhost:5555");
    $socket->send(json_encode(['match_id' => $matchId]));
}

try {
    // Récupère le paramètre competition
    $competition = $_GET['competition'] ?? '';

    // Requête préparée avec champs spécifiques
    $query = "
        SELECT m.*, 
               UNIX_TIMESTAMP(m.timer_start) AS timer_start_unix,
               COALESCE(m.score_home, 0) AS score_home,
               COALESCE(m.score_away, 0) AS score_away,
               m.first_half_duration,
               m.timer_status,
               m.timer_duration,
               m.first_half_extra,
               m.second_half_extra
        FROM matches m
        WHERE m.status = 'ongoing' AND m.competition = :competition
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['competition' => $competition]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = ['success' => true, 'matches' => [], 'websocket_url' => 'ws://localhost:8080'];

    // Ajoute les buts, cartons et calcul du temps pour chaque match
    foreach ($matches as &$match) {
        // Calculer le temps actuel
        $elapsed = (int)($match['timer_elapsed'] ?? 0);
        if ($match['timer_start_unix']) {
            $elapsed += time() - $match['timer_start_unix'];
        }
        if ($elapsed < 0) {
            $elapsed = 0;
        }

        // Limiter le temps selon la mi-temps et le temps additionnel
        $halfDuration = floor(($match['timer_duration'] ?? 5400) / 2);
        $additionalTime = $match['timer_status'] === 'first_half' ? ($match['first_half_extra'] ?? 0) : ($match['second_half_extra'] ?? 0);
        $limit = $halfDuration + $additionalTime;
        if ($elapsed > $limit) {
            $elapsed = $limit;
        }

        $minutes = floor($elapsed / 60);
        $seconds = $elapsed % 60;
        $currentTime = sprintf('%02d:%02d', $minutes, $seconds);

        // Fetch goals
        $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
        $goalsStmt->execute([$match['id']]);
        $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch cards
        $cardsStmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
        $cardsStmt->execute([$match['id']]);
        $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Ajouter les données au match
        $result['matches'][] = [
            'id' => $match['id'],
            'team_home' => $match['team_home'],
            'team_away' => $match['team_away'],
            'score_home' => $match['score_home'],
            'score_away' => $match['score_away'],
            'timer_status' => $match['timer_status'],
            'current_time' => $currentTime,
            'timer_duration' => $match['timer_duration'],
            'first_half_duration' => $match['first_half_duration'],
            'first_half_extra' => $match['first_half_extra'],
            'second_half_extra' => $match['second_half_extra'],
            'timer_start_unix' => $match['timer_start_unix'],
            'timer_elapsed' => $match['timer_elapsed'],
            'phase' => $match['phase'],
            'status' => $match['status'],
            'goals' => $goals,
            'cards' => $cards
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Error in api/live_match.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>