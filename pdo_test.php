<?php
try {
    $pdo = new PDO('mysql:host=db;dbname=activite_ujem', 'app_user', 'userpassword', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $result = $pdo->query("SELECT 'Connection successful' AS status")->fetch();
    echo $result['status'];
} catch (PDOException $e) {
    echo "CONNECTION FAILED: " . $e->getMessage();
}
