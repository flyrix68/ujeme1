<?php
header('Content-Type: application/json');
require_once '../includes/db-config.php';

// Get match ID from POST data
$matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);

if (!$matchId) {
    echo json_encode(['success' => false, 'error' => 'Invalid match ID']);
    exit();
}

try {
    // Get current match time from database
    $stmt = $pdo->prepare("
        SELECT *, UNIX_TIMESTAMP(timer_start) as timer_start_unix, 
               timer_elapsed, timer_status, timer_duration,
               first_half_duration, first_half_extra,
               second_half_extra, timer_paused
        FROM matches 
        WHERE id = ?
    ");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        echo json_encode(['success' => false, 'error' => 'Match not found']);
        exit();
    }

    // Calculate elapsed time
    $elapsed = $match['timer_elapsed'];
    if ($match['timer_start_unix'] && !$match['timer_paused']) {
        $elapsed += time() - $match['timer_start_unix'];
    }

    // Format time display
    $minutes = floor($elapsed / 60);
    $seconds = $elapsed % 60;
    $displayTime = sprintf('%02d:%02d', $minutes, $seconds);

    // Calculate total duration based on match status
    $totalDuration = $match['timer_duration'];
    if ($match['timer_status'] === 'second_half') {
        $totalDuration = $match['first_half_duration'] + ($match['first_half_extra'] ?? 0) + $match['second_half_duration'] + ($match['second_half_extra'] ?? 0);
    } elseif ($match['timer_status'] === 'first_half') {
        $totalDuration = $match['first_half_duration'] + ($match['first_half_extra'] ?? 0);
    }

    // Prepare response
    $response = [
        'success' => true,
        'display_time' => $displayTime . ' / ' . gmdate('i:s', $totalDuration),
        'is_ongoing' => $match['status'] === 'ongoing' && $match['timer_status'] !== 'ended',
        'elapsed' => $elapsed,
        'total_duration' => $totalDuration,
        'status' => $match['timer_status']
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Error in update_timer.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}
