<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Verify admin authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Unauthorized access attempt to manage_players.php");
    header('Location: ../index.php');
    exit();
}

try {
    require_once '../includes/db-config.php';
    $pdo = DatabaseConfig::getConnection();
    
    // Form handling goes here
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle player add/edit/delete
    }

    // Fetch all players
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.position, p.jersey_number, 
               p.photo, t.team_name, p.user_id, p.team_id
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        ORDER BY t.team_name, p.name
    ");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all teams for dropdown
    $teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY team_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error in manage_players.php: " . $e->getMessage());
    die("Erreur lors du traitement des joueurs.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Joueurs - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .player-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-users me-2"></i>Gestion des Joueurs</h2>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Liste des Joueurs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($players)): ?>
                            <div class="alert alert-info">Aucun joueur enregistré.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>Nom</th>
                                            <th>Position</th>
                                            <th>Numéro</th>
                                            <th>Équipe</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($players as $player): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($player['photo'])): ?>
                                                    <img src="<?= safe_html($player['photo']) ?>" alt="<?= safe_html($player['name']) ?>" class="player-photo">
                                                <?php else: ?>
                                                    <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= safe_html($player['name']) ?></td>
                                            <td><?= safe_html($player['position']) ?></td>
                                            <td><?= safe_html($player['jersey_number']) ?></td>
                                            <td><?= safe_html($player['team_name'] ?? 'Aucune équipe') ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-player">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-player">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Ajouter/Modifier un Joueur</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Position</label>
                                    <select name="position" class="form-select" required>
                                        <option value="gardien">Gardien</option>
                                        <option value="defenseur">Défenseur</option>
                                        <option value="milieu">Milieu</option>
                                        <option value="attaquant">Attaquant</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Numéro de maillot</label>
                                    <input type="number" name="jersey_number" class="form-control" min="1" max="99
