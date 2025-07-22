<?php
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

$response = [
    'status' => 'healthy', 
    'timestamp' => time(),
    'services' => [
        'webserver' => 'running',
        'database' => [
            'status' => 'unknown',
            'error' => null
        ]
    ],
    'system' => [
        'php_version' => phpversion(),
        'extensions' => get_loaded_extensions()
    ],
    'environment' => [
        'DATABASE_URL' => getenv('DATABASE_URL') ? 'set' : 'not set',
        'DB_HOST' => getenv('DB_HOST'),
        'DB_PORT' => getenv('DB_PORT'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER')
    ]
];

    try {
        // Check database connection with detailed diagnostics
        require_once __DIR__ . '/../includes/db-config.php';
        
        // Log environment for debugging
        error_log("Environment: " . print_r($_SERVER, true));
        
        try {
            $pdo = DatabaseConfig::getConnection(1, 0); // Single attempt, no delay
            if ($pdo === null) {
                throw new RuntimeException('Database connection failed');
            }
            
            $response['services']['database'] = [
                'status' => 'connected',
                'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
                'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'timeout' => $pdo->getAttribute(PDO::ATTR_TIMEOUT),
                'env' => [
                    'DATABASE_URL' => getenv('DATABASE_URL') ? 'set' : 'not set',
                    'DB_HOST' => getenv('DB_HOST'),
                    'DB_USER' => getenv('DB_USER')
                ]
            ];

        // Test simple query
        $stmt = $pdo->query("SELECT 1");
        $response['services']['database']['query_test'] = $stmt->fetchColumn() === '1' ? 'success' : 'failed';
    } catch (Exception $e) {
        $response['services']['database'] = [
            'status' => 'error',
            'error' => $e->getMessage(),
            'trace' => $e->getTrace()
        ];
        $response['status'] = 'degraded';
    }

    // Try to establish database connection with debug info
    try {
        $pdo = DatabaseConfig::getConnection(1, 0); // Single attempt, no delay
        
        $response['services']['database'] = [
            'status' => 'connected',
            'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'tables' => $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)
        ];
        
    } catch (Exception $e) {
        $response['status'] = 'unhealthy';
        $response['services']['database'] = [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }

    // Set HTTP status
    // Only return 503 for truly unhealthy states (error/database completely down)
    http_response_code($response['status'] === 'error' ? 503 : 200);

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTrace(),
        'timestamp' => time()
    ];
    http_response_code(503);
}

// Clear any unexpected output
ob_end_clean();
echo json_encode($response, JSON_PRETTY_PRINT);
