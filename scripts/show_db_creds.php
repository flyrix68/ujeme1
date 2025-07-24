<?php
require_once __DIR__ . '/../includes/db-config.php';

header('Content-Type: text/plain');

function maskPassword($pass, $showFirst=2) {
    return substr($pass, 0, $showFirst) . str_repeat('*', strlen($pass)-$showFirst);
}

echo "Database Connection Details:\n\n";

if (!getenv('DATABASE_URL')) {
    echo "DATABASE_URL not set in environment\n";
    exit(1);
}

$db_url = getenv('DATABASE_URL');

echo "Original URL: $db_url\n\n";

// Parse URL
preg_match('#^mysql://([^:]+):([^@]+)@([^/:]+):?([0-9]*)/(.+)$#', $db_url, $matches);

echo "Host: ".$matches[3]."\n";
echo "Port: ".($matches[4] ?: '3306 (default)')."\n";
echo "Username: ".$matches[1]."\n";
echo "Password: ".maskPassword($matches[2])."\n"; 
echo "Database: ".$matches[5]."\n\n";

echo "MySQL Command Line:\n";
echo sprintf(
    'mysql -h %s -P %d -u %s -p%s %s',
    $matches[3],
    ($matches[4] ?: 3306),
    $matches[1],
    $matches[2],
    $matches[5]
);
