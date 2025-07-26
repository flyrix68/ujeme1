&lt;?php
require 'includes/db-config.php';

// Create table if doesn't exist
$sql = file_get_contents(__DIR__.'/sql/create_match_processed_table.sql');
$pdo = DatabaseConfig::getConnection();
$pdo->exec($sql);

// Run standings updater
require 'update_standings.php';
