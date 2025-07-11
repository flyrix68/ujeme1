<?php
header('Content-Type: application/json');

// Basic health check without DB dependency
http_response_code(200);
echo json_encode([
    'status' => 'healthy',
    'timestamp' => time(),
    'version' => '1.0.1',
    'message' => 'Basic health check passed'
]);
