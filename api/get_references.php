<?php
require_once '../includes/db-config.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$data = [];

try {
    switch ($category) {
        case 'match':
            $stmt = $pdo->query("SELECT id, team_home, team_away FROM matches ORDER BY match_date DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'concours':
            $stmt = $pdo->query("SELECT id, titre FROM concours_miss ORDER BY date_debut DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'cours':
            $stmt = $pdo->query("SELECT id, titre FROM cours_vacances ORDER BY date_debut DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
    
    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}