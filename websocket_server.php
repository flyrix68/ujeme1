<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db-config.php';

class MatchUpdates implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        global $pdo;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Handle subscription to match ID
        $data = json_decode($msg, true);
        if (isset($data['match_id'])) {
            $from->match_id = $data['match_id'];
            $this->sendMatchUpdate($from);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Method to send updates (called by external script or cron)
    public function sendMatchUpdate($conn) {
        if (!isset($conn->match_id)) return;

        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, 
                       UNIX_TIMESTAMP(m.timer_start) AS timer_start_unix,
                       t1.team_name AS team1_name, t1.logo_path AS team1_logo,
                       t2.team_name AS team2_name, t2.logo_path AS team2_logo,
                       p.name AS poule_name
                FROM matches m
                LEFT JOIN teams t1 ON m.team1_id = t1.id
                LEFT JOIN teams t2 ON m.team2_id = t2.id
                LEFT JOIN poules p ON m.poule_id = p.id
                WHERE m.id = ? AND m.status = 'ongoing'
            ");
            $stmt->execute([$conn->match_id]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($match) {
                $goalsStmt = $this->pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
                $goalsStmt->execute([$conn->match_id]);
                $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);

                $cardsStmt = $this->pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
                $cardsStmt->execute([$conn->match_id]);
                $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);

                $elapsed = $match['timer_elapsed'] ?? 0;
                if ($match['timer_start_unix']) {
                    $elapsed += (time() - $match['timer_start_unix']);
                }

                $conn->send(json_encode([
                    'type' => 'match_update',
                    'match' => $match,
                    'goals' => $goals,
                    'cards' => $cards,
                    'current_time' => gmdate('i:s', $elapsed)
                ]));
            }
        } catch (PDOException $e) {
            error_log("WebSocket error: " . $e->getMessage());
        }
    }

    // Broadcast updates to all clients (called externally)
    public function broadcastUpdate($matchId) {
        foreach ($this->clients as $client) {
            if (isset($client->match_id) && $client->match_id == $matchId) {
                $this->sendMatchUpdate($client);
            }
        }
    }
}

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new MatchUpdates()
        )
    ),
    8080
);

$server->run();