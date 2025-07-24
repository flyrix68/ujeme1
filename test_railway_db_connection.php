<?php
require_once __DIR__.'/includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    echo "Successfully connected to Railway database!\n";

    // Test teams table exists and has records
    $teams = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    echo "Teams table contains $teams records\n";

} catch (PDOException $e) {
    echo "Railway DB Connection failed: " . $e->getMessage() . "\n";
    error_log("Railway connection error: " . $e->getMessage());
    echo "Detailed error info:\n";
    print_r($e);
}
