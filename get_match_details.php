&lt;?php
require_once __DIR__ . '/includes/db-ssl.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    $stmt = $pdo->prepare('SELECT * FROM matches WHERE id = 26');
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        header('Content-Type: application/json');
        echo json_encode($match, JSON_PRETTY_PRINT);
    } else {
        echo "No match found with ID 26";
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
exit();
?&gt;
