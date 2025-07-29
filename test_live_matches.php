<?php
require_once 'includes/db-config.php';

try {
    echo "Attempting database connection...\n";
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful\n\n";

    // Check for ongoing matches
    echo "Checking for ongoing matches...\n";
    $stmt = $pdo->query("SELECT id, team_home, team_away, status FROM matches WHERE status = 'ongoing'");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($matches)) {
        echo "No ongoing matches found in database\n";
    } else {
        echo "Found " . count($matches) . " ongoing matches:\n";
        foreach ($matches as $match) {
            echo "Match ID: " . $match['id'] . " - " . $match['team_home'] . " vs " . $match['team_away'] . "\n";
        }
    }

    // Test live_match API
    echo "\nTesting live_match API...\n";
    $url = 'http://localhost/ujem/api/live_match.php?competition=tournoi';
    echo "Requesting: $url\n";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if ($data['success']) {
        echo "API returned " . count($data['matches']) . " ongoing matches\n";
        if (!empty($data['matches'])) {
            print_r($data['matches'][0]); // Show first match details
        }
    } else {
        echo "API Error: " . $data['error'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
