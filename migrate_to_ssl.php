<?php
/**
 * Migration Script: Update DatabaseConfig to DatabaseSSL
 * 
 * This script helps migrate from DatabaseConfig to DatabaseSSL by updating the database
 * configuration in all PHP files. It creates backups before making changes.
 */

// Configuration
$backupDir = __DIR__ . '/backups/' . date('Y-m-d_His');
$searchPatterns = [
    // Include patterns
    "/require.*['\"]\.\.\/includes\/db-config\.php['\"]/i",
    "/include.*['\"]\.\.\/includes\/db-config\.php['\"]/i",
    "/require.*['\"]includes\/db-config\.php['\"]/i",
    "/include.*['\"]includes\/db-config\.php['\"]/i",
    
    // Class usage patterns
    "/DatabaseConfig::getConnection\s*\(/i",
    "/DatabaseConfig::getTeamLogo\s*\(/i",
];

$replacements = [
    // Include replacements
    "require_once __DIR__ . '/includes/db-ssl.php'",
    "require_once __DIR__ . '/includes/db-ssl.php'",
    "require_once __DIR__ . '/includes/db-ssl.php'",
    "require_once __DIR__ . '/includes/db-ssl.php'",
    
    // Class usage replacements
    "DatabaseSSL::getInstance()->getConnection(",
    "DatabaseSSL::getInstance()->getTeamLogo(",
];

// Find all PHP files in the project
function getPhpFiles($dir) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'vendor') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            $files = array_merge($files, getPhpFiles($path));
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    
    return $files;
}

// Create backup of a file
function backupFile($filePath, $backupDir) {
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $relativePath = str_replace(DIRECTORY_SEPARATOR, '_', ltrim(str_replace(__DIR__, '', $filePath), DIRECTORY_SEPARATOR));
    $backupPath = $backupDir . $relativePath;
    
    if (copy($filePath, $backupPath)) {
        return $backupPath;
    }
    
    return false;
}

// Process a single file
function processFile($filePath, $backupDir) {
    global $searchPatterns, $replacements;
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "❌ Error reading file: $filePath\n";
        return false;
    }
    
    $originalContent = $content;
    
    // Apply replacements
    $content = preg_replace($searchPatterns, $replacements, $content);
    
    // Check if any changes were made
    if ($content === $originalContent) {
        return false; // No changes made
    }
    
    // Create backup
    $backupPath = backupFile($filePath, $backupDir);
    if ($backupPath === false) {
        echo "❌ Failed to create backup for: $filePath\n";
        return false;
    }
    
    // Save changes
    if (file_put_contents($filePath, $content) === false) {
        echo "❌ Failed to save changes to: $filePath\n";
        return false;
    }
    
    return true;
}

// Main execution
echo "=== Database Configuration Migration Tool ===\n\n";
echo "This tool will help migrate from DatabaseConfig to DatabaseSSL.\n";
echo "Backups will be saved to: $backupDir\n\n";

// Ask for confirmation
echo "WARNING: This will modify files in your project.\n";
echo "It's recommended to have a backup before proceeding.\n\n";

echo "Do you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'yes') {
    echo "\nMigration cancelled.\n";
    exit(0);
}

// Find all PHP files
$phpFiles = getPhpFiles(__DIR__);
$totalFiles = count($phpFiles);
$modifiedFiles = 0;

echo "\nFound $totalFiles PHP files to process.\n";

// Process each file
foreach ($phpFiles as $i => $file) {
    echo "[" . ($i + 1) . "/$totalFiles] Processing: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file) . "\r";
    
    if (processFile($file, $backupDir)) {
        $modifiedFiles++;
        echo "[" . ($i + 1) . "/$totalFiles] ✅ Modified: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file) . "\n";
    }
}

// Summary
echo "\n\n=== Migration Summary ===\n";
echo "Total files processed: $totalFiles\n";
echo "Files modified: $modifiedFiles\n";
echo "Backups saved to: $backupDir\n";

if ($modifiedFiles > 0) {
    echo "\nMigration completed successfully!\n";
    echo "Please test your application thoroughly.\n";
    echo "If you encounter any issues, you can restore files from the backup directory.\n";
} else {
    echo "\nNo files needed to be modified.\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Test all database operations\n";
echo "2. Check logs/db_connection.log for any errors\n";
echo "3. Review the MIGRATION_GUIDE.md for additional information\n";
