<?php
require_once 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();
$stmt = $pdo->query('SELECT competition FROM matches WHERE id = 41');
$competition = $stmt->fetchColumn();
echo "Competition for match 41: " . ($competition ?: 'NULL');
