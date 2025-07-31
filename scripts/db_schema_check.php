<?php
require_once __DIR__ . '/../includes/db-config.php';

header('Content-Type: application/json');

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    // Get matches table status column info
    $statusColumn = $pdo->query("SHOW COLUMNS FROM matches LIKE 'status'")->fetch();
    
    // Get tables list
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'status_column' => $statusColumn,
        'tables' => $tables,
        'matches_rows' => $pdo->query("SELECT id, status FROM matches LIMIT 10")->fetchAll()
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}
