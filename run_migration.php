<?php
// Enable output buffering and error reporting
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/includes/db-config.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    // Execute the migration SQL files
    $sqlFiles = [
        __DIR__.'/sql/fix_status_column.sql',
        __DIR__.'/sql/update_score_defaults.sql'
    ];
    
    foreach ($sqlFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("SQL file not found: $file");
        }
        
        $sql = file_get_contents($file);
        $pdo->exec($sql);
    }
    
    // Verify the changes
    $statusCheck = $pdo->query("SHOW COLUMNS FROM matches LIKE 'status'")->fetch();
    $homeScoreCheck = $pdo->query("SHOW COLUMNS FROM matches WHERE Field = 'score_home'")->fetch();
    $awayScoreCheck = $pdo->query("SHOW COLUMNS FROM matches WHERE Field = 'score_away'")->fetch();
    
    $success = true;
    
    if (strpos($statusCheck['Type'], "enum('pending','ongoing','completed','finished')") === false && 
        strpos($statusCheck['Type'], "enum('pending','ongoing','completed')") === false) {
        echo "ERROR: Status column not updated properly\\n";
        $success = false;
    }
    
    if ($homeScoreCheck['Default'] != '0' || $awayScoreCheck['Default'] != '0') {
        echo "ERROR: Score defaults not updated properly\\n";
        $success = false;
    }
    
    if ($success) {
        echo "SUCCESS: All migrations applied successfully\\n";
        exit(0);
    } else {
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\\n";
    exit(1);
}
// Clean output buffer and send proper headers
header('Content-Type: text/plain');
ob_end_flush();
?>
