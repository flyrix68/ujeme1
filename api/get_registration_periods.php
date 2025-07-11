<?php
header('Content-Type: application/json');
require_once '../includes/db-config.php';

try {
    $stmt = $pdo->query("SELECT * FROM registration_periods WHERE is_active = 1 ORDER BY start_date");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for frontend
    $formatted = [];
    foreach ($periods as $period) {
        $formatted[$period['category']] = [
            'start' => $period['start_date'],
            'end' => $period['end_date'],
            'closed_message' => $period['closed_message']
        ];
    }
    
    echo json_encode($formatted);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
