#!/usr/bin/env php
<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful!\n";

    // Test query
    // Show table structure for critical tables
    echo "\nPlayers table structure:\n";
    $stmt = $pdo->query("DESCRIBE players");
    $playersStruct = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($playersStruct);

    echo "\nMatches table structure:\n";
    $stmt = $pdo->query("DESCRIBE matches");
    $matchesStruct = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($matchesStruct);

} catch (Exception $e) {
    http_response_code(500);
    echo "Database connection failed:\n";
    echo "Error: " . $e->getMessage() . "\n";
    error_log("DB Error: " . $e->getMessage());
}
?>
