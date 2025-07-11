<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Log session data for debugging
error_log("Session data in admin/manage_players.php: " . print_r($_SESSION, true));

// Verify admin authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Unauthorized access attempt to admin/manage_players.php");
    header('Location: ../index.php');
    exit();
}

// Database connection
require_once '../includes/db-config.php';

// Verify database connection
if (!isset($pdo) || $pdo === null) {
    error_log("Critical error: Database connection not established in admin/manage_players.php");
    die("A database connection error occurred. Please contact the administrator.");
}

// Fetch all teams for form dropdown
try {
    $teams = $pdo->query("SELECT id, team_name AS name FROM teams ORDER BY team_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teams: " . $e->getMessage());
    die("Erreur lors du chargement des équipes.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add or update player
        if (isset($_POST['add_player']) || isset($_POST['update_player'])) {
            $player_id = isset($_POST['player_id']) ? filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT) : null;
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);

            if (empty($name) || !$team_id) {
                error_log("Invalid input for player: name=$name, team_id=$team_id");
                $_SESSION['error'] = "Le nom du joueur et l'équipe sont requis.";
            } else {
                // Handle photo upload
                $photoPath = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../Uploads/players/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('player_') . '.' . $extension;
                    $photoPath = $uploadDir . $filename;

                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                        error_log("Failed to upload photo for player: $name");
                        $_SESSION['error'] = "Erreur lors de l'upload de la photo.";
                        header("Location: manage_players.php");
                        exit();
                    }
                }

                if (isset($_POST['add_player'])) {
                    $stmt = $pdo->prepare("INSERT INTO players (name, team_id, photo) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $team_id, $photoPath]);
                    $_SESSION['message'] = "Joueur ajouté avec succès !";
                } elseif (isset($_POST['update_player']) && $player_id) {
                    // Fetch existing photo path for deletion
                    if ($photoPath) {
                        $stmt = $pdo->prepare("SELECT photo FROM players WHERE id = ?");
                        $stmt->execute([$player_id]);
                        $oldPhoto = $stmt->fetchColumn();
                        if ($oldPhoto && file_exists($oldPhoto)) {
                            unlink($oldPhoto);
                        }
                    }
                    $stmt = $pdo->prepare("UPDATE players SET name = ?, team_id = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$name, $team_id, $photoPath ?: ($oldPhoto ?: null), $player_id]);
                    $_SESSION['message'] = "Joueur mis à jour avec succès !";
                }
            }
        }

        // Delete player
        if (isset($_POST['delete_player'])) {
            $player_id = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
            if ($player_id) {
                // Delete associated photo
                $stmt = $pdo->prepare("SELECT photo FROM players WHERE id = ?");
                $stmt->execute([$player_id]);
                $photoPath = $stmt->fetchColumn();
                if ($photoPath && file_exists($photoPath)) {
                    unlink($photoPath);
                }

                // Delete associated goals and player
                $stmt = $pdo->prepare("DELETE FROM goals WHERE player = (SELECT name FROM players WHERE id = ?)");
                $stmt->execute([$player_id]);
                $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
                $stmt->execute([$player_id]);
                $_SESSION['message'] = "Joueur supprimé avec succès !";
            } else {
                $_SESSION['error'] = "ID de joueur invalide.";
            }
        }

        header("Location: manage_players.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error processing player form: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors du traitement des données.";
        header("Location: manage_players.php");
        exit();
    }
}

// Fetch all players
try {
    $players = $pdo->query("SELECT p.id, p.name, p.photo, t.team_name AS team_name, t.id AS team_id 
                            FROM players p 
                            JOIN teams t ON p.team_id = t.id 
                            ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching players: " . $e->getMessage());
    die("Erreur lors du chargement des joueurs.");
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
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .player-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
           <?php include '../includes/sidebar.php'; ?>  
           
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-user me-2"></i>Gestion des Joueurs</h2>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Form to add/edit player -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Ajouter/Modifier un Joueur</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="player_id" id="player_id">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="name" class="form-label">Nom du Joueur</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="team_id" class="form-label">Équipe</label>
                                    <select name="team_id" id="team_id" class="form-select" required>
                                        <option value="">Sélectionner une équipe</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="photo" class="form-label">Photo du Joueur</label>
                                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                <button type="submit" name="add_player" id="add_player" class="btn btn-primary">Ajouter</button>
                                <button type="submit" name="update_player" id="update_player" class="btn btn-primary d-none">Mettre à jour</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Players list -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Joueurs</h5>
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
                                            <th>Équipe</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($players as $player): ?>
                                        <tr>
                                            <td>
                                                <?php if ($player['photo']): ?>
                                                    <img src="<?= htmlspecialchars($player['photo']) ?>" 
                                                         alt="Photo de <?= htmlspecialchars($player['name']) ?>" 
                                                         class="player-photo">
                                                <?php else: ?>
                                                    <img src="../assets/img/players/default.png" 
                                                         alt="Photo par défaut" 
                                                         class="player-photo">
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($player['name']) ?></td>
                                            <td><?= htmlspecialchars($player['team_name']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-player" 
                                                        data-id="<?= $player['id'] ?>" 
                                                        data-name="<?= htmlspecialchars($player['name']) ?>" 
                                                        data-team-id="<?= $player['team_id'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment supprimer ce joueur ?');">
                                                    <input type="hidden" name="player_id" value="<?= $player['id'] ?>">
                                                    <button type="submit" name="delete_player" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit button click
        document.querySelectorAll('.edit-player').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('player_id').value = this.dataset.id;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('team_id').value = this.dataset.teamId;
                document.getElementById('add_player').classList.add('d-none');
                document.getElementById('update_player').classList.remove('d-none');
                document.getElementById('photo').value = ''; // Clear file input
            });
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>