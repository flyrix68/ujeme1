# Migration Guide: Updating to DatabaseSSL

This guide will help you migrate your application from the old `DatabaseConfig` class to the new `DatabaseSSL` class, which includes SSL support and improved functionality.

## 1. Update Database Configuration File

### Old Configuration (db-config.php)
```php
// Old way (db-config.php)
$pdo = DatabaseConfig::getConnection();
```

### New Configuration (db-ssl.php)
```php
// New way (db-ssl.php)
$db = DatabaseSSL::getInstance();
$pdo = $db->getConnection(); // Only if you need direct PDO access
```

## 2. Common Migration Patterns

### Getting a Database Connection

**Before:**
```php
require_once __DIR__ . '/includes/db-config.php';
try {
    $pdo = DatabaseConfig::getConnection();
} catch (Exception $e) {
    // Error handling
}
```

**After:**
```php
require_once __DIR__ . '/includes/db-ssl.php';
try {
    $db = DatabaseSSL::getInstance();
    // Use $db methods directly or get PDO instance:
    $pdo = $db->getConnection();
} catch (Exception $e) {
    // Error handling (now with better error messages)
}
```

### Executing Queries

**Before:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
```

**After:**
```php
// Option 1: Using DatabaseSSL methods
$user = $db->getRow("SELECT * FROM users WHERE id = ?", [$userId]);

// Option 2: Using PDO directly (if needed)
$stmt = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
$user = $stmt->fetch();
```

### Inserting Data

**Before:**
```php
$stmt = $pdo->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
$stmt->execute([$username, $email]);
$userId = $pdo->lastInsertId();
```

**After:**
```php
// Using DatabaseSSL insert method
$userId = $db->insert('users', [
    'username' => $username,
    'email' => $email
]);
```

### Updating Data

**Before:**
```php
$stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
$stmt->execute([$newEmail, $userId]);
$affectedRows = $stmt->rowCount();
```

**After:**
```php
$affectedRows = $db->update(
    'users', 
    ['email' => $newEmail],
    'id = ?',
    [$userId]
);
```

### Getting Multiple Rows

**Before:**
```php
$stmt = $pdo->query("SELECT * FROM posts WHERE status = 'published'");
$posts = $stmt->fetchAll();
```

**After:**
```php
$posts = $db->getRows("SELECT * FROM posts WHERE status = ?", ['published']);
```

## 3. Special Cases

### Using getTeamLogo

**Before:**
```php
$logo = DatabaseConfig::getTeamLogo($teamName);
```

**After:**
```php
// You'll need to implement getTeamLogo in DatabaseSSL or use an alternative approach
// For example, you could add this method to DatabaseSSL class:
// public function getTeamLogo($teamName) {
//     $team = $this->getRow("SELECT logo FROM teams WHERE name = ?", [$teamName]);
//     return $team ? $team['logo'] : 'default-logo.png';
// }
```

## 4. Testing the Migration

1. Test all database operations in a development environment
2. Check the `logs/db_connection.log` for any errors
3. Verify that SSL is being used by checking the logs for "Using SSL with CA certificate"

## 5. Deployment

1. Backup your current database configuration
2. Deploy the new `db-ssl.php` file
3. Update all files to use the new `DatabaseSSL` class
4. Test thoroughly in production

## 6. Rollback Plan

If you encounter issues, you can revert to the old configuration by:
1. Restoring the old `db-config.php` file
2. Reverting any file changes that use `DatabaseSSL`
3. Checking the logs for any errors that need to be addressed

## 7. Additional Notes

- The new `DatabaseSSL` class includes automatic connection retries
- All database errors are logged to `logs/db_connection.log`
- The connection automatically reconnects if it's been idle for more than 5 minutes
- SSL verification can be enabled/disabled using the `ssl_verify` configuration option
