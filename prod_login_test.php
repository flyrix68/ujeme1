&lt;?php
session_start();
require 'includes/db-config.php';

try {
    // Debug database connection
    $pdo = DatabaseConfig::getConnection();
    echo "Database connection successful!&lt;br&gt;";
    
    // Fetch admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "Found admin user: " . $admin['email'] . "&lt;br&gt;";
        
        // Set session
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_email'] = $admin['email']; 
        $_SESSION['role'] = $admin['role'];
        
        echo "Session set. Redirecting to admin dashboard...";
        header("Refresh: 3; url=admin/dashboard.php");
    } else {
        die("No admin users found!");
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?&gt;
