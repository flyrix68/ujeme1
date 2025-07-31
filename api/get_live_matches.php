<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db-ssl.php';

$matches = $pdo->query("
    SELECT id, timer_start, timer_elapsed, timer_paused, score_home, score_away 
    FROM matches 
    WHERE status = 'ongoing'
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['matches' => $matches]);