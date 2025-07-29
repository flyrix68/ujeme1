<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to log actions in the audit log
function logAction($pdo, $matchId, $actionType, $actionDetails = null, $previousValue = null, $newValue = null) {
    // Validate inputs
    if (!is_numeric($matchId) || !is_string($actionType) || !in_array($actionType, [
        'UPDATE_SCORE', 'ADD_GOAL', 'ADD_CARD', 'START_FIRST_HALF', 'END_FIRST_HALF', 
        'START_SECOND_HALF', 'END_MATCH', 'SET_EXTRA_TIME', 'FINALIZE_MATCH', 
        'SET_MATCH_DURATION', 'UPDATE_STANDING', 'CREATE_STANDING', 'DELETE_MATCH'
    ])) {
        error_log("Invalid logAction inputs: matchId=$matchId, actionType=$actionType");
        return;
    }
    
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        error_log("Invalid user_id in logAction: " . print_r($_SESSION, true));
        return;
    }

    // Format action_details as JSON
    $actionDetailsJson = null;
    if (!is_null($actionDetails)) {
        if (function_exists('mb_convert_encoding')) {
            $actionDetails = mb_convert_encoding((string)$actionDetails, 'UTF-8', 'auto');
        } else {
            $actionDetails = (string)$actionDetails;
        }
        $actionDetailsJson = json_encode(['details' => $actionDetails], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Format previous and new values as JSON
    $previousValueJson = !is_null($previousValue) ? json_encode(['value' => $previousValue], JSON_UNESCAPED_UNICODE) : null;
    $newValueJson = !is_null($newValue) ? json_encode(['value' => $newValue], JSON_UNESCAPED_UNICODE) : null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO match_logs 
            (match_id, user_id, action_type, action_details, previous_value, new_value, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $matchId,
            $_SESSION['user_id'],
            $actionType,
            $actionDetailsJson,
            $previousValueJson,
            $newValueJson
        ]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Failed to log action: " . ($errorInfo[2] ?? 'Unknown error'));
        }
    } catch (PDOException $e) {
        error_log("Database error in logAction: " . $e->getMessage());
    }
}

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Helper function to safely output HTML
function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Log session data
error_log("Session data in admin/manage_matches.php: " . print_r($_SESSION, true));

// Verify admin authentication before anything else
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Unauthorized access attempt to admin/manage_matches.php");
    header('Location: ../index.php');
    exit();
}

// Now initialize database connection safely
try {
    require_once '../includes/db-config.php';
    $pdo = DatabaseConfig::getConnection();
    
    // Verify database connection
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("A database connection error occurred. Please contact the administrator.");
}

// Fetch all teams for form dropdowns
try {
    $teams = $pdo->query("SELECT id, team_name AS name FROM teams ORDER BY team_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teams: " . $e->getMessage());
    die("Erreur lors du chargement des équipes.");
}

