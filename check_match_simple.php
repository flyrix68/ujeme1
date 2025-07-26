<?php
require 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();
$match = $pdo->query('SELECT * FROM matches WHERE id = 26')->fetch(PDO::FETCH_ASSOC);
print_r($match);
?>
