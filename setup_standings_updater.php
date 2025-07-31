&lt;?php
require_once __DIR__ . '/includes/db-ssl.php';

// Create table if doesn't exist
$sql = file_get_contents(__DIR__.'/sql/create_match_processed_table.sql');
$pdo = DatabaseSSL::getInstance()->getConnection();
$pdo->exec($sql);

// Run standings updater
require 'update_standings.php';
