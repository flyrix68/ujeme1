<?php
require __DIR__ . '/../includes/db-config.php';

$context = new ZMQContext();
$socket = $context->getSocket(ZMQ::SOCKET_PUSH);
$socket->bind("tcp://*:5555");

// Exemple: Notifier les changements toutes les 5 secondes
while (true) {
    $matches = $pdo->query("SELECT id FROM matches WHERE status = 'ongoing'")->fetchAll();
    
    foreach ($matches as $match) {
        $socket->send(json_encode(['match_id' => $match['id']]));
    }
    
    sleep(5);
}