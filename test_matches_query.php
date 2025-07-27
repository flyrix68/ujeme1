<?php
// Same database setup as admin_header.php
$dbConfigPath = __DIR__ . '/includes/db-config.php';
if (!file_exists($dbConfigPath)) {
    die("Database config not found at: $dbConfigPath\n");
}

require $dbConfigPath;

echo "Testing DB connection and matches table...\n";

try {
    // Establish DB connection using the same method as admin
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully\n\n";

    // Check if matches table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'matches'");
    $matchesTableExists = $stmt->fetch() !== false;

    if (!$matchesTableExists) {
        echo "ERROR: Matches table does not exist\n";
        exit(1);
    }
    echo "Matches table exists\n";

    // Show table structure
    $stmt = $pdo->query("DESCRIBE matches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nMatches table structure:\n";
    foreach ($columns as $col) {
        printf("%-15s %-10s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }

    // Count ongoing/pending matches
    $stmt = $pdo->query("SELECT COUNT(*) FROM matches WHERE status IN ('ongoing', 'pending')");
    $count = $stmt->fetchColumn();
    echo "\nNumber of ongoing/pending matches: $count\n";

    if ($count > 0) {
        // Show sample matches
        $matches = $pdo->query("
            SELECT id, team_home, team_away, status, match_date 
            FROM matches 
            WHERE status IN ('ongoing', 'pending') 
            ORDER BY match_date DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nRecent ongoing/pending matches:\n";
        foreach ($matches as $match) {
            printf(
                "%4d | %-20s vs %-20s | %-8s | %s\n",
                $match['id'],
                $match['team_home'],
                $match['team_away'],
                $match['status'],
                $match['match_date']
            );
        }
    }

} catch (PDOException $e) {
    echo "\nDATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nTest completed\n";
