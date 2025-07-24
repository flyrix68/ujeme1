<?php
ob_start();
require __DIR__ . '/admin_header.php';

// Initialize empty teams array to prevent undefined variable errors
$teams = [];

// Verify database connection
if (!isset($pdo)) {
    error_log("Critical error: Database connection not available in admin/manage_teams.php");
    die("Database connection error. Please contact administrator.");
}

// Create poules table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS poules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL
    )");
    
    // Add poule_id column to teams table if it doesn't exist
    $result = $pdo->query("SHOW COLUMNS FROM teams LIKE 'poule_id'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE teams ADD COLUMN poule_id INT DEFAULT NULL");
    }
} catch (PDOException $e) {
    error_log("Error creating tables: " . $e->getMessage());
}

// Fetch all poules
try {
    $poules = $pdo->query("SELECT id, name, category FROM poules ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching poules: " . $e->getMessage());
    $poules = [];
}

// Handle poule operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Add new poule
        if ($_POST['action'] === 'add_poule') {
            $poule_name = filter_input(INPUT_POST, 'poule_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $poule_category = filter_input(INPUT_POST, 'poule_category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!empty($poule_name) && !empty($poule_category)) {
                $stmt = $pdo->prepare("INSERT INTO poules (name, category) VALUES (?, ?)");
                $stmt->execute([$poule_name, $poule_category]);
                $_SESSION['message'] = "Poule ajoutée avec succès !";
            } else {
                $_SESSION['error'] = "Nom de poule et catégorie requis.";
            }
            header("Location: manage_teams.php");
            exit();
        }
        
        // Delete poule
        if ($_POST['action'] === 'delete_poule') {
            $poule_id = filter_input(INPUT_POST, 'poule_id', FILTER_VALIDATE_INT);
            
            if ($poule_id) {
                // Reset poule_id for teams in this poule
                $stmt = $pdo->prepare("UPDATE teams SET poule_id = NULL WHERE poule_id = ?");
                $stmt->execute([$poule_id]);
                
                // Delete the poule
                $stmt = $pdo->prepare("DELETE FROM poules WHERE id = ?");
                $stmt->execute([$poule_id]);
                $_SESSION['message'] = "Poule supprimée avec succès !";
            }
            header("Location: manage_teams.php");
            exit();
        }
        
        // Assign teams to poules randomly
        if ($_POST['action'] === 'random_assignment') {
            $category = filter_input(INPUT_POST, 'category_filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!empty($category)) {
                // Get all teams from the specified category
                $stmt = $pdo->prepare("SELECT id FROM teams WHERE category = ?");
                $stmt->execute([$category]);
                $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Get all poules for the specified category
                $stmt = $pdo->prepare("SELECT id FROM poules WHERE category = ?");
                $stmt->execute([$category]);
                $category_poules = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($teams) && !empty($category_poules)) {
                    // Reset poule assignments for this category
                    $stmt = $pdo->prepare("UPDATE teams SET poule_id = NULL WHERE category = ?");
                    $stmt->execute([$category]);
                    
                    // Shuffle teams
                    shuffle($teams);
                    
                    // Distribute teams evenly across poules
                    $teams_per_poule = ceil(count($teams) / count($category_poules));
                    
                    foreach ($teams as $index => $team_id) {
                        $poule_index = floor($index / $teams_per_poule);
                        // Make sure we don't exceed the number of poules
                        if ($poule_index >= count($category_poules)) {
                            $poule_index = count($category_poules) - 1;
                        }
                        
                        $poule_id = $category_poules[$poule_index];
                        
                        $stmt = $pdo->prepare("UPDATE teams SET poule_id = ? WHERE id = ?");
                        $stmt->execute([$poule_id, $team_id]);
                    }
                    
                    $_SESSION['message'] = "Tirage au sort effectué avec succès pour la catégorie " . ucfirst($category) . " !";
                } else {
                    if (empty($teams)) {
                        $_SESSION['error'] = "Aucune équipe dans la catégorie sélectionnée.";
                    } else {
                        $_SESSION['error'] = "Aucune poule pour la catégorie sélectionnée.";
                    }
                }
            } else {
                $_SESSION['error'] = "Veuillez sélectionner une catégorie pour le tirage au sort.";
            }
            header("Location: manage_teams.php");
            exit();
        }
        
        // Clear poule assignments
        if ($_POST['action'] === 'clear_assignment') {
            $category = filter_input(INPUT_POST, 'category_filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!empty($category)) {
                $stmt = $pdo->prepare("UPDATE teams SET poule_id = NULL WHERE category = ?");
                $stmt->execute([$category]);
                $_SESSION['message'] = "Répartition en poules effacée pour la catégorie " . ucfirst($category) . " !";
            } else {
                $_SESSION['error'] = "Veuillez sélectionner une catégorie.";
            }
            header("Location: manage_teams.php");
            exit();
        }
        
        // Manually assign team to poule
        if ($_POST['action'] === 'assign_poule') {
            $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
            $poule_id = filter_input(INPUT_POST, 'poule_id', FILTER_VALIDATE_INT) ?: null;
            
            if ($team_id) {
                $stmt = $pdo->prepare("UPDATE teams SET poule_id = ? WHERE id = ?");
                $stmt->execute([$poule_id, $team_id]);
                $_SESSION['message'] = "Équipe assignée à la poule avec succès !";
            }
            header("Location: manage_teams.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error processing poule form: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors du traitement des données de poule.";
        header("Location: manage_teams.php");
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add or update team
        if (isset($_POST['add_team']) || isset($_POST['update_team'])) {
            $team_id = isset($_POST['team_id']) ? filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT) : null;
            $team_name = filter_input(INPUT_POST, 'team_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $manager_name = filter_input(INPUT_POST, 'manager_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $manager_email = filter_input(INPUT_POST, 'manager_email', FILTER_SANITIZE_EMAIL);
            $manager_phone = filter_input(INPUT_POST, 'manager_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $poule_id = !empty($_POST['poule_id']) ? filter_input(INPUT_POST, 'poule_id', FILTER_VALIDATE_INT) : null;

            if (empty($team_name) || !in_array($category, ['senior', 'junior', 'feminine']) || empty($location) || 
                empty($manager_name) || empty($manager_email) || empty($manager_phone)) {
                error_log("Invalid input for team: team_name=$team_name, category=$category, location=$location, manager_name=$manager_name");
                $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
            } else {
                // Handle logo upload
                $logo_name = null;
                if (!empty($_FILES['logo_path']['name'])) {
                    $upload_dir = '../uploads/logos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $file_extension = pathinfo($_FILES['logo_path']['name'], PATHINFO_EXTENSION);
                    $logo_name = uniqid('team_') . '.' . $file_extension;
                    $logo_path = $upload_dir . $logo_name;
                    if (!move_uploaded_file($_FILES['logo_path']['tmp_name'], $logo_path)) {
                        error_log("Failed to upload logo for team: $team_name");
                        $_SESSION['error'] = "Erreur lors du téléchargement du logo.";
                    }
                }

                if (isset($_POST['add_team'])) {
                    $stmt = $pdo->prepare("INSERT INTO teams (team_name, category, location, manager_name, manager_email, manager_phone, logo_path, poule_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$team_name, $category, $location, $manager_name, $manager_email, $manager_phone, $logo_name, $poule_id]);
                    $_SESSION['message'] = "Équipe ajoutée avec succès !";
                } elseif (isset($_POST['update_team']) && $team_id) {
                    if ($logo_name) {
                        // First get old logo to delete it
                        $stmt = $pdo->prepare("SELECT logo_path FROM teams WHERE id = ?");
                        $stmt->execute([$team_id]);
                        $old_logo = $stmt->fetchColumn();
                        if ($old_logo && file_exists("../uploads/logos/" . $old_logo)) {
                            unlink("../uploads/logos/" . $old_logo);
                        }
                        
                        $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, category = ?, location = ?, manager_name = ?, manager_email = ?, manager_phone = ?, logo_path = ?, poule_id = ? WHERE id = ?");
                        $stmt->execute([$team_name, $category, $location, $manager_name, $manager_email, $manager_phone, $logo_name, $poule_id, $team_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, category = ?, location = ?, manager_name = ?, manager_email = ?, manager_phone = ?, poule_id = ? WHERE id = ?");
                        $stmt->execute([$team_name, $category, $location, $manager_name, $manager_email, $manager_phone, $poule_id, $team_id]);
                    }
                    $_SESSION['message'] = "Équipe mise à jour avec succès !";
                }
            }
        }

        // Delete team
        if (isset($_POST['delete_team'])) {
            $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
            if ($team_id) {
                $stmt = $pdo->prepare("SELECT team_name FROM teams WHERE id = ?");
                $stmt->execute([$team_id]);
                $team_name = $stmt->fetchColumn();
                if ($team_name) {
                    $stmt = $pdo->prepare("DELETE FROM players WHERE team_id = ?");
                    $stmt->execute([$team_id]);
                    $stmt = $pdo->prepare("DELETE FROM matches WHERE team_home = ? OR team_away = ?");
                    $stmt->execute([$team_name, $team_name]);
                    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
                    $stmt->execute([$team_id]);
                    $_SESSION['message'] = "Équipe supprimée avec succès !";
                } else {
                    $_SESSION['error'] = "Équipe introuvable.";
                }
            } else {
                $_SESSION['error'] = "ID d'équipe invalide.";
            }
        }

        header("Location: manage_teams.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error processing team form: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors du traitement des données.";
        header("Location: manage_teams.php");
        exit();
    }
}

// Fetch all teams
try {
    $query = "SELECT t.id, t.team_name AS name, 
                     CASE 
                         WHEN t.logo_path IS NOT NULL THEN CONCAT('/uploads/logos/', t.logo_path)
                         ELSE '/assets/img/teams/default.png' 
                     END AS logo,
                     t.category, t.location,
                     t.manager_name, t.manager_email, t.manager_phone,
                     p.id AS poule_id, p.name AS poule_name
              FROM teams t
              LEFT JOIN poules p ON t.poule_id = p.id
              ORDER BY t.category, t.team_name";
    
    error_log("Executing teams query: " . $query);
    $stmt = $pdo->query($query);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Teams count: " . count($teams));
    if (empty($teams)) {
        error_log("Teams query returned empty result");
    } else {
        error_log("First team: " . print_r($teams[0], true));
    }
} catch (PDOException $e) {
    error_log("Error fetching teams: " . $e->getMessage());
    die("Erreur lors du chargement des équipes.");
}

// Get teams count by categories
$team_counts = [
    'senior' => 0,
    'junior' => 0,
    'feminine' => 0
];

foreach ($teams as $team) {
    if (isset($team_counts[$team['category']])) {
        $team_counts[$team['category']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Équipes - UJEM</title>
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
        .team-logo {
            width: 50px;
            height: auto;
        }
        .poule-badge {
            padding: 5px 8px;
            border-radius: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-size: 0.85rem;
        }
        .team-card {
            border-left: 4px solid #007bff;
        }
        .category-header {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-weight: bold;
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
                <h2 class="h3 mb-4"><i class="fas fa-users me-2"></i>Gestion des Équipes</h2>

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

                <ul class="nav nav-tabs mb-4" id="teamTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="teams-tab" data-bs-toggle="tab" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="true">
                            <i class="fas fa-users me-2"></i>Équipes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="poules-tab" data-bs-toggle="tab" data-bs-target="#poules" type="button" role="tab" aria-controls="poules" aria-selected="false">
                            <i class="fas fa-layer-group me-2"></i>Poules
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="teamTabContent">
                    <!-- Teams Tab -->
                    <div class="tab-pane fade show active" id="teams" role="tabpanel" aria-labelledby="teams-tab">
                        <!-- Form to add/edit team -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Ajouter/Modifier une Équipe</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="team_id" id="team_id">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="team_name" class="form-label">Nom de l'Équipe</label>
                                            <input type="text" name="team_name" id="team_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="category" class="form-label">Catégorie</label>
                                            <select name="category" id="category" class="form-select" required>
                                                <option value="">Sélectionner une catégorie</option>
                                                <option value="senior">Sénior</option>
                                                <option value="junior">Junior</option>
                                                <option value="feminine">Féminine</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="location" class="form-label">Lieu</label>
                                            <input type="text" name="location" id="location" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="poule_id" class="form-label">Poule</label>
                                            <select name="poule_id" id="poule_id" class="form-select">
                                                <option value="">Aucune poule</option>
                                                <?php foreach ($poules as $poule): ?>
                                                    <option value="<?= $poule['id'] ?>" data-category="<?= $poule['category'] ?>">
                                                        <?= htmlspecialchars($poule['name']) ?> (<?= ucfirst($poule['category']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="manager_name" class="form-label">Nom du Manager</label>
                                            <input type="text" name="manager_name" id="manager_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="manager_email" class="form-label">Email du Manager</label>
                                            <input type="email" name="manager_email" id="manager_email" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="manager_phone" class="form-label">Téléphone du Manager</label>
                                            <input type="text" name="manager_phone" id="manager_phone" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="logo_path" class="form-label">Logo (PNG)</label>
                                            <input type="file" name="logo_path" id="logo_path" class="form-control" accept="image/png">
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                        <button type="submit" name="add_team" id="add_team" class="btn btn-primary">Ajouter</button>
                                        <button type="submit" name="update_team" id="update_team" class="btn btn-primary d-none">Mettre à jour</button>
                                        <button type="button" id="reset_form" class="btn btn-secondary">Réinitialiser</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Teams list -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Équipes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($teams)): ?>
                                    <div class="alert alert-info">Aucune équipe enregistrée.</div>
                                <?php else: ?>
                                    <!-- Category filter -->
                                    <div class="mb-4">
                                        <label class="form-label">Filtrer par catégorie:</label>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary active filter-btn" data-filter="all">
                                                Toutes (<?= count($teams) ?>)
                                            </button>
                                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="senior">
                                                Sénior (<?= $team_counts['senior'] ?>)
                                            </button>
                                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="junior">
                                                Junior (<?= $team_counts['junior'] ?>)
                                            </button>
                                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="feminine">
                                                Féminine (<?= $team_counts['feminine'] ?>)
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Logo</th>
                                                    <th>Nom</th>
                                                    <th>Catégorie</th>
                                                    <th>Lieu</th>
                                                    <th>Poule</th>
                                                    <th>Manager</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teams as $team): ?>
                                                <tr class="team-row" data-category="<?= htmlspecialchars($team['category']) ?>">
                                                    <td>
                                                        <?php if ($team['logo']): ?>
                                                            <img src="../assets/img/teams/<?= htmlspecialchars($team['logo']) ?>" alt="<?= htmlspecialchars($team['name']) ?>" class="team-logo" onerror="this.src='../assets/img/teams/default.png'">
                                                        <?php else: ?>
                                                            <img src="../assets/img/teams/default.png" alt="Default" class="team-logo">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($team['name']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($team['category'])) ?></td>
                                                    <td><?= htmlspecialchars($team['location']) ?></td>
                                                    <td>
                                                        <?php if (!empty($team['poule_name'])): ?>
                                                            <span class="poule-badge"><?= htmlspecialchars($team['poule_name']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Non assignée</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($team['manager_name']) ?> (<?= htmlspecialchars($team['manager_email']) ?>)</td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm btn-outline-primary edit-team" 
                                                                    data-id="<?= $team['id'] ?>" 
                                                                    data-name="<?= htmlspecialchars($team['name']) ?>" 
                                                                    data-category="<?= htmlspecialchars($team['category']) ?>" 
                                                                    data-location="<?= htmlspecialchars($team['location']) ?>" 
                                                                    data-manager-name="<?= htmlspecialchars($team['manager_name']) ?>" 
                                                                    data-manager-email="<?= htmlspecialchars($team['manager_email']) ?>" 
                                                                    data-manager-phone="<?= htmlspecialchars($team['manager_phone']) ?>"
                                                                    data-poule-id="<?= $team['poule_id'] ?? '' ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            
                                                            <button class="btn btn-sm btn-outline-info assign-poule" 
                                                                    data-id="<?= $team['id'] ?>" 
                                                                    data-name="<?= htmlspecialchars($team['name']) ?>"
                                                                    data-category="<?= htmlspecialchars($team['category']) ?>"
                                                                    data-poule-id="<?= $team['poule_id'] ?? '' ?>">
                                                                <i class="fas fa-layer-group"></i>
                                                            </button>
                                                            
                                                            <form method="post" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment supprimer cette équipe ? Cela supprimera aussi ses joueurs et matchs associés.');">
                                                                <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                                                <button type="submit" name="delete_team" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Teams by Poule View -->
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-th-large me-2"></i>Équipes par Poule</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $categories = ['senior' => 'Sénior', 'junior' => 'Junior', 'feminine' => 'Féminine'];
                                
                                foreach ($categories as $cat_key => $cat_name): 
                                    // Filter teams by category
                                    $cat_teams = array_filter($teams, function($team) use ($cat_key) {
                                        return $team['category'] === $cat_key;
                                    });
                                    
                                    if (!empty($cat_teams)):
                                ?>
                                <div class="category-section mb-4" id="category-<?= $cat_key ?>">
                                    <div class="category-header">
                                        <i class="fas fa-trophy me-2"></i><?= $cat_name ?> 
                                        <span class="ms-2 badge bg-secondary"><?= count($cat_teams) ?> équipes</span>
                                        
                                        <!-- Poule assignment buttons -->
                                        <div class="float-end">
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="random_assignment">
                                                <input type="hidden" name="category_filter" value="<?= $cat_key ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" 
                                                        onclick="return confirm('Voulez-vous effectuer un tirage au sort pour les équipes <?= $cat_name ?> ?');">
                                                    <i class="fas fa-random me-1"></i> Tirage au sort
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="clear_assignment">
                                                <input type="hidden" name="category_filter" value="<?= $cat_key ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="return confirm('Voulez-vous effacer toutes les assignations de poules pour les équipes <?= $cat_name ?> ?');">
                                                    <i class="fas fa-eraser me-1"></i> Effacer assignations
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <?php
                                        // Get poules for this category
                                        $cat_poules = array_filter($poules, function($poule) use ($cat_key) {
                                            return $poule['category'] === $cat_key;
                                        });
                                        
                                        // Teams not assigned to any poule
                                        $unassigned_teams = array_filter($cat_teams, function($team) {
                                            return empty($team['poule_id']);
                                        });
                                        
                                        if (!empty($unassigned_teams)):
                                        ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Non assignées <span class="badge bg-secondary"><?= count($unassigned_teams) ?></span></h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (!empty($unassigned_teams)): ?>
                                                        <?php foreach ($unassigned_teams as $team): ?>
                                                            <div class="card mb-2 team-card">
                                                                <div class="card-body py-2">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if ($team['logo']): ?>
                                                                            <img src="../assets/img/teams/<?= htmlspecialchars(basename($team['logo'])) ?>" alt="<?= htmlspecialchars($team['name']) ?>" class="team-logo me-2" onerror="this.src='../assets/img/teams/default.png'">
                                                                        <?php else: ?>
                                                                            <img src="../assets/img/teams/default.png" alt="Default" class="team-logo me-2">
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <strong><?= htmlspecialchars($team['name']) ?></strong><br>
                                                                            <small class="text-muted"><?= htmlspecialchars($team['location']) ?></small>
                                                                        </div>
                                                                        <div class="ms-auto">
                                                                            <button class="btn btn-sm btn-outline-info assign-poule" 
                                                                                    data-id="<?= $team['id'] ?>" 
                                                                                    data-name="<?= htmlspecialchars($team['name']) ?>"
                                                                                    data-category="<?= htmlspecialchars($team['category']) ?>">
                                                                                <i class="fas fa-layer-group"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted">Aucune équipe non assignée</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Display teams by poule
                                        foreach ($cat_poules as $poule):
                                            $poule_teams = array_filter($cat_teams, function($team) use ($poule) {
                                                return $team['poule_id'] == $poule['id'];
                                            });
                                        ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Poule <?= htmlspecialchars($poule['name']) ?> <span class="badge bg-secondary"><?= count($poule_teams) ?></span></h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (!empty($poule_teams)): ?>
                                                        <?php foreach ($poule_teams as $team): ?>
                                                            <div class="card mb-2 team-card">
                                                                <div class="card-body py-2">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if ($team['logo']): ?>
                                                                            <img src="../assets/img/teams/<?= htmlspecialchars($team['logo']) ?>" alt="<?= htmlspecialchars($team['name']) ?>" class="team-logo me-2" onerror="this.src='../assets/img/teams/default.png'">
                                                                        <?php else: ?>
                                                                            <img src="../assets/img/teams/default.png" alt="Default" class="team-logo me-2">
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <strong><?= htmlspecialchars($team['name']) ?></strong><br>
                                                                            <small class="text-muted"><?= htmlspecialchars($team['location']) ?></small>
                                                                        </div>
                                                                        <div class="ms-auto">
                                                                            <button class="btn btn-sm btn-outline-info assign-poule" 
                                                                                    data-id="<?= $team['id'] ?>" 
                                                                                    data-name="<?= htmlspecialchars($team['name']) ?>"
                                                                                    data-category="<?= htmlspecialchars($team['category']) ?>"
                                                                                    data-poule-id="<?= $team['poule_id'] ?>">
                                                                                <i class="fas fa-layer-group"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted">Aucune équipe dans cette poule</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Poules Tab -->
                    <div class="tab-pane fade" id="poules" role="tabpanel" aria-labelledby="poules-tab">
                        <!-- Form to add poule -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Ajouter une Poule</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="add_poule">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="poule_name" class="form-label">Nom de la Poule</label>
                                            <input type="text" name="poule_name" id="poule_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="poule_category" class="form-label">Catégorie</label>
                                            <select name="poule_category" id="poule_category" class="form-select" required>
                                                <option value="">Sélectionner une catégorie</option>
                                                <option value="senior">Sénior</option>
                                                <option value="junior">Junior</option>
                                                <option value="feminine">Féminine</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                        <button type="submit" class="btn btn-primary">Ajouter la Poule</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Poules list -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Poules</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($poules)): ?>
                                    <div class="alert alert-info">Aucune poule enregistrée.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Catégorie</th>
                                                    <th>Nombre d'équipes</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($poules as $poule): 
                                                    // Count teams in this poule
                                                    $poule_team_count = count(array_filter($teams, function($team) use ($poule) {
                                                        return $team['poule_id'] == $poule['id'];
                                                    }));
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($poule['name']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($poule['category'])) ?></td>
                                                    <td><?= $poule_team_count ?></td>
                                                    <td>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment supprimer cette poule ?');">
                                                            <input type="hidden" name="action" value="delete_poule">
                                                            <input type="hidden" name="poule_id" value="<?= $poule['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
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
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Poule Assignment Modal -->
    <div class="modal fade" id="assignPouleModal" tabindex="-1" aria-labelledby="assignPouleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignPouleModalLabel">Assigner une poule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="assignPouleForm">
                        <input type="hidden" name="action" value="assign_poule">
                        <input type="hidden" name="team_id" id="assign_team_id">
                        <div class="mb-3">
                            <label class="form-label">Équipe</label>
                            <p id="assign_team_name" class="form-control-plaintext"></p>
                        </div>
                        <div class="mb-3">
                            <label for="assign_poule_id" class="form-label">Poule</label>
                            <select name="poule_id" id="assign_poule_id" class="form-select">
                                <option value="">Aucune poule</option>
                                <?php foreach ($poules as $poule): ?>
                                    <option value="<?= $poule['id'] ?>" data-category="<?= $poule['category'] ?>">
                                        <?= htmlspecialchars($poule['name']) ?> (<?= ucfirst($poule['category']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button click
            document.querySelectorAll('.edit-team').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('team_id').value = this.dataset.id;
                    document.getElementById('team_name').value = this.dataset.name;
                    document.getElementById('category').value = this.dataset.category;
                    document.getElementById('location').value = this.dataset.location;
                    document.getElementById('manager_name').value = this.dataset.managerName;
                    document.getElementById('manager_email').value = this.dataset.managerEmail;
                    document.getElementById('manager_phone').value = this.dataset.managerPhone;
                    document.getElementById('poule_id').value = this.dataset.pouleId || '';
                    document.getElementById('add_team').classList.add('d-none');
                    document.getElementById('update_team').classList.remove('d-none');
                    
                    // Scroll to form
                    document.querySelector('.card-header').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Handle reset form button
            document.getElementById('reset_form').addEventListener('click', function() {
                document.getElementById('team_id').value = '';
                document.getElementById('team_name').value = '';
                document.getElementById('category').value = '';
                document.getElementById('location').value = '';
                document.getElementById('manager_name').value = '';
                document.getElementById('manager_email').value = '';
                document.getElementById('manager_phone').value = '';
                document.getElementById('poule_id').value = '';
                document.getElementById('logo_path').value = '';
                document.getElementById('add_team').classList.remove('d-none');
                document.getElementById('update_team').classList.add('d-none');
            });

            // Filter teams by category
            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Update active button
                    document.querySelectorAll('.filter-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Filter table rows
                    document.querySelectorAll('.team-row').forEach(row => {
                        if (filter === 'all' || row.dataset.category === filter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
            
            // Filter poule options based on team category
            document.getElementById('category').addEventListener('change', function() {
                const selectedCategory = this.value;
                const pouleOptions = document.querySelectorAll('#poule_id option');
                
                // Reset selection
                document.getElementById('poule_id').value = '';
                
                // Show/hide options based on category
                pouleOptions.forEach(option => {
                    if (!option.dataset.category || option.value === '') {
                        // Always show "None" option
                        option.style.display = '';
                    } else if (option.dataset.category === selectedCategory) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
            
            // Handle assign poule button
            document.querySelectorAll('.assign-poule').forEach(button => {
                button.addEventListener('click', function() {
                    const teamId = this.dataset.id;
                    const teamName = this.dataset.name;
                    const teamCategory = this.dataset.category;
                    const pouleId = this.dataset.pouleId || '';
                    
                    document.getElementById('assign_team_id').value = teamId;
                    document.getElementById('assign_team_name').textContent = teamName;
                    document.getElementById('assign_poule_id').value = pouleId;
                    
                    // Filter poule options based on team category
                    const pouleOptions = document.querySelectorAll('#assign_poule_id option');
                    pouleOptions.forEach(option => {
                        if (!option.dataset.category || option.value === '') {
                            // Always show "None" option
                            option.style.display = '';
                        } else if (option.dataset.category === teamCategory) {
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('assignPouleModal'));
                    modal.show();
                });
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
