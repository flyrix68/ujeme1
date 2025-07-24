&lt;?php
header('Content-Type: application/json');

// Health check minimaliste - répond immédiatement
http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'timestamp' => time(),
    'message' => 'API is running'
]);
