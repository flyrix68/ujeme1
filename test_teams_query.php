<?php
require 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();

$teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY team_name")->fetchAll();

echo "Teams in database:\n";
print_r($teams);
