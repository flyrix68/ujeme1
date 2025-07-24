&lt;?php
require 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();

// Check users table columns
$columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
echo "Users table columns: " . implode(', ', $columns) . "\n";

// Check admin user count
$adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
echo "Admin users: $adminCount\n";

// Sample admin user (for reference)
if ($adminCount > 0) {
    $admin = $pdo->query("SELECT email, role FROM users WHERE role='admin' LIMIT 1")->fetch();
    echo "Sample admin: " . $admin['email'] . " (Role: " . $admin['role'] . ")";
}
