<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\ZMQ\Context;

class MatchUpdates implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct($pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
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
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

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
                $goals = $this->pdo->query("SELECT * FROM goals WHERE match_id = {$conn->match_id} ORDER BY minute")
                    ->fetchAll(PDO::FETCH_ASSOC);
                
                $cards = $this->pdo->query("SELECT * FROM cards WHERE match_id = {$conn->match_id} ORDER BY minute")
                    ->fetchAll(PDO::FETCH_ASSOC);

                $elapsed = $match['timer_elapsed'] ?? 0;
                if ($match['timer_start_unix']) {
                    $elapsed += (time() - $match['timer_start_unix']);
                }

                $conn->send(json_encode([
                    'type' => 'match_update',
                    'match' => $match,
                    'goals' => $goals,
                    'cards' => $cards,
                    'current_time' => gmdate('i:s', $elapsed),
                    'timestamp' => time()
                ]));
            }
        } catch (PDOException $e) {
            error_log("WebSocket error: " . $e->getMessage());
        }
    }

    public function broadcastUpdate($matchId) {
        foreach ($this->clients as $client) {
            if (isset($client->match_id) && $client->match_id == $matchId) {
                $this->sendMatchUpdate($client);
            }
        }
    }
}

// Démarrer le serveur
function runWebSocketServer($pdo) {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new MatchUpdates($pdo)
            )
        ),
        8080
    );

    // Configuration ZMQ pour recevoir les notifications
    $context = new React\ZMQ\Context($server->loop);
    $pull = $context->getSocket(ZMQ::SOCKET_PULL);
    $pull->bind('tcp://127.0.0.1:5555');
    $pull->on('message', function($msg) use ($server) {
        $data = json_decode($msg, true);
        foreach ($server->app->connections as $conn) {
            if ($conn->match_id == $data['match_id']) {
                $server->app->sendMatchUpdate($conn);
            }
        }
    });

    $server->run();
}