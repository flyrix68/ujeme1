<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    echo "Connected to database successfully\n";
    echo "Listing all tables:\n\n";
    
    // Query to list all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) === 0) {
        echo "No tables found in database";
    } else {
        echo "Tables in database:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error getting tables: " . $e->getMessage();
    echo "\n\nMake sure your .env has the correct DATABASE_URL credentials";
}
