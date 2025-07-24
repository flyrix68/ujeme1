<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Verify admin authentication with CSRF protection
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'member') !== 'admin') {
    error_log("Unauthorized access attempt to manage_players from IP: " . $_SERVER['REMOTE_ADDR']);
    header('Location: ../index.php');
    exit();
}

// Generate and store CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Handle form submissions with CSRF check
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("CSRF token validation failed in manage_players.php");
            $_SESSION['error'] = "Erreur de sécurité. Veuillez réessayer.";
            header("Location: manage_players.php");
            exit();
        }

        // Add new player
        if (isset($_POST['add_player'])) {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);
            $jersey_number = filter_input(INPUT_POST, 'jersey_number', FILTER_VALIDATE_INT);
            $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
            
            // Validate inputs
            if (empty($name) || !$team_id) {
                $_SESSION['error'] = "Le nom du joueur et l'équipe sont requis.";
            } else {
                // Handle file upload
                $photoPath = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/players/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Validate image
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($fileInfo, $_FILES['photo']['tmp_name']);
                    
                    if (in_array($mimeType, $allowedTypes)) {
                        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                        $filename = uniqid('player_') . '.' . $extension;
                        $photoPath = $uploadDir . $filename;
                        
                        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                            $_SESSION['error'] = "Erreur lors de l'upload de la photo.";
                        }
                    } else {
                        $_SESSION['error'] = "Type de fichier non supporté. Seuls JPEG, PNG et GIF sont autorisés.";
                    }
                }
                
                if (!isset($_SESSION['error'])) {
                    $stmt = $pdo->prepare("INSERT INTO players (name, position, jersey_number, team_id, photo) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $position, $jersey_number, $team_id, $photoPath]);
                    $_SESSION['message'] = "Joueur ajouté avec succès!";
                }
            }
        }
        
        // Update player
        if (isset($_POST['update_player'])) {
            $player_id = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);
            $jersey_number = filter_input(INPUT_POST, 'jersey_number', FILTER_VALIDATE_INT);
            $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
            
            if (!$player_id || empty($name) || !$team_id) {
                $_SESSION['error'] = "Données invalides pour la mise à jour.";
            } else {
                // Handle file upload
                $photoPath = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/players/';
                    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('player_') . '.' . $extension;
                    $photoPath = $uploadDir . $filename;
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                        $_SESSION['error'] = "Erreur lors de l'upload de la photo.";
                    } else {
                        // Delete old photo if exists
                        $stmt = $pdo->prepare("SELECT photo FROM players WHERE id = ?");
                        $stmt->execute([$player_id]);
                        $oldPhoto = $stmt->fetchColumn();
                        if ($oldPhoto && file_exists($oldPhoto)) {
                            unlink($oldPhoto);
                        }
                    }
                }
                
                if (!isset($_SESSION['error'])) {
                    $stmt = $pdo->prepare("UPDATE players 
                                           SET name = ?, position = ?, jersey_number = ?, team_id = ?, 
                                               photo = IFNULL(?, photo) 
                                           WHERE id = ?");
                    $stmt->execute([$name, $position, $jersey_number, $team_id, $photoPath, $player_id]);
                    $_SESSION['message'] = "Joueur mis à jour avec succès!";
                }
            }
        }
        
        // Delete player
        if (isset($_POST['delete_player'])) {
            $player_id = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
            
            if ($player_id) {
                // Get photo path before deletion
                $stmt = $pdo->prepare("SELECT photo FROM players WHERE id = ?");
                $stmt->execute([$player_id]);
                $photoPath = $stmt->fetchColumn();
                
                // Delete player
                $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
                $stmt->execute([$player_id]);
                
                // Delete photo file if exists
                if ($photoPath && file_exists($photoPath)) {
                    unlink($photoPath);
                }
                
                $_SESSION['message'] = "Joueur supprimé avec succès!";
            } else {
                $_SESSION['error'] = "ID de joueur invalide.";
            }
        }
        
        header("Location: manage_players.php");
        exit();
    }
    
    // Fetch all players with team info
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.position, p.jersey_number, p.photo, 
               t.id AS team_id, t.team_name
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        ORDER BY t.team_name, p.name
    ");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all teams for dropdown
    $teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY team_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur de base de données est survenue.";
    header("Location: manage_players.php");
    exit();
}

