&lt;?php
session_start();
require 'includes/db-config.php';

// Debug output
echo "&lt;pre&gt;";
echo "Testing Railway MySQL Authentication\n";
echo "==================================\n";

try {
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful!\n";
    
    // Check users table
    $users = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if (!$users) {
        die("ERROR: Users table not found");
    }
    
    // Check columns
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Users table columns: " . implode(', ', $columns) . "\n";
    
    // Check admin users
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    echo "Admin users: $adminCount\n";
    
    if ($adminCount > 0) {
        $admin = $pdo->query("SELECT id, email, role FROM users WHERE role='admin' LIMIT 1")->fetch();
        echo "Sample admin user:\n";
        print_r($admin);
        
        // Set test session
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['role'] = $admin['role'];
        
        echo "\nSession set for admin user. Redirecting to admin dashboard...";
        header("Refresh: 3; url=admin/dashboard.php");
    } else {
        echo "No admin users found. Please create an admin account first.";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

echo "&lt;/pre&gt;";
