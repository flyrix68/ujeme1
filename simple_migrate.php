<?php
/**
 * Simple Migration Script
 * 
 * This script will help migrate from DatabaseConfig to DatabaseSSL
 */

// Configuration
$backupDir = __DIR__ . '/backups/' . date('Y-m-d_His');
$searchReplace = [
    // File includes
    "require.*['\"]\.\./includes/db-config\.php['\"]" => "require_once __DIR__ . '/includes/db-ssl.php'",
    "include.*['\"]\.\./includes/db-config\.php['\"]" => "require_once __DIR__ . '/includes/db-ssl.php'",
    "require.*['\"]includes/db-config\.php['\"]" => "require_once __DIR__ . '/includes/db-ssl.php'",
    "include.*['\"]includes/db-config\.php['\"]" => "require_once __DIR__ . '/includes/db-ssl.php'",
    
    // Class usage
    "DatabaseConfig::getConnection\s*\(([^)]*)\)" => "DatabaseSSL::getInstance()->getConnection($1)",
    "DatabaseConfig::getTeamLogo\s*\(([^)]*)\)" => "DatabaseSSL::getInstance()->getTeamLogo($1)",
];

// Get all PHP files
function getPhpFiles($dir) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'vendor') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            $files = array_merge($files, getPhpFiles($path));
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    
    return $files;
}

// Process a single file
function processFile($filePath, $backupDir) {
    global $searchReplace;
    
    $content = file_get_contents($filePath);
    if ($content === false) return false;
    
    $original = $content;
    
    // Apply replacements
    foreach ($searchReplace as $search => $replace) {
        $content = preg_replace("/$search/i", $replace, $content);
    }
    
    // If no changes, skip
    if ($content === $original) return false;
    
    // Create backup directory if it doesn't exist
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Create backup
    $backupFile = $backupDir . '/' . basename($filePath) . '.bak';
    if (!copy($filePath, $backupFile)) {
        echo "❌ Failed to create backup: $filePath\n";
        return false;
    }
    
    // Save changes
    if (file_put_contents($filePath, $content) === false) {
        echo "❌ Failed to save changes: $filePath\n";
        return false;
    }
    
    return true;
}

// Main execution
echo "=== Simple Database Migration Tool ===\n\n";

echo "This will modify files in your project.\n";
echo "A backup will be created in: $backupDir\n\n";

echo "Do you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'yes') {
    echo "\nMigration cancelled.\n";
    exit(0);
}

// Process files
$files = getPhpFiles(__DIR__);
$total = count($files);
$modified = 0;

echo "\nFound $total PHP files to process.\n";

foreach ($files as $i => $file) {
    echo "[" . ($i + 1) . "/$total] Processing: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file) . "\r";
    
    if (processFile($file, $backupDir)) {
        $modified++;
        echo "[" . ($i + 1) . "/$total] ✅ Modified: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file) . "\n";
    }
}

// Summary
echo "\n\n=== Migration Complete ===\n";
echo "Total files processed: $total\n";
echo "Files modified: $modified\n";
echo "Backups saved to: $backupDir\n\n";

echo "=== Next Steps ===\n";
echo "1. Test your application thoroughly\n";
echo "2. Check logs/db_connection.log for any errors\n";
echo "3. If you encounter issues, you can restore files from the backup directory\n\n";

echo "Migration completed!\n";
