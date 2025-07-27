<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Obtenir la connexion à la base de données
$pdo = DatabaseConfig::getConnection();
$pdo->setAttribute(PDO::ATCH_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Afficher les 5 derniers matchs
echo "<h2>Derniers matchs :</h2>";
$stmt = $pdo->query("SELECT id, team_home, team_away, match_date, status FROM matches ORDER BY id DESC LIMIT 5");
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date</th><th>Statut</th></tr>";

foreach ($matches as $match) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($match['id']) . "</td>";
    echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
    echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
    echo "<td>" . htmlspecialchars($match['match_date']) . "</td>";
    echo "<td>" . htmlspecialchars($match['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Vérifier les statuts des matchs
echo "<h2>Répartition des statuts :</h2>";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM matches GROUP BY status");
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>Statut</th><th>Nombre de matchs</th></tr>";

foreach ($statuses as $status) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($status['status']) . "</td>";
    echo "<td>" . htmlspecialchars($status['count']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Vérifier les matchs en cours
echo "<h2>Matchs en cours (status 'ongoing' ou 'pending') :</h2>";
$stmt = $pdo->query("SELECT id, team_home, team_away, match_date, status FROM matches WHERE status IN ('ongoing', 'pending') ORDER BY match_date ASC");
$currentMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($currentMatches) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Équipe domicile</th><th>Équipe extérieure</th><th>Date</th><th>Statut</th></tr>";
    
    foreach ($currentMatches as $match) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($match['id']) . "</td>";
        echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
        echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
        echo "<td>" . htmlspecialchars($match['match_date']) . "</td>";
        echo "<td>" . htmlspecialchars($match['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Aucun match en cours ou en attente trouvé.</p>";
}
?>