// Fetch all poules (groups) for form dropdowns
try {
    $poules = $pdo->query("SELECT id, name, competition FROM poules ORDER BY competition, name")->fetchAll(PDO::FETCH_ASSOC);
    
    // If poules table doesn't exist yet, create it
    if (empty($poules)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS poules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                competition VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Add poule_id column to matches table if it doesn't exist
        try {
            $columnExists = $pdo->query("SHOW COLUMNS FROM matches LIKE 'poule_id'")->rowCount() > 0;
            if (!$columnExists) {
                $pdo->exec("ALTER TABLE matches ADD COLUMN poule_id INT NULL");
            }
        } catch (PDOException $e) {
            error_log("Error checking or adding poule_id column: " . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching poules: " . $e->getMessage());
    // Create poules table if it doesn't exist
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS poules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                competition VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        // Add poule_id column to matches table if it doesn't exist
        $pdo->exec("ALTER TABLE matches ADD COLUMN poule_id INT NULL");
        $poules = [];
    } catch (PDOException $e2) {
        error_log("Error creating poules table: " . $e2->getMessage());
        die("Erreur lors de la création de la table des poules.");
    }
}

// Handle poule form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_poule'])) {
    try {
        $poule_name = filter_input(INPUT_POST, 'poule_name', FILTER_SANITIZE_STRING);
        $poule_competition = filter_input(INPUT_POST, 'poule_competition', FILTER_SANITIZE_STRING);
        
        if (!$poule_name || !$poule_competition) {
            $_SESSION['error'] = "Le nom de la poule et la compétition sont requis.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO poules (name, competition) VALUES (?, ?)");
            $stmt->execute([$poule_name, $poule_competition]);
            $_SESSION['message'] = "Poule ajoutée avec succès !";
        }
        
        header("Location: manage_matches.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error adding poule: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'ajout de la poule.";
        header("Location: manage_matches.php");
        exit();
    }
}

// Handle match form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Début du traitement du formulaire de match");
        
        // Add or update match
        if (isset($_POST['add_match']) || isset($_POST['update_match'])) {
            error_log("Traitement d'ajout/mise à jour de match");
            
            // Récupération des données du formulaire
            $match_id = isset($_POST['match_id']) ? filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT) : null;
            $team_home = filter_input(INPUT_POST, 'team_home', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $team_away = filter_input(INPUT_POST, 'team_away', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $competition = filter_input(INPUT_POST, 'competition', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $poule_id = filter_input(INPUT_POST, 'poule_id', FILTER_VALIDATE_INT);
            $match_date = filter_input(INPUT_POST, 'match_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $match_time = filter_input(INPUT_POST, 'match_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $phase = filter_input(INPUT_POST, 'phase', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $venue = filter_input(INPUT_POST, 'venue', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $score_home = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
            $score_away = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

            error_log("Données du formulaire:");
            error_log(print_r([
                'match_id' => $match_id,
                'team_home' => $team_home,
                'team_away' => $team_away,
                'competition' => $competition,
                'poule_id' => $poule_id,
                'match_date' => $match_date,
                'match_time' => $match_time,
                'phase' => $phase,
                'venue' => $venue,
                'score_home' => $score_home,
                'score_away' => $score_away
            ], true));

            // Validation des champs obligatoires
            if (empty($team_home) || empty($team_away) || empty($competition) || empty($match_date) || empty($match_time) || empty($phase) || empty($venue)) {
                $error_msg = "Tous les champs obligatoires doivent être remplis.";
                error_log("Erreur de validation: $error_msg");
                $_SESSION['error'] = $error_msg;
            } elseif ($team_home === $team_away) {
                $error_msg = "Les équipes à domicile et à l'extérieur doivent être différentes.";
                error_log("Erreur de validation: $error_msg");
                $_SESSION['error'] = $error_msg;
            } else {
                // Vérification de l'existence des équipes
                try {
                    $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE team_name IN (?, ?)");
                    $stmt->execute([$team_home, $team_away]);
                    $existing_teams = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    if (count($existing_teams) !== 2) {
                        $missing_teams = [];
                        if (!isset($existing_teams[$team_home])) $missing_teams[] = $team_home;
                        if (!isset($existing_teams[$team_away])) $missing_teams[] = $team_away;
                        
                        $error_msg = "Les équipes suivantes n'existent pas : " . implode(', ', $missing_teams);
                        error_log("Erreur: $error_msg");
                        $_SESSION['error'] = $error_msg;
                    } else {
                        // Déterminer le statut en fonction de la date et des scores
                        $match_datetime = strtotime($match_date . ' ' . $match_time);
                        $current_datetime = time();
                        
                        if ($score_home !== null && $score_away !== null) {
                            $status = 'completed';
                        } elseif ($match_datetime > $current_datetime) {
                            $status = 'pending';
                        } else {
                            $status = 'ongoing';
                        }
                        
                        error_log("Statut du match déterminé: $status");

                        // Ajout ou mise à jour du match
                        if (isset($_POST['add_match'])) {
                            try {
                                // Validate teams are different
                                if ($team_home === $team_away) {
                                    throw new Exception("Les équipes à domicile et à l'extérieur doivent être différentes.");
                                }

                                // Validate mandatory fields
                                $requiredFields = [
                                    'competition' => $competition,
                                    'phase' => $phase,
                                    'match_date' => $match_date,
                                    'match_time' => $match_time,
                                    'venue' => $venue
                                ];
                                
                                foreach ($requiredFields as $field => $value) {
                                    if (empty($value)) {
                                        throw new Exception("Le champ '$field' est requis pour programmer un match.");
                                    }
                                }

            // Définir les scores à 0 s'ils ne sont pas définis
            $score_home = isset($score_home) ? $score_home : 0;
            $score_away = isset($score_away) ? $score_away : 0;
            
            $sql = "INSERT INTO matches (competition, phase, match_date, match_time, team_home, team_away, venue, score_home, score_away, status, poule_id) " . 
                   "VALUES (?, ?, ?, ?, ?, ?, ?, COALESCE(?, 0), COALESCE(?, 0), ?, ?)";
                                $stmt = $pdo->prepare($sql);
                                $params = [
                                    $competition, 
                                    $phase, 
                                    $match_date, 
                                    $match_time, 
                                    $team_home, 
                                    $team_away, 
                                    $venue, 
                                    $score_home, 
                                    $score_away, 
                                    $status, 
                                    $poule_id
                                ];
                                
                                error_log("Exécution de la requête INSERT: $sql");
                                error_log("Paramètres: " . print_r($params, true));
                                
                                $result = $stmt->execute($params);
                                
                                if (!$result) {
                                    $errorInfo = $stmt->errorInfo();
                                    throw new Exception("Échec de l'ajout du match: " . ($errorInfo[2] ?? 'Erreur inconnue'));
                                }

                                $match_id = $pdo->lastInsertId();
                                $_SESSION['message'] = "Match programmé avec succès ! ID: $match_id";
                                error_log("Match programmé avec succès, ID: $match_id");
                                
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            } catch (PDOException $e) {
                                error_log("Erreur PDO lors de l'ajout du match: " . $e->getMessage());
                                error_log("Code d'erreur: " . $e->getCode());
                                $_SESSION['error'] = "Erreur lors de la programmation du match : " . $e->getMessage();
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            } catch (Exception $e) {
                                error_log("Erreur lors de la programmation du match: " . $e->getMessage());
                                $_SESSION['error'] = $e->getMessage();
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            }
                        }
                        // Gestion de la mise à jour du match
                        elseif (isset($_POST['update_match']) && $match_id) {
                            try {
                                // First check if match exists
                                $checkStmt = $pdo->prepare("SELECT id FROM matches WHERE id = ?");
                                $checkStmt->execute([$match_id]);
                                
                                if ($checkStmt->rowCount() === 0) {
                                    throw new Exception("Le match spécifié n'existe pas.");
                                }

                                // S'assurer que les scores ne sont pas NULL
                                $score_home = isset($score_home) ? $score_home : 0;
                                $score_away = isset($score_away) ? $score_away : 0;
                                
                                $sql = "UPDATE matches SET 
                                    competition = ?, 
                                    phase = ?, 
                                    match_date = ?, 
                                    match_time = ?, 
                                    team_home = ?, 
                                    team_away = ?, 
                                    venue = ?, 
                                    score_home = COALESCE(?, 0), 
                                    score_away = COALESCE(?, 0), 
                                    status = ?, 
                                    poule_id = ? 
                                    WHERE id = ?";
                                
                                $stmt = $pdo->prepare($sql);
                                $params = [
                                    $competition, 
                                    $phase, 
                                    $match_date, 
                                    $match_time, 
                                    $team_home, 
                                    $team_away, 
                                    $venue, 
                                    $score_home, 
                                    $score_away, 
                                    $status, 
                                    $poule_id,
                                    $match_id
                                ];
                                
                                error_log("Exécution de la requête UPDATE: $sql");
                                error_log("Paramètres: " . print_r($params, true));
                                
                                $result = $stmt->execute($params);
                                
                                if ($result && $stmt->rowCount() > 0) {
                                    $_SESSION['message'] = "Match mis à jour avec succès !";
                                    error_log("Match mis à jour avec succès, ID: $match_id");
                                    
                                    // Redirection pour éviter la soumission multiple du formulaire
                                    header("Location: " . $_SERVER['PHP_SELF']);
                                    exit();
                                } else {
                                    $errorInfo = $stmt->errorInfo();
                                    throw new Exception("Échec de la mise à jour du match: " . ($errorInfo[2] ?? 'Erreur inconnue'));
                                }
                            } catch (PDOException $e) {
                                error_log("Erreur PDO lors de la mise à jour du match: " . $e->getMessage());
                                error_log("Code d'erreur: " . $e->getCode());
                                throw new Exception("Erreur lors de la mise à jour du match dans la base de données");
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Erreur lors de la vérification des équipes: " . $e->getMessage());
                    $_SESSION['error'] = "Erreur lors de la vérification des équipes. Veuillez réessayer.";
                }
            }
        }
        
        // Gestion de la suppression d'un match
        if (isset($_POST['delete_match'])) {
            try {
                $match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
                
                if (!$match_id) {
                    throw new Exception("ID de match invalide.");
                }
                
                // Vérifier si le match existe
                $stmt = $pdo->prepare("SELECT id FROM matches WHERE id = ?");
                $stmt->execute([$match_id]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Le match spécifié n'existe pas.");
                }
                
            // First get match details to see if it was completed
            $stmt = $pdo->prepare("SELECT status, competition, poule_id FROM matches WHERE id = ?");
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to log actions in the audit log
function logAction($pdo, $matchId, $actionType, $actionDetails = null, $previousValue = null, $newValue = null) {
    // Validate inputs
    if (!is_numeric($matchId) || !is_string($actionType) || !in_array($actionType, [
        'UPDATE_SCORE', 'ADD_GOAL', 'ADD_CARD', 'START_FIRST_HALF', 'END_FIRST_HALF', 
        'START_SECOND_HALF', 'END_MATCH', 'SET_EXTRA_TIME', 'FINALIZE_MATCH', 
        'SET_MATCH_DURATION', 'UPDATE_STANDING', 'CREATE_STANDING', 'DELETE_MATCH'
    ])) {
        error_log("Invalid logAction inputs: matchId=$matchId, actionType=$actionType");
        return;
    }
    
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        error_log("Invalid user_id in logAction: " . print_r($_SESSION, true));
        return;
    }

    // Format action_details as JSON
    $actionDetailsJson = null;
    if (!is_null($actionDetails)) {
        if (function_exists('mb_convert_encoding')) {
            $actionDetails = mb_convert_encoding((string)$actionDetails, 'UTF-8', 'auto');
        } else {
            $actionDetails = (string)$actionDetails;
        }
        $actionDetailsJson = json_encode(['details' => $actionDetails], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Format previous and new values as JSON
    $previousValueJson = !is_null($previousValue) ? json_encode(['value' => $previousValue], JSON_UNESCAPED_UNICODE) : null;
    $newValueJson = !is_null($newValue) ? json_encode(['value' => $newValue], JSON_UNESCAPED_UNICODE) : null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO match_logs 
            (match_id, user_id, action_type, action_details, previous_value, new_value, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $matchId,
            $_SESSION['user_id'],
            $actionType,
            $actionDetailsJson,
            $previousValueJson,
            $newValueJson
        ]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Failed to log action: " . ($errorInfo[2] ?? 'Unknown error'));
        }
    } catch (PDOException $e) {
        error_log("Database error in logAction: " . $e->getMessage());
    }
}

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Helper function to safely output HTML
function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Log session data
error_log("Session data in admin/manage_matches.php: " . print_r($_SESSION, true));

// Verify admin authentication before anything else
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Unauthorized access attempt to admin/manage_matches.php");
    header('Location: ../index.php');
    exit();
}

// Now initialize database connection safely
try {
    require_once '../includes/db-config.php';
    $pdo = DatabaseConfig::getConnection();
    
    // Verify database connection
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("A database connection error occurred. Please contact the administrator.");
}

// Fetch all teams for form dropdowns
try {
    $teams = $pdo->query("SELECT id, team_name AS name FROM teams ORDER BY team_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teams: " . $e->getMessage());
    die("Erreur lors du chargement des équipes.");
}

// Fetch all poules (groups) for form dropdowns
try {
    $poules = $pdo->query("SELECT id, name, competition FROM poules ORDER BY competition, name")->fetchAll(PDO::FETCH_ASSOC);
    
    // If poules table doesn't exist yet, create it
    if (empty($poules)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS poules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                competition VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Add poule_id column to matches table if it doesn't exist
        try {
            $columnExists = $pdo->query("SHOW COLUMNS FROM matches LIKE 'poule_id'")->rowCount() > 0;
            if (!$columnExists) {
                $pdo->exec("ALTER TABLE matches ADD COLUMN poule_id INT NULL");
            }
        } catch (PDOException $e) {
            error_log("Error checking or adding poule_id column: " . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching poules: " . $e->getMessage());
    // Create poules table if it doesn't exist
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS poules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                competition VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        // Add poule_id column to matches table if it doesn't exist
        $pdo->exec("ALTER TABLE matches ADD COLUMN poule_id INT NULL");
        $poules = [];
    } catch (PDOException $e2) {
        error_log("Error creating poules table: " . $e2->getMessage());
        die("Erreur lors de la création de la table des poules.");
    }
}

// Handle poule form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_poule'])) {
    try {
        $poule_name = filter_input(INPUT_POST, 'poule_name', FILTER_SANITIZE_STRING);
        $poule_competition = filter_input(INPUT_POST, 'poule_competition', FILTER_SANITIZE_STRING);
        
        if (!$poule_name || !$poule_competition) {
            $_SESSION['error'] = "Le nom de la poule et la compétition sont requis.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO poules (name, competition) VALUES (?, ?)");
            $stmt->execute([$poule_name, $poule_competition]);
            $_SESSION['message'] = "Poule ajoutée avec succès !";
        }
        
        header("Location: manage_matches.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error adding poule: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'ajout de la poule.";
        header("Location: manage_matches.php");
        exit();
    }
}

// Handle match form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Début du traitement du formulaire de match");
        
        // Add or update match
        if (isset($_POST['add_match']) || isset($_POST['update_match'])) {
            error_log("Traitement d'ajout/mise à jour de match");
            
            // Récupération des données du formulaire
            $match_id = isset($_POST['match_id']) ? filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT) : null;
            $team_home = filter_input(INPUT_POST, 'team_home', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $team_away = filter_input(INPUT_POST, 'team_away', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $competition = filter_input(INPUT_POST, 'competition', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $poule_id = filter_input(INPUT_POST, 'poule_id', FILTER_VALIDATE_INT);
            $match_date = filter_input(INPUT_POST, 'match_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $match_time = filter_input(INPUT_POST, 'match_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $phase = filter_input(INPUT_POST, 'phase', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $venue = filter_input(INPUT_POST, 'venue', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $score_home = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
            $score_away = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

            error_log("Données du formulaire:");
            error_log(print_r([
                'match_id' => $match_id,
                'team_home' => $team_home,
                'team_away' => $team_away,
                'competition' => $competition,
                'poule_id' => $poule_id,
                'match_date' => $match_date,
                'match_time' => $match_time,
                'phase' => $phase,
                'venue' => $venue,
                'score_home' => $score_home,
                'score_away' => $score_away
            ], true));

            // Validation des champs obligatoires
            if (empty($team_home) || empty($team_away) || empty($competition) || empty($match_date) || empty($match_time) || empty($phase) || empty($venue)) {
                $error_msg = "Tous les champs obligatoires doivent être remplis.";
                error_log("Erreur de validation: $error_msg");
                $_SESSION['error'] = $error_msg;
            } elseif ($team_home === $team_away) {
                $error_msg = "Les équipes à domicile et à l'extérieur doivent être différentes.";
                error_log("Erreur de validation: $error_msg");
                $_SESSION['error'] = $error_msg;
            } else {
                // Vérification de l'existence des équipes
                try {
                    $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE team_name IN (?, ?)");
                    $stmt->execute([$team_home, $team_away]);
                    $existing_teams = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    if (count($existing_teams) !== 2) {
                        $missing_teams = [];
                        if (!isset($existing_teams[$team_home])) $missing_teams[] = $team_home;
                        if (!isset($existing_teams[$team_away])) $missing_teams[] = $team_away;
                        
                        $error_msg = "Les équipes suivantes n'existent pas : " . implode(', ', $missing_teams);
                        error_log("Erreur: $error_msg");
                        $_SESSION['error'] = $error_msg;
                    } else {
                        // Déterminer le statut en fonction de la date et des scores
                        $match_datetime = strtotime($match_date . ' ' . $match_time);
                        $current_datetime = time();
                        
                        if ($score_home !== null && $score_away !== null) {
                            $status = 'completed';
                        } elseif ($match_datetime > $current_datetime) {
                            $status = 'pending';
                        } else {
                            $status = 'ongoing';
                        }
                        
                        error_log("Statut du match déterminé: $status");

                        // Ajout ou mise à jour du match
                        if (isset($_POST['add_match'])) {
                            try {
                                // Validate teams are different
                                if ($team_home === $team_away) {
                                    throw new Exception("Les équipes à domicile et à l'extérieur doivent être différentes.");
                                }

                                // Validate mandatory fields
                                $requiredFields = [
                                    'competition' => $competition,
                                    'phase' => $phase,
                                    'match_date' => $match_date,
                                    'match_time' => $match_time,
                                    'venue' => $venue
                                ];
                                
                                foreach ($requiredFields as $field => $value) {
                                    if (empty($value)) {
                                        throw new Exception("Le champ '$field' est requis pour programmer un match.");
                                    }
                                }

            // Définir les scores à 0 s'ils ne sont pas définis
            $score_home = isset($score_home) ? $score_home : 0;
            $score_away = isset($score_away) ? $score_away : 0;
            
            $sql = "INSERT INTO matches (competition, phase, match_date, match_time, team_home, team_away, venue, score_home, score_away, status, poule_id) " . 
                   "VALUES (?, ?, ?, ?, ?, ?, ?, COALESCE(?, 0), COALESCE(?, 0), ?, ?)";
                                $stmt = $pdo->prepare($sql);
                                $params = [
                                    $competition, 
                                    $phase, 
                                    $match_date, 
                                    $match_time, 
                                    $team_home, 
                                    $team_away, 
                                    $venue, 
                                    $score_home, 
                                    $score_away, 
                                    $status, 
                                    $poule_id
                                ];
                                
                                error_log("Exécution de la requête INSERT: $sql");
                                error_log("Paramètres: " . print_r($params, true));
                                
                                $result = $stmt->execute($params);
                                
                                if (!$result) {
                                    $errorInfo = $stmt->errorInfo();
                                    throw new Exception("Échec de l'ajout du match: " . ($errorInfo[2] ?? 'Erreur inconnue'));
                                }

                                $match_id = $pdo->lastInsertId();
                                $_SESSION['message'] = "Match programmé avec succès ! ID: $match_id";
                                error_log("Match programmé avec succès, ID: $match_id");
                                
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            } catch (PDOException $e) {
                                error_log("Erreur PDO lors de l'ajout du match: " . $e->getMessage());
                                error_log("Code d'erreur: " . $e->getCode());
                                $_SESSION['error'] = "Erreur lors de la programmation du match : " . $e->getMessage();
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            } catch (Exception $e) {
                                error_log("Erreur lors de la programmation du match: " . $e->getMessage());
                                $_SESSION['error'] = $e->getMessage();
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            }
                        }
                        // Gestion de la mise à jour du match
                        elseif (isset($_POST['update_match']) && $match_id) {
                            try {
                                // First check if match exists
                                $checkStmt = $pdo->prepare("SELECT id FROM matches WHERE id = ?");
                                $checkStmt->execute([$match_id]);
                                
                                if ($checkStmt->rowCount() === 0) {
                                    throw new Exception("Le match spécifié n'existe pas.");
                                }

                                // S'assurer que les scores ne sont pas NULL
                                $score_home = isset($score_home) ? $score_home : 0;
                                $score_away = isset($score_away) ? $score_away : 0;
                                
                                $sql = "UPDATE matches SET 
                                    competition = ?, 
                                    phase = ?, 
                                    match_date = ?, 
                                    match_time = ?, 
                                    team_home = ?, 
                                    team_away = ?, 
                                    venue = ?, 
                                    score_home = COALESCE(?, 0), 
                                    score_away = COALESCE(?, 0), 
                                    status = ?, 
                                    poule_id = ? 
                                    WHERE id = ?";
                                
                                $stmt = $pdo->prepare($sql);
                                $params = [
                                    $competition, 
                                    $phase, 
                                    $match_date, 
                                    $match_time, 
                                    $team_home, 
                                    $team_away, 
                                    $venue, 
                                    $score_home, 
                                    $score_away, 
                                    $status, 
                                    $poule_id,
                                    $match_id
                                ];
                                
                                error_log("Exécution de la requête UPDATE: $sql");
                                error_log("Paramètres: " . print_r($params, true));
                                
                                $result = $stmt->execute($params);
                                
                                if ($result && $stmt->rowCount() > 0) {
                                    $_SESSION['message'] = "Match mis à jour avec succès !";
                                    error_log("Match mis à jour avec succès, ID: $match_id");
                                    
                                    // Redirection pour éviter la soumission multiple du formulaire
                                    header("Location: " . $_SERVER['PHP_SELF']);
                                    exit();
                                } else {
                                    $errorInfo = $stmt->errorInfo();
                                    throw new Exception("Échec de la mise à jour du match: " . ($errorInfo[2] ?? 'Erreur inconnue'));
                                }
                            } catch (PDOException $e) {
                                error_log("Erreur PDO lors de la mise à jour du match: " . $e->getMessage());
                                error_log("Code d'erreur: " . $e->getCode());
                                throw new Exception("Erreur lors de la mise à jour du match dans la base de données");
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Erreur lors de la vérification des équipes: " . $e->getMessage());
                    $_SESSION['error'] = "Erreur lors de la vérification des équipes. Veuillez réessayer.";
                }
            }
        }
        
        // Gestion de la suppression d'un match
        if (isset($_POST['delete_match'])) {
            try {
                $match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
                
                if (!$match_id) {
                    throw new Exception("ID de match invalide.");
                }
                
                // Vérifier si le match existe
                $stmt = $pdo->prepare("SELECT id FROM matches WHERE id = ?");
                $stmt->execute([$match_id]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Le match spécifié n'existe pas.");
                }
                
                // Récupérer les détails du match avant suppression pour mettre à jour le classement
                $matchStmt = $pdo->prepare("SELECT m.*, p.name as poule_name, p.competition 
                                         FROM matches m 
                                         LEFT JOIN poules p ON m.poule_id = p.id 
                                         WHERE m.id = ?");
                $matchStmt->execute([$match_id]);
                $match = $matchStmt->fetch(PDO::FETCH_ASSOC);
                
                // Démarrer une transaction pour garantir l'intégrité des données
                $pdo->beginTransaction();
                
                try {
                    // Si c'est un match terminé, mettre à jour le classement
                    if ($match && $match['status'] === 'completed') {
                        // Inverser les scores pour soustraire les statistiques
                        $temp = $match['score_home'];
                        $match['score_home'] = $match['score_away'];
                        $match['score_away'] = $temp;
                        
                        // Mettre à jour le classement avec des valeurs négatives pour soustraire
                        $season = date('Y') . '-' . (date('Y') + 1);
                        
                        // Mettre à jour les statistiques de l'équipe à domicile
                        $points = ($match['score_away'] > $match['score_home']) ? 3 : 
                                 ($match['score_away'] == $match['score_home'] ? 1 : 0);
                        
                        $stmt = $pdo->prepare("
                            UPDATE classement 
                            SET matchs_joues = GREATEST(0, matchs_joues - 1),
                                matchs_gagnes = GREATEST(0, matchs_gagnes - ?),
                                matchs_nuls = GREATEST(0, matchs_nuls - ?),
                                matchs_perdus = GREATEST(0, matchs_perdus - ?),
                                buts_pour = GREATEST(0, buts_pour - ?),
                                buts_contre = GREATEST(0, buts_contre - ?),
                                difference_buts = buts_pour - buts_contre,
                                points = GREATEST(0, points - ?),
                                forme = SUBSTRING(forme, 1, 4)  /* Supprimer le dernier résultat de la forme */
                            WHERE saison = ? 
                            AND competition = ? 
                            AND poule_id = ? 
                            AND nom_equipe = ?
                        ");
                        $stmt->execute([
                            $points == 3 ? 1 : 0,  // matchs_gagnes
                            $points == 1 ? 1 : 0,  // matchs_nuls
                            $points == 0 ? 1 : 0,  // matchs_perdus
                            $match['score_away'],  // buts_pour
                            $match['score_home'],  // buts_contre
                            $points,               // points
                            $season,
                            $match['competition'],
                            $match['poule_id'],
                            $match['team_away']
                        ]);
                        
                        // Mettre à jour les statistiques de l'équipe à l'extérieur
                        $points = ($match['score_home'] > $match['score_away']) ? 3 : 
                                 ($match['score_home'] == $match['score_away'] ? 1 : 0);
                        
                        $stmt->execute([
                            $points == 3 ? 1 : 0,  // matchs_gagnes
                            $points == 1 ? 1 : 0,  // matchs_nuls
                            $points == 0 ? 1 : 0,  // matchs_perdus
                            $match['score_home'],  // buts_pour
                            $match['score_away'],  // buts_contre
                            $points,               // points
                            $season,
                            $match['competition'],
                            $match['poule_id'],
                            $match['team_home']
                        ]);
                    }
                    
                    // Maintenant supprimer le match
                    $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
                    $result = $stmt->execute([$match_id]);
                    
                    if ($result) {
                        // Valider la transaction
                        $pdo->commit();
                        
                        // Journaliser l'action
                        logAction(
                            $pdo,
                            $match_id,
                            'DELETE_MATCH',
                            "Match supprimé",
                            json_encode($match),
                            null
                        );
                        
                        $_SESSION['message'] = "Le match a été supprimé avec succès et le classement a été mis à jour.";
                } else {
                    throw new Exception("Échec de la suppression du match.");
                }
                
            } catch (Exception $e) {
                error_log("Erreur lors de la suppression du match: " . $e->getMessage());
                $_SESSION['error'] = "Erreur lors de la suppression du match: " . $e->getMessage();
            }
            
            header("Location: manage_matches.php");
            exit();
        }
        
        // Gestion de la finalisation du match
        if (isset($_POST['finalize_match'])) {
            try {
                $match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
                $score_home = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT);
                $score_away = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT);
                
                error_log("Finalisation du match ID: $match_id, Score: $score_home - $score_away");
                
                if (!$match_id || $score_home === null || $score_away === null) {
                    $error_msg = "Veuillez entrer des scores valides pour finaliser le match.";
                    error_log("Erreur de validation: $error_msg");
                    $_SESSION['error'] = $error_msg;
                } else {
                            // Start transaction for match finalization and ranking updates
                            $pdo->beginTransaction();
                            
                            try {
                                // Finalize the match
                                $stmt = $pdo->prepare("
                                    UPDATE matches 
                                    SET status = 'completed', 
                                        score_home = ?,
                                        score_away = ?,
                                        updated_at = NOW()
                                    WHERE id = ?
                                ");
                                
                                $result = $stmt->execute([$score_home, $score_away, $match_id]);
                                
                                if ($result) {
                                    // Get match details for ranking updates
                                    $matchStmt = $pdo->prepare("
                                        SELECT m.*, p.name as poule_name, p.competition 
                                        FROM matches m
                                        LEFT JOIN poules p ON m.poule_id = p.id
                                        WHERE m.id = ?
                                    ");
                                    $matchStmt->execute([$match_id]);
                                    $match = $matchStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($match) {
                                        // Update rankings for both teams
                                        $season = date('Y') . '-' . (date('Y') + 1); // Current season format
                                        
                                        // Update home team stats
                                        $points = ($score_home > $score_away) ? 3 : ($score_home == $score_away ? 1 : 0);
                                        $form = ($score_home > $score_away) ? 'V' : ($score_home == $score_away ? 'N' : 'D');
                                        $stmt = $pdo->prepare("
                                            INSERT INTO classement (
                                                saison, competition, poule_id, nom_equipe, 
                                                matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus, 
                                                buts_pour, buts_contre, difference_buts, points, forme
                                            ) 
                                            VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
                                            ON DUPLICATE KEY UPDATE
                                                matchs_joues = matchs_joues + 1,
                                                matchs_gagnes = matchs_gagnes + ?,
                                                matchs_nuls = matchs_nuls + ?,
                                                matchs_perdus = matchs_perdus + ?,
                                                buts_pour = buts_pour + ?,
                                                buts_contre = buts_contre + ?,
                                                difference_buts = difference_buts + ?,
                                                points = points + ?,
                                                forme = CONCAT(SUBSTRING(forme, 2, 4), ?)
                                        ");
                                        $stmt->execute([
                                            $season, $match['competition'], $match['poule_id'], $match['team_home'],
                                            ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
                                            $score_home, $score_away, ($score_home - $score_away), $points, $form,
                                            ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
                                            $score_home, $score_away, ($score_home - $score_away), $points, $form
                                        ]);
                                        
                                        // Update away team stats
                                        $points = ($score_away > $score_home) ? 3 : ($score_away == $score_home ? 1 : 0);
                                        $form = ($score_away > $score_home) ? 'V' : ($score_away == $score_home ? 'N' : 'D');
                                        $stmt->execute([
                                            $season, $match['competition'], $match['poule_id'], $match['team_away'],
                                            ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
                                            $score_away, $score_home, ($score_away - $score_home), $points, $form,
                                            ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
                                            $score_away, $score_home, ($score_away - $score_home), $points, $form
                                        ]);
                                        
                                        $_SESSION['message'] = "Match finalisé et classement mis à jour avec succès !";
                                        error_log("Match finalisé et classement mis à jour, ID: $match_id");
                                        $pdo->commit();
                        
                                        // Redirection pour éviter la soumission multiple du formulaire
                                        header("Location: " . $_SERVER['PHP_SELF']);
                                        exit();
                                    } else {
                                        throw new Exception("Impossible de récupérer les détails du match");
                                    }
                                } else {
                                    $errorInfo = $stmt->errorInfo();
                                    throw new Exception("Échec de la finalisation du match: " . ($errorInfo[2] ?? 'Erreur inconnue'));
                                }
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                error_log("Error during match finalization: " . $e->getMessage());
                                $_SESSION['error'] = $e->getMessage();
                    }
                }
            } catch (PDOException $e) {
                error_log("Erreur PDO lors de la finalisation du match: " . $e->getMessage());
                error_log("Code d'erreur: " . $e->getCode());
                $_SESSION['error'] = "Erreur lors de la finalisation du match. Veuillez réessayer.";
            } catch (Exception $e) {
                error_log("Erreur lors de la finalisation du match: " . $e->getMessage());
                $_SESSION['error'] = $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        error_log("Erreur lors du traitement du formulaire de match: " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer.";
        
        // Enregistrer plus de détails dans les logs
        error_log("Trace complète de l'erreur: " . $e->getTraceAsString());
        
        // Si c'est une erreur PDO, enregistrer les informations supplémentaires
        if ($e instanceof PDOException) {
            error_log("Code d'erreur PDO: " . $e->getCode());
            error_log("Informations sur l'erreur: " . print_r($e->errorInfo, true));
        }
    }
}

// Function to update classement for a completed match
function updateClassementForMatch($pdo, $match) {
    $season = date('Y') . '-' . (date('Y') + 1);
    
    // Update home team stats
    $points = ($match['score_home'] > $match['score_away']) ? 3 : 
             ($match['score_home'] == $match['score_away'] ? 1 : 0);
    $form = ($points == 3) ? 'V' : ($points == 1 ? 'N' : 'D');
    
    $stmt = $pdo->prepare("
        INSERT INTO classement (
            saison, competition, poule_id, nom_equipe, 
            matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus, 
            buts_pour, buts_contre, difference_buts, points, forme
        ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            matchs_joues = matchs_joues + 1,
            matchs_gagnes = matchs_gagnes + ?,
            matchs_nuls = matchs_nuls + ?,
            matchs_perdus = matchs_perdus + ?,
            buts_pour = buts_pour + ?,
            buts_contre = buts_contre + ?,
            difference_buts = difference_buts + ?,
            points = points + ?,
            forme = CONCAT(SUBSTRING(forme, 2, 4), ?)
    ");
    $stmt->execute([
        $season, $match['competition'], $match['poule_id'], $match['team_home'],
        ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
        $match['score_home'], $match['score_away'], 
        ($match['score_home'] - $match['score_away']), $points, $form,
        ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
        $match['score_home'], $match['score_away'], 
        ($match['score_home'] - $match['score_away']), $points, $form
    ]);
    
    // Update away team stats
    $points = ($match['score_away'] > $match['score_home']) ? 3 : 
             ($match['score_away'] == $match['score_home'] ? 1 : 0);
    $form = ($points == 3) ? 'V' : ($points == 1 ? 'N' : 'D');
    
    $stmt->execute([
        $season, $match['competition'], $match['poule_id'], $match['team_away'],
        ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
        $match['score_away'], $match['score_home'], 
        ($match['score_away'] - $match['score_home']), $points, $form,
        ($points == 3 ? 1 : 0), ($points == 1 ? 1 : 0), ($points == 0 ? 1 : 0),
        $match['score_away'], $match['score_home'], 
        ($match['score_away'] - $match['score_home']), $points, $form
    ]);
}

// Process existing completed matches on initial load
try {
    // Récupérer tous les matchs terminés qui n'ont pas encore été traités dans le classement
    $completedMatches = $pdo->query("
        SELECT DISTINCT m.*, p.name as poule_name, p.competition 
        FROM matches m
        LEFT JOIN poules p ON m.poule_id = p.id
        WHERE m.status = 'completed'
        AND (m.poule_id IS NOT NULL AND m.poule_id != '')  // S'assurer que le match a une poule
        AND (
            /* Vérifier si au moins une des équipes n'a pas d'entrée dans le classement */
            NOT EXISTS (
                SELECT 1 FROM classement c 
                WHERE c.nom_equipe = m.team_home
                AND c.saison = CONCAT(YEAR(m.match_date), '-', YEAR(m.match_date) + 1)
                AND c.competition = m.competition
                AND c.poule_id = m.poule_id
            )
            OR
            NOT EXISTS (
                SELECT 1 FROM classement c 
                WHERE c.nom_equipe = m.team_away
                AND c.saison = CONCAT(YEAR(m.match_date), '-', YEAR(m.match_date) + 1)
                AND c.competition = m.competition
                AND c.poule_id = m.poule_id
            )
            /* Ou si le match n'a pas encore été traité */
            OR NOT EXISTS (
                SELECT 1 FROM classement c 
                WHERE c.nom_equipe IN (m.team_home, m.team_away)
                AND c.saison = CONCAT(YEAR(m.match_date), '-', YEAR(m.match_date) + 1)
                AND c.competition = m.competition
                AND c.poule_id = m.poule_id
                AND c.dernier_match_traite >= m.match_date
            )
        )
        ORDER BY m.match_date, m.match_time
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Mettre à jour le classement pour chaque match
    foreach ($completedMatches as $match) {
        try {
            updateClassementForMatch($pdo, $match);
            
            // Marquer le match comme traité dans le classement
            $season = date('Y', strtotime($match['match_date'])) . '-' . (date('Y', strtotime($match['match_date'])) + 1);
            $dateNow = date('Y-m-d H:i:s');
            
            // Pour l'équipe à domicile
            $stmt = $pdo->prepare("
                UPDATE classement 
                SET dernier_match_traite = ? 
                WHERE nom_equipe = ? 
                AND saison = ? 
                AND competition = ? 
                AND poule_id = ?
            ");
            $stmt->execute([$dateNow, $match['team_home'], $season, $match['competition'], $match['poule_id']]);
            
            // Pour l'équipe à l'extérieur
            $stmt->execute([$dateNow, $match['team_away'], $season, $match['competition'], $match['poule_id']]);
            
        } catch (Exception $e) {
            error_log("Erreur lors du traitement du match ID {$match['id']}: " . $e->getMessage());
            continue;  // Continuer avec le match suivant en cas d'erreur
        }
} catch (PDOException $e) {
    error_log("Error backfilling completed matches: " . $e->getMessage());
}

// Fetch all matches with poule information
try {
    $matches = $pdo->query("
        SELECT m.id, m.team_home, m.team_away, m.match_date, m.match_time, m.phase, m.venue, m.status, 
               m.competition, m.score_home, m.score_away, m.poule_id, p.name as poule_name
        FROM matches m
        LEFT JOIN poules p ON m.poule_id = p.id
        ORDER BY m.match_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching matches: " . $e->getMessage());
    die("Erreur lors du chargement des matchs.");
}

// Reload poules after any changes
try {
    $poules = $pdo->query("SELECT id, name, competition FROM poules ORDER BY competition, name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error reloading poules: " . $e->getMessage());
    $poules = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matchs - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
                    
                    const homeTeam = match.team_home;
                    const awayTeam = match.team_away;
                    const homeScore = parseInt(match.score_home);
                    const awayScore = parseInt(match.score_away);
                    
                    // Update home team stats
                    teamStats[homeTeam].played++;
                    teamStats[homeTeam].goalsFor += homeScore;
                    teamStats[homeTeam].goalsAgainst += awayScore;
                    
                    // Update away team stats
                    teamStats[awayTeam].played++;
                    teamStats[awayTeam].goalsFor += awayScore;
                    teamStats[awayTeam].goalsAgainst += homeScore;
                    
                    if (homeScore > awayScore) {
                        // Home team won
                        teamStats[homeTeam].won++;
                        teamStats[homeTeam].points += 3;
                        teamStats[awayTeam].lost++;
                    } else if (homeScore < awayScore) {
                        // Away team won
                        teamStats[awayTeam].won++;
                        teamStats[awayTeam].points += 3;
                        teamStats[homeTeam].lost++;
                    } else {
                        // Draw
                        teamStats[homeTeam].drawn++;
                        teamStats[homeTeam].points += 1;
                        teamStats[awayTeam].drawn++;
                        teamStats[awayTeam].points += 1;
                    }
                });
                
                // Convert to array and sort by points, then goal difference
                const sortedTeams = Object.values(teamStats).sort((a, b) => {
                    if (a.points !== b.points) {
                        return b.points - a.points;
                    }
                    const aDiff = a.goalsFor - a.goalsAgainst;
                    const bDiff = b.goalsFor - b.goalsAgainst;
                    if (aDiff !== bDiff) {
                        return bDiff - aDiff;
                    }
                    return b.goalsFor - a.goalsFor;
                });
                
                // Create the standings table
                const standingsContainer = document.createElement('div');
                standingsContainer.className = 'card mt-4';
                standingsContainer.dataset.poulesStandings = poule.id;
                standingsContainer.style.display = 'none'; // Hide by default
                
                const standingsHeader = document.createElement('div');
                standingsHeader.className = 'card-header bg-success text-white';
                standingsHeader.innerHTML = `<h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Classement - ${poule.name} (${poule.competition})</h5>`;
                
                const standingsBody = document.createElement('div');
                standingsBody.className = 'card-body';
                
                const standingsTable = document.createElement('table');
                standingsTable.className = 'table table-striped table-hover';
                standingsTable.innerHTML = `
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Équipe</th>
                            <th>J</th>
                            <th>G</th>
                            <th>N</th>
                            <th>P</th>
                            <th>BP</th>
                            <th>BC</th>
                            <th>Diff</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sortedTeams.map((team, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${team.team}</td>
                                <td>${team.played}</td>
                                <td>${team.won}</td>
                                <td>${team.drawn}</td>
                                <td>${team.lost}</td>
                                <td>${team.goalsFor}</td>
                                <td>${team.goalsAgainst}</td>
                                <td>${team.goalsFor - team.goalsAgainst}</td>
                                <td><strong>${team.points}</strong></td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                
                standingsBody.appendChild(standingsTable);
                standingsContainer.appendChild(standingsHeader);
                standingsContainer.appendChild(standingsBody);
                
                document.getElementById('poules').appendChild(standingsContainer);
            });
            
            // Create a dropdown to select which standings to display
            const selectContainer = document.createElement('div');
            selectContainer.className = 'row mb-4';
            selectContainer.innerHTML = `
                <div class="col-md-6">
                    <label for="view_standings" class="form-label">Afficher le classement:</label>
                    <select id="view_standings" class="form-select">
                        <option value="">-- Sélectionner une poule --</option>
                        ${poules.map(poule => `
                            <option value="${poule.id}">${poule.competition} - ${poule.name}</option>
                        `).join('')}
                    </select>
                </div>
            `;
            
            // Insert after the "Add Poule" card
            const addPouleCard = document.querySelector('#poules .card');
            addPouleCard.parentNode.insertBefore(selectContainer, addPouleCard.nextSibling);
            
            // Add event listener to dropdown
            document.getElementById('view_standings').addEventListener('change', function() {
                const poulesId = this.value;
                const standingsTables = document.querySelectorAll('[data-poules-standings]');
                
                standingsTables.forEach(table => {
                    if (table.dataset.poulesStandings === poulesId) {
                        table.style.display = 'block';
                    } else {
                        table.style.display = 'none';
                    }
                });
            });
        }
        
        // Call generateStandings when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Only generate standings if there are poules
            const poules = <?= json_encode($poules) ?>;
            if (poules && poules.length > 0) {
                // Generate standings tables
                if (document.querySelectorAll('#poules .card').length > 0) {
                    generateStandings();
                }
            }
        });
    </script>
</body>
</html>
