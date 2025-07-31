<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/db-config.php';

// Initialize database connection
try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    if (!$pdo) {
        throw new Exception('Failed to connect to database');
    }
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}

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
    
    error_log("Live matches query - Competition: $competition");
    error_log("Found matches: " . json_encode($matches));

    $result = ['success' => true, 'matches' => [], 'websocket_url' => 'ws://localhost:8080'];

    // Ajoute les buts, cartons et calcul du temps pour chaque match
    foreach ($matches as &$match) {
        // Calculate current time with pause handling
        $elapsed = (int)($match['timer_elapsed'] ?? 0);
        
        if ($match['timer_status'] === 'first_half' && $match['timer_start_unix']) {
            $elapsed = time() - $match['timer_start_unix'];
        } elseif ($match['timer_status'] === 'second_half' && $match['second_half_start']) {
            $elapsed = floor($match['timer_duration'] / 2) + (time() - strtotime($match['second_half_start']));
        }
        
        // Apply extra time
        if ($match['timer_status'] === 'first_half') {
            $elapsed += (int)($match['first_half_extra'] ?? 0);
        } elseif ($match['timer_status'] === 'second_half') {
            $elapsed += (int)($match['second_half_extra'] ?? 0);
        }
        
        $elapsed = max(0, $elapsed); // Ensure non-negative

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