// Helper function for safe output
function safe_html($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
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
        .form-section {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-users me-2"></i>Gestion des Joueurs</h2>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= safe_html($_SESSION['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= safe_html($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card form-section">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Ajouter/Modifier un Joueur</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data" id="playerForm">
                            <input type="hidden" name="player_id" id="player_id" value="">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nom complet</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="position" class="form-label">Position</label>
                                    <select class="form-select" id="position" name="position" required>
                                        <option value="">Sélectionner</option>
                                        <option value="gardien">Gardien</option>
                                        <option value="defenseur">Défenseur</option>
                                        <option value="milieu">Milieu</option>
                                        <option value="attaquant">Attaquant</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="jersey_number" class="form-label">Numéro</label>
                                    <input type="number" class="form-control" id="jersey_number" name="jersey_number" min="1" max="99">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="team_id" class="form-label">Équipe</label>
                                    <select class="form-select" id="team_id" name="team_id" required>
                                        <option value="">Sélectionner une équipe</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?= safe_html($team['id']) ?>">
                                                <?= safe_html($team['team_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="photo" class="form-label">Photo</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
                                    <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                                    <div class="form-text">Les photos seront redimensionnées à 300x300 pixels.</div>
                                </div>
                                <input type="hidden" name="csrf_token" value="<?= safe_html($_SESSION['csrf_token']) ?>">
                                
                                <div class="col-12 mt-3">
                                    <button type="submit" name="add_player" id="addBtn" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Ajouter
                                    </button>
                                    <button type="submit" name="update_player" id="updateBtn" class="btn btn-warning d-none">
                                        <i class="fas fa-save me-1"></i> Mettre à jour
                                    </button>
                                    <button type="button" id="cancelBtn" class="btn btn-secondary d-none">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
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
                                                    <img src="<?= safe_html($player['photo']) ?>" 
                                                         alt="<?= safe_html($player['name']) ?>" 
                                                         class="player-photo">
                                                <?php else: ?>
                                                    <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= safe_html($player['name']) ?></td>
                                            <td><?= safe_html($player['position']) ?></td>
                                            <td><?= safe_html($player['jersey_number']) ?></td>
                                            <td><?= safe_html($player['team_name'] ?? 'Aucune équipe') ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-player" 
                                                        data-id="<?= safe_html($player['id']) ?>"
                                                        data-name="<?= safe_html($player['name']) ?>"
                                                        data-position="<?= safe_html($player['position']) ?>"
                                                        data-jersey="<?= safe_html($player['jersey_number']) ?>"
                                                        data-team="<?= safe_html($player['team_id']) ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce joueur?');">
                                                    <input type="hidden" name="csrf_token" value="<?= safe_html($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="player_id" value="<?= safe_html($player['id']) ?>">
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
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button clicks
            document.querySelectorAll('.edit-player').forEach(btn => {
                btn.addEventListener('click', function() {
                    const playerId = this.dataset.id;
                    const playerName = this.dataset.name;
                    const position = this.dataset.position;
                    const jerseyNumber = this.dataset.jersey;
                    const teamId = this.dataset.team;
                    
                    // Fill the form
                    document.getElementById('player_id').value = playerId;
                    document.getElementById('name').value = playerName;
                    document.getElementById('position').value = position;
                    document.getElementById('jersey_number').value = jerseyNumber;
                    document.getElementById('team_id').value = teamId;
                    
                    // Toggle buttons
                    document.getElementById('addBtn').classList.add('d-none');
                    document.getElementById('updateBtn').classList.remove('d-none');
                    document.getElementById('cancelBtn').classList.remove('d-none');
                    
                    // Scroll to form
                    document.getElementById('playerForm').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Handle cancel button
            document.getElementById('cancelBtn').addEventListener('click', function() {
                // Reset form
                document.getElementById('playerForm').reset();
                document.getElementById('player_id').value = '';
                
                // Toggle buttons
                document.getElementById('addBtn').classList.remove('d-none');
                document.getElementById('updateBtn').classList.add('d-none');
                this.classList.add('d-none');
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
