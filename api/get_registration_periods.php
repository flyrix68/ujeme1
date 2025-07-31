&lt;?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db-ssl.php';

try {
    // Initialize database connection
    $pdo = DatabaseSSL::getInstance()->getConnection();
    error_log("Registration periods API - DB connection established");

    // Fetch active periods using prepared statement
    $stmt = $pdo->prepare("SELECT * FROM registration_periods WHERE is_active = 1 ORDER BY start_date");
    $stmt->execute();
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format standardized response
    $response = [
        'success' => true,
        'data' => array_map(function($period) {
            return [
                'id' => $period['id'],
                'category' => $period['category'],
                'start_date' => $period['start_date'],
                'end_date' => $period['end_date'],
                'closed_message' => $period['closed_message'],
                'is_active' => (bool)$period['is_active']
            ];
        }, $periods)
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Registration periods API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?&gt;
