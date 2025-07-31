<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Verify admin authentication before anything else
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    die("Accès non autorisé");
}

// Initialize database connection
try {
    require_once __DIR__ . '/includes/db-ssl.php';
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>Structure de la base de données</h1>";
    
    foreach ($tables as $table) {
        echo "<h2>Table: $table</h2>";
        
        // Get table structure
        $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show sample data (first 5 rows)
        try {
            $sampleData = $pdo->query("SELECT * FROM $table LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sampleData)) {
                echo "<h3>Exemple de données (5 premières lignes)</h3>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 40px;'>";
                
                // Table header
                echo "<tr>";
                foreach (array_keys($sampleData[0]) as $column) {
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }
                echo "</tr>";
                
                // Table rows
                foreach ($sampleData as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars(substr(strval($value), 0, 100)) . (strlen(strval($value)) > 100 ? '...' : '') . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p>Impossible d'afficher les exemples de données: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch (Exception $e) {
    die("Erreur de connexion à la base de données: " . htmlspecialchars($e->getMessage()));
}
?>
