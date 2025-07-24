&lt;?php
require 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();
$stmt = $pdo->prepare('INSERT INTO users (email, password, prenom, nom, role) VALUES (?, ?, ?, ?, ?)');
$hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
$stmt->execute(['admin@ujem.com', $hashedPassword, 'Admin', 'User', 'admin']);
echo 'Test admin user created';
