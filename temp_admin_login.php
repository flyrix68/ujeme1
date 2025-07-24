&lt;?php
session_start();
require 'includes/db-config.php';

// Admin credentials from database
$adminEmail = "kacoujunior98@gmail.com";
$adminPassword = "junior"; // Temporary placeholder - in real scenario we wouldn't store this

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Attempt login
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($adminPassword, $admin['password'])) {
        // Set admin session
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['role'] = $admin['role'];
        
        // Redirect to admin dashboard
        header("Location: admin/dashboard.php");
        exit();
    } else {
        die("Failed to authenticate admin - please check credentials");
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?&gt;
