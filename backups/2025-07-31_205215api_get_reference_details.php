<?php
require_once '../includes/db-config.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$id = $_GET['id'] ?? 0;

try {
    switch ($category) {
        case 'match':
            $stmt = $pdo->prepare("SELECT CONCAT(team_home, ' vs ', team_away) as titre, 
                                  CONCAT('Résultat: ', score_home, '-', score_away) as description 
                                  FROM matches WHERE id = ?");
            break;
        case 'concours':
            $stmt = $pdo->prepare("SELECT titre, CONCAT('Édition ', YEAR(date_debut)) as description 
                                  FROM concours_miss WHERE id = ?");
            break;
        case 'cours':
            $stmt = $pdo->prepare("SELECT titre, 'Session de formation' as description 
                                  FROM cours_vacances WHERE id = ?");
            break;
        default:
            echo json_encode(null);
            exit();
    }
    
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}