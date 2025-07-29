<?php
require_once 'includes/db-config.php';

try {
    echo "Testing live_match API directly...\n";
    $_GET['competition'] = 'tournoi';
    require 'api/live_match.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
