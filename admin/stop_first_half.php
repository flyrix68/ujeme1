<?php
require_once __DIR__ . '/includes/db-ssl.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'])) {
    $matchId = (int)$_POST['match_id'];
    // Calcule le temps écoulé
    $stmt = $pdo->prepare("SELECT timer_start, timer_elapsed FROM matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    $elapsed = $match['timer_elapsed'];
    if ($match['timer_start']) {
        $elapsed += (strtotime('now') - strtotime($match['timer_start']));
    }
    // Passe en mi-temps et enregistre l'heure de pause
    $stmt = $pdo->prepare("UPDATE matches SET timer_start = NULL, timer_elapsed = ?, timer_status = 'half_time', timer_paused = 0, half_time_pause_start = NOW() WHERE id = ?");
    $stmt->execute([$elapsed, $matchId]);
    echo 'OK';
}