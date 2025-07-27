<?php
require __DIR__ . '/admin_header.php';

// Récupérer les matchs en cours (en attente ou en cours)
try {
    $currentMatches = $pdo->query("
        SELECT m.*, 
               t1.logo as home_logo, 
               t2.logo as away_logo
        FROM matches m
        LEFT JOIN teams t1 ON m.team_home = t1.team_name
        LEFT JOIN teams t2 ON m.team_away = t2.team_name
        WHERE m.status IN ('ongoing', 'pending')
        ORDER BY m.match_date ASC, m.match_time ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des matchs en cours: " . $e->getMessage());
    $currentMatches = [];
    $_SESSION['error'] = "Erreur lors du chargement des matchs en cours. Veuillez réessayer.";
}

// Function to log actions in the audit log
function logAction($pdo, $matchId, $actionType, $actionDetails = null, $previousValue = null, $newValue = null) {
    // Validate inputs
    if (!is_numeric($matchId) || !is_string($actionType) || !in_array($actionType, [
        'UPDATE_SCORE', 'ADD_GOAL', 'ADD_CARD', 'START_FIRST_HALF', 'END_FIRST_HALF', 
        'START_SECOND_HALF', 'END_MATCH', 'SET_EXTRA_TIME', 'FINALIZE_MATCH', 
        'SET_MATCH_DURATION', 'UPDATE_STANDING', 'CREATE_STANDING'
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
        // Ensure UTF-8 encoding while handling mbstring not available
        if (function_exists('mb_convert_encoding')) {
            $actionDetails = mb_convert_encoding((string)$actionDetails, 'UTF-8', 'auto');
        } else {
            // Fallback if mbstring not available
            $actionDetails = (string)$actionDetails;
            if (!preg_match('//u', $actionDetails)) {
                $actionDetails = utf8_encode($actionDetails);
            }
        }
        // Wrap in a JSON object
        $actionDetailsJson = json_encode(['message' => $actionDetails], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
        if ($actionDetailsJson === false) {
            error_log("Failed to encode action_details to JSON: " . json_last_error_msg());
            $actionDetailsJson = json_encode(['message' => 'Invalid details']);
        }
    }

    $maxTextLength = 65535; // TEXT column limit
    $previousValueJson = $previousValue ? substr(json_encode($previousValue, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE), 0, $maxTextLength) : null;
    $newValueJson = $newValue ? substr(json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE), 0, $maxTextLength) : null;

    // Log inputs for debugging
    error_log("logAction: matchId=$matchId, user_id={$_SESSION['user_id']}, actionType=$actionType, " .
              "actionDetails=" . ($actionDetailsJson ?? 'null') . 
              ", previousValue=" . ($previousValueJson ?? 'null') . 
              ", newValue=" . ($newValueJson ?? 'null'));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO match_logs 
            (match_id, user_id, action_type, action_details, previous_value, new_value, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $matchId,
            $_SESSION['user_id'],
            $actionType,
            $actionDetailsJson,
            $previousValueJson,
            $newValueJson
        ]);
    } catch (PDOException $e) {
        error_log("logAction failed: matchId=$matchId, actionType=$actionType, actionDetails=" . ($actionDetailsJson ?? 'null') . ", error: " . $e->getMessage());
        throw $e;
    }
}

// Le reste du code reste inchangé...

function updateStandings($matchId, $pdo) {
    try {
        // Fetch the match
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$match) {
            error_log("updateStandings: Match not found for match_id=$matchId");
            return;
        }

        // Validate required fields
        if (empty($match['saison']) || empty($match['competition']) || !isset($match['poule_id']) || 
            !isset($match['score_home']) || !isset($match['score_away']) || $match['status'] !== 'completed') {
            error_log("updateStandings: Invalid match data for match_id=$matchId, data=" . json_encode($match));
            return;
        }

        $saison = $match['saison'];
        $competition = $match['competition'];
        $poule_id = $match['poule_id'];
        $teams = [
            ['name' => $match['team_home'], 'is_home' => true],
            ['name' => $match['team_away'], 'is_home' => false]
        ];

        foreach ($teams as $team) {
            $nom_equipe = $team['name'];

            // Fetch all completed matches for the team
            $sql = "SELECT * FROM matches 
                    WHERE (team_home = :team OR team_away = :team)
                    AND saison = :saison AND competition = :competition AND poule_id = :poule_id
                    AND status = 'completed'";
            $stmt2 = $pdo->prepare($sql);
            $stmt2->execute([
                'team' => $nom_equipe,
                'saison' => $saison,
                'competition' => $competition,
                'poule_id' => $poule_id
            ]);
            $matches = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            error_log("updateStandings: Fetched " . count($matches) . " matches for team=$nom_equipe, match_id=$matchId");

            // Calculate standings
            $joues = $gagnes = $nuls = $perdus = $bp = $bc = $points = 0;
            $forme = [];

            foreach ($matches as $m) {
                $isHome = ($m['team_home'] === $nom_equipe);
                $for = $isHome ? $m['score_home'] : $m['score_away'];
                $against = $isHome ? $m['score_away'] : $m['score_home'];

                // Skip if scores are invalid
                if ($for === null || $against === null) {
                    error_log("updateStandings: Skipping match_id={$m['id']} for team=$nom_equipe due to null scores");
                    continue;
                }

                $joues++;
                $bp += $for;
                $bc += $against;

                if ($for > $against) {
                    $gagnes++;
                    $points += 3;
                    $forme[] = 'V';
                } elseif ($for == $against) {
                    $nuls++;
                    $points += 1;
                    $forme[] = 'N';
                } else {
                    $perdus++;
                    $forme[] = 'D';
                }
            }
            $diff = $bp - $bc;
            $formeStr = implode(',', array_slice(array_reverse($forme), 0, 5));

            // Fetch existing standing
            $oldStandingStmt = $pdo->prepare("SELECT * FROM classement WHERE saison = ? AND competition = ? AND poule_id = ? AND nom_equipe = ?");
            $oldStandingStmt->execute([$saison, $competition, $poule_id, $nom_equipe]);
            $oldStanding = $oldStandingStmt->fetch(PDO::FETCH_ASSOC);

            // Upsert standings
            $upsert = "INSERT INTO classement 
                (saison, competition, poule_id, nom_equipe, matchs_joues, matchs_gagnes, matchs_nuls, matchs_perdus, buts_pour, buts_contre, difference_buts, points, forme)
                VALUES (:saison, :competition, :poule_id, :nom_equipe, :joues, :gagnes, :nuls, :perdus, :bp, :bc, :diff, :points, :forme)
                ON DUPLICATE KEY UPDATE
                    matchs_joues = VALUES(matchs_joues),
                    matchs_gagnes = VALUES(matchs_gagnes),
                    matchs_nuls = VALUES(matchs_nuls),
                    matchs_perdus = VALUES(matchs_perdus),
                    buts_pour = VALUES(buts_pour),
                    buts_contre = VALUES(buts_contre),
                    difference_buts = VALUES(difference_buts),
                    points = VALUES(points),
                    forme = VALUES(forme)";
            $stmt3 = $pdo->prepare($upsert);
            $params = [
                'saison' => $saison,
                'competition' => $competition,
                'poule_id' => $poule_id,
                'nom_equipe' => $nom_equipe,
                'joues' => $joues,
                'gagnes' => $gagnes,
                'nuls' => $nuls,
                'perdus' => $perdus,
                'bp' => $bp,
                'bc' => $bc,
                'diff' => $diff,
                'points' => $points,
                'forme' => $formeStr
            ];
            $stmt3->execute($params);
            error_log("updateStandings: Upsert executed for team=$nom_equipe, match_id=$matchId, params=" . json_encode($params));

            // Log the action
            $newStandingData = [
                'matchs_joues' => $joues,
                'matchs_gagnes' => $gagnes,
                'matchs_nuls' => $nuls,
                'matchs_perdus' => $perdus,
                'buts_pour' => $bp,
                'buts_contre' => $bc,
                'difference_buts' => $diff,
                'points' => $points,
                'forme' => $formeStr
            ];
            if ($oldStanding) {
                logAction(
                    $pdo,
                    $matchId,
                    'UPDATE_STANDING',
                    "Classement mis à jour: $nom_equipe",
                    $oldStanding,
                    $newStandingData
                );
            } else {
                logAction(
                    $pdo,
                    $matchId,
                    'CREATE_STANDING',
                    "Classement créé: $nom_equipe",
                    null,
                    $newStandingData
                );
            }
        }
    } catch (PDOException $e) {
        error_log("updateStandings failed for match_id=$matchId: " . $e->getMessage());
        throw $e;
    }
}

// Check if registration_periods table exists, create if not
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registration_periods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL DEFAULT 'default',
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            closed_message TEXT,
            is_active BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (category),
            INDEX (is_active)
        )
    ");
} catch (PDOException $e) {
    error_log("Error creating registration_periods table: " . $e->getMessage());
}

// Fetch ongoing and waiting matches
try {
    // Récupérer les matchs avec les statuts 'ongoing' ou 'pending' (en cours ou en attente)
    $currentMatches = $pdo->query("SELECT * FROM matches WHERE status IN ('ongoing', 'pending') ORDER BY match_date DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching current matches: " . $e->getMessage());
    die("Erreur lors du chargement des matchs.");
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update score
        if (isset($_POST['update_score'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $scoreHome = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT);
            $scoreAway = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT);

            if (!$matchId || $scoreHome < 0 || $scoreAway < 0) {
                error_log("Invalid input for updating score: match_id=$matchId, score_home=$scoreHome, score_away=$scoreAway");
                $_SESSION['error'] = "Données invalides pour la mise à jour du score.";
                header("Location: dashboard.php");
                exit();
            }

            // Fetch previous score
            $stmt = $pdo->prepare("SELECT score_home, score_away FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $oldScore = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("UPDATE matches SET score_home = ?, score_away = ? WHERE id = ?");
            $stmt->execute([$scoreHome, $scoreAway, $matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'UPDATE_SCORE',
                "Mise à jour du score",
                ['score_home' => $oldScore['score_home'], 'score_away' => $oldScore['score_away']],
                ['score_home' => $scoreHome, 'score_away' => $scoreAway]
            );

            $_SESSION['message'] = "Score mis à jour avec succès !";
            header("Location: dashboard.php");
            exit();
        }

        // Add goal
        if (isset($_POST['add_goal'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $team = filter_input(INPUT_POST, 'team', FILTER_SANITIZE_STRING);
            $player = filter_input(INPUT_POST, 'player', FILTER_SANITIZE_STRING);
            $minute = filter_input(INPUT_POST, 'minute', FILTER_VALIDATE_INT);

            if (!$matchId || !in_array($team, ['home', 'away']) || empty($player) || !$minute || $minute < 1 || $minute > 120) {
                error_log("Invalid input for adding goal: match_id=$matchId, team=$team, player=$player, minute=$minute");
                $_SESSION['error'] = "Données invalides pour l'ajout du but.";
                header("Location: dashboard.php");
                exit();
            }

            // Adjust minute for second half
            $stmt = $pdo->prepare("SELECT timer_status, first_half_duration, timer_duration FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            $adjustedMinute = $minute;
            if ($match && $match['timer_status'] === 'second_half') {
                $firstHalfMinutes = (int)round(($match['first_half_duration'] ?? 0) / 60);
                $adjustedMinute += $firstHalfMinutes;
            }

            // Insert goal
            $stmt = $pdo->prepare("INSERT INTO goals (match_id, team, player, minute) VALUES (?, ?, ?, ?)");
            $stmt->execute([$matchId, $team, $player, $adjustedMinute]);

            // Update score
            $column = ($team === 'home') ? 'score_home' : 'score_away';
            $stmt = $pdo->prepare("UPDATE matches SET $column = COALESCE($column, 0) + 1 WHERE id = ?");
            $stmt->execute([$matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'ADD_GOAL',
                "But marqué par $player à la $minute minute pour l'équipe " . ($team === 'home' ? $match['team_home'] : $match['team_away']),
                null,
                [
                    'team' => $team,
                    'player' => $player,
                    'minute' => $adjustedMinute,
                    'score_change' => ($team === 'home' ? '+1' : '+0') . ' - ' . ($team === 'away' ? '+1' : '+0')
                ]
            );

            $pdo->commit();
            $_SESSION['message'] = "But enregistré avec succès !";
            header("Location: dashboard.php");
            exit();
        }

        // Add card
        if (isset($_POST['add_card'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $team = filter_input(INPUT_POST, 'team', FILTER_SANITIZE_STRING);
            $player = filter_input(INPUT_POST, 'player', FILTER_SANITIZE_STRING);
            $cardType = filter_input(INPUT_POST, 'card_type', FILTER_SANITIZE_STRING);
            $minute = filter_input(INPUT_POST, 'minute', FILTER_VALIDATE_INT);

            if (!$matchId || !in_array($team, ['home', 'away']) || empty($player) || 
                !in_array($cardType, ['yellow', 'red', 'blue']) || !$minute || $minute < 1 || $minute > 120) {
                error_log("Invalid input for adding card: match_id=$matchId, team=$team, player=$player, card_type=$cardType, minute=$minute");
                $_SESSION['error'] = "Données invalides pour l'ajout du carton.";
                header("Location: dashboard.php");
                exit();
            }

            // Adjust minute for second half
            $stmt = $pdo->prepare("SELECT timer_status, first_half_duration, timer_duration FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
                        
            $adjustedMinute = $minute;
            if ($match && $match['timer_status'] === 'second_half') {
                $firstHalfMinutes = (int)round(($match['first_half_duration'] ?? 0) / 60);
                $adjustedMinute += $firstHalfMinutes;
            }

            $stmt = $pdo->prepare("INSERT INTO cards (match_id, team, player, card_type, minute) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$matchId, $team, $player, $cardType, $adjustedMinute]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'ADD_CARD',
                "Carton $cardType pour $player à la $minute minute",
                null,
                [
                    'team' => $team,
                    'player' => $player,
                    'card_type' => $cardType,
                    'minute' => $adjustedMinute
                ]
            );

            $pdo->commit();
            $_SESSION['message'] = "Carton enregistré avec succès !";
            header("Location: dashboard.php");
            exit();
        }

        // Start first half
        if (isset($_POST['start_first_half'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            if (!$matchId) {
                error_log("Invalid match_id for start_first_half: $matchId");
                $_SESSION['error'] = "ID de match invalide.";
                header("Location: dashboard.php");
                exit();
            }
            
            // Fetch previous status
            $stmt = $pdo->prepare("SELECT timer_status FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $oldStatus = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE matches SET timer_start = NOW(), timer_elapsed = 0, timer_status = 'first_half', timer_paused = 0, first_half_duration = 0 WHERE id = ?");
            $stmt->execute([$matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'START_FIRST_HALF',
                "Début de la première mi-temps",
                ['timer_status' => $oldStatus],
                ['timer_status' => 'first_half']
            );

            $pdo->commit();
            $_SESSION['message'] = "1ère mi-temps démarrée !";
            header("Location: dashboard.php");
            exit();
        }

        // Stop timer
        if (isset($_POST['stop_timer'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            if (!$matchId) {
                error_log("Invalid match_id for stop_timer: $matchId");
                $_SESSION['error'] = "ID de match invalide.";
                header("Location: dashboard.php");
                exit();
            }

            // Check if already stopped
            $stmt = $pdo->prepare("SELECT timer_status FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($match && in_array($match['timer_status'], ['half_time', 'ended'])) {
                $pdo->commit();
                header("Location: dashboard.php");
                exit();
            }

            // Fetch match data
            $stmt = $pdo->prepare("SELECT timer_start, timer_elapsed, timer_status, first_half_extra, second_half_extra, timer_duration, first_half_duration FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$match) {
                error_log("Match not found for match_id: $matchId");
                $_SESSION['error'] = "Match non trouvé.";
                header("Location: dashboard.php");
                exit();
            }

            $elapsed = (int)$match['timer_elapsed'];
            $halfDuration = (int)($match['timer_duration'] / 2);
            $additionalTime = $match['timer_status'] === 'first_half' ? (int)($match['first_half_extra'] ?? 0) : (int)($match['second_half_extra'] ?? 0);

            // Calculate elapsed time
            if ($match['timer_start'] && strtotime($match['timer_start']) !== false) {
                $currentTime = time();
                $startTime = strtotime($match['timer_start']);
                $elapsed += ($currentTime - $startTime);

                // Cap elapsed time
                if ($match['timer_status'] === 'first_half') {
                    $maxElapsed = $halfDuration ;
                    if ($elapsed > $maxElapsed) {
                        $elapsed = $maxElapsed;
                        error_log("Elapsed time capped at $maxElapsed for first half, match_id: $matchId");
                    }
                } else {
                    $maxElapsed = $halfDuration + $additionalTime;
                    if ($elapsed > $maxElapsed) {
                        $elapsed = $maxElapsed;
                        error_log("Elapsed time capped at $maxElapsed for second half, match_id: $matchId");
                    }
                }
            }

            // Determine new status and save first_half_duration
            $newStatus = ($match['timer_status'] === 'first_half') ? 'half_time' : 'ended';
            if ($newStatus === 'half_time') {
                $firstHalfDuration = min($elapsed, $halfDuration + $additionalTime);
                $stmt = $pdo->prepare("UPDATE matches SET timer_start = NULL, timer_elapsed = ?, timer_status = ?, timer_paused = 0, first_half_duration = ? WHERE id = ?");
                $stmt->execute([$elapsed, $newStatus, $firstHalfDuration, $matchId]);
            } else {
                $stmt = $pdo->prepare("UPDATE matches SET timer_start = NULL, timer_elapsed = ?, timer_status = ?, timer_paused = 0 WHERE id = ?");
                $stmt->execute([$elapsed, $newStatus, $matchId]);
            }

            // Log the action
            logAction(
                $pdo,
                $matchId,
                $newStatus === 'half_time' ? 'END_FIRST_HALF' : 'END_MATCH',
                $newStatus === 'half_time' ? "Fin de la première mi-temps" : "Fin du match",
                ['timer_status' => $match['timer_status']],
                ['timer_status' => $newStatus, 'elapsed_time' => $elapsed]
            );

            $pdo->commit();
            $_SESSION['message'] = ($newStatus === 'half_time') ? "Mi-temps !" : "Match terminé !";
            header("Location: dashboard.php");
            exit();
        }

        // Start second half
        if (isset($_POST['start_second_half'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            if (!$matchId) {
                error_log("Invalid match_id for start_second_half: $matchId");
                $_SESSION['error'] = "ID de match invalide.";
                header("Location: dashboard.php");
                exit();
            }
            
            // Fetch previous status
            $stmt = $pdo->prepare("SELECT timer_status FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $oldStatus = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE matches SET timer_start = NOW(), timer_elapsed = 0, timer_status = 'second_half', timer_paused = 0 WHERE id = ?");
            $stmt->execute([$matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'START_SECOND_HALF',
                "Début de la deuxième mi-temps",
                ['timer_status' => $oldStatus],
                ['timer_status' => 'second_half']
            );

            $pdo->commit();
            $_SESSION['message'] = "2ème mi-temps démarrée !";
            header("Location: dashboard.php");
            exit();
        }

        // Set first half extra time
        if (isset($_POST['set_first_half_extra'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $extra = filter_input(INPUT_POST, 'first_half_extra', FILTER_VALIDATE_INT) * 60;
            if (!$matchId || $extra < 0) {
                error_log("Invalid input for first_half_extra: match_id=$matchId, extra=$extra");
                $_SESSION['error'] = "Données invalides pour le temps additionnel.";
                header("Location: dashboard.php");
                exit();
            }

            // Fetch previous value
            $stmt = $pdo->prepare("SELECT first_half_extra FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $oldExtra = $stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE matches SET first_half_extra = ? WHERE id = ?");
            $stmt->execute([$extra, $matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'SET_EXTRA_TIME',
                "Temps additionnel première mi-temps défini",
                ['first_half_extra' => $oldExtra],
                ['first_half_extra' => $extra]
            );

            $pdo->commit();
            $_SESSION['message'] = "Temps additionnel 1ère mi-temps défini !";
            header("Location: dashboard.php");
            exit();
        }

        // Set second half extra time
        if (isset($_POST['set_second_half_extra'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $extra = filter_input(INPUT_POST, 'second_half_extra', FILTER_VALIDATE_INT) * 60;
            if (!$matchId || $extra < 0) {
                error_log("Invalid input for second_half_extra: match_id=$matchId, extra=$extra");
                $_SESSION['error'] = "Données invalides pour le temps additionnel.";
                header("Location: dashboard.php");
                exit();
            }

            // Fetch previous value
            $stmt = $pdo->prepare("SELECT second_half_extra FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $oldExtra = $stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE matches SET second_half_extra = ? WHERE id = ?");
            $stmt->execute([$extra, $matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'SET_EXTRA_TIME',
                "Temps additionnel deuxième mi-temps défini",
                ['second_half_extra' => $oldExtra],
                ['second_half_extra' => $extra]
            );

            $pdo->commit();
            $_SESSION['message'] = "Temps additionnel 2ème mi-temps défini !";
            header("Location: dashboard.php");
            exit();
        }

        // Finalize match and update standings
        if (isset($_POST['finalize_match'])) {
            error_log("=== DÉBUT FINALISATION MATCH ===");
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            if (!$matchId) {
                $errorMsg = "Invalid match_id for finalize_match: " . ($_POST['match_id'] ?? 'non défini');
                error_log($errorMsg);
                $_SESSION['error'] = "ID de match invalide: " . ($_POST['match_id'] ?? 'non défini');
                header("Location: dashboard.php");
                exit();
            }
            error_log("Tentative de finalisation du match ID: $matchId");

            // Fetch previous status
            $stmt = $pdo->prepare("SELECT status, score_home, score_away, saison, competition, poule_id FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$match) {
                error_log("Match not found for match_id: $matchId");
                $_SESSION['error'] = "Match non trouvé.";
                header("Location: dashboard.php");
                exit();
            }

            // Validate match data
            if (!isset($match['score_home']) || !isset($match['score_away']) || empty($match['saison']) || empty($match['competition']) || !isset($match['poule_id'])) {
                $errorMsg = "Données du match incomplètes pour la finalisation. match_id=$matchId, data=" . json_encode($match);
                error_log($errorMsg);
                $_SESSION['error'] = $errorMsg;
                header("Location: dashboard.php");
                exit();
            }
            error_log("Données du match validées: " . json_encode([
                'score_home' => $match['score_home'],
                'score_away' => $match['score_away'],
                'saison' => $match['saison'],
                'competition' => $match['competition'],
                'poule_id' => $match['poule_id']
            ]));

            $oldStatus = $match['status'];
            error_log("Mise à jour du statut du match $matchId de '$oldStatus' à 'completed'");
            $stmt = $pdo->prepare("UPDATE matches SET status = 'completed', timer_start = NULL, timer_status = 'ended' WHERE id = ?");
            $updateResult = $stmt->execute([$matchId]);
            error_log("Résultat de la mise à jour du statut: " . ($updateResult ? 'succès' : 'échec'));

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'FINALIZE_MATCH',
                "Match finalisé",
                ['status' => $oldStatus],
                ['status' => 'completed']
            );

            // Inclure la fonction de mise à jour du classement
            error_log("Inclusion du fichier temp_update_classement.php");
            require_once __DIR__ . '/../temp_update_classement.php';
            
            // Récupérer toutes les données du match pour la mise à jour du classement
            $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $matchData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($matchData) {
                error_log("Données du match récupérées pour la mise à jour du classement: " . json_encode($matchData));
                // Préparer les données pour la mise à jour du classement
                $classementData = [
                    'saison' => $matchData['saison'],
                    'competition' => $matchData['competition'],
                    'poule_id' => $matchData['poule_id'],
                    'team_home' => $matchData['team_home'],
                    'team_away' => $matchData['team_away'],
                    'score_home' => (int)$matchData['score_home'],
                    'score_away' => (int)$matchData['score_away']
                ];
                error_log("Données transmises à updateClassementForMatch: " . json_encode($classementData));
                
                // Mettre à jour le classement avec les nouvelles données
                $updateResult = updateClassementForMatch($pdo, $classementData);
                
                if (!$updateResult) {
                    $errorMsg = "Erreur lors de la mise à jour du classement pour le match ID: $matchId";
                    error_log($errorMsg);
                    throw new Exception($errorMsg);
                }
                error_log("Mise à jour du classement effectuée avec succès pour le match ID: $matchId");
                
                // Mettre à jour l'ancien système de classement pour compatibilité
                if (function_exists('updateStandings')) {
                    updateStandings($matchId, $pdo);
                }
            }

            error_log("Validation de la transaction pour le match ID: $matchId");
            $pdo->commit();
            $successMsg = "Match finalisé et classement mis à jour avec succès !";
            error_log($successMsg);
            $_SESSION['message'] = $successMsg;
            header("Location: dashboard.php");
            exit();
        }

        // Set match duration
        if (isset($_POST['set_duration'])) {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $duration = filter_input(INPUT_POST, 'match_duration', FILTER_VALIDATE_INT);

            if (!$matchId || $duration < 1 || $duration > 120) {
                error_log("Invalid input for setting duration: match_id=$matchId, duration=$duration");
                $_SESSION['error'] = "Durée invalide (1 à 120 minutes).";
                header("Location: dashboard.php");
                exit();
            }

            // Fetch previous duration
            $stmt = $pdo->prepare("SELECT timer_duration FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $oldDuration = $stmt->fetchColumn();

            // Convert to seconds
            $durationSeconds = $duration * 60;
            $stmt = $pdo->prepare("UPDATE matches SET timer_duration = ? WHERE id = ?");
            $stmt->execute([$durationSeconds, $matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'SET_MATCH_DURATION',
                "Durée du match définie",
                ['timer_duration' => $oldDuration],
                ['timer_duration' => $durationSeconds]
            );

            $pdo->commit();
            $_SESSION['message'] = "Durée réglementaire définie à $duration minutes !";
            header("Location: dashboard.php");
            exit();
        }

        // Handle registration period form submission
        if (isset($_POST['add_period']) || isset($_POST['update_period'])) {
            $periodId = filter_input(INPUT_POST, 'period_id', FILTER_VALIDATE_INT);
            $startDate = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
            $endDate = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
            $closedMessage = filter_input(INPUT_POST, 'closed_message', FILTER_SANITIZE_STRING);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $category = 'default'; // Can be extended to support multiple categories

            // Validate dates
            if (!$startDate || !$endDate || strtotime($startDate) === false || strtotime($endDate) === false) {
                throw new RuntimeException("Dates invalides");
            }

            if (isset($_POST['add_period'])) {
                // Insert new period
                $stmt = $pdo->prepare("
                    INSERT INTO registration_periods 
                    (category, start_date, end_date, closed_message, is_active)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$category, $startDate, $endDate, $closedMessage, $isActive]);
                $_SESSION['message'] = "Période d'inscription ajoutée avec succès!";
            } else {
                // Update existing period
                if (!$periodId) {
                    throw new RuntimeException("ID de période invalide");
                }
                $stmt = $pdo->prepare("
                    UPDATE registration_periods SET
                    start_date = ?,
                    end_date = ?,
                    closed_message = ?,
                    is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$startDate, $endDate, $closedMessage, $isActive, $periodId]);
                $_SESSION['message'] = "Période d'inscription mise à jour avec succès!";
            }
        }

        // Handle period deletion
        if (isset($_POST['delete_period'])) {
            $periodId = filter_input(INPUT_POST, 'period_id', FILTER_VALIDATE_INT);
            if (!$periodId) {
                throw new RuntimeException("ID de période invalide");
            }
            $stmt = $pdo->prepare("DELETE FROM registration_periods WHERE id = ?");
            $stmt->execute([$periodId]);
            $_SESSION['message'] = "Période d'inscription supprimée avec succès!";
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error processing form action: " . ($_POST['update_score'] ?? 'unknown') . ", match_id=" . ($_POST['match_id'] ?? 'unknown') . 
                  ", error: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors du traitement des données: " . htmlspecialchars($e->getMessage());
        header("Location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Tableau de bord - Administration</title>
    <style>
        :root {
            --bs-font-sans-serif: 'Inter', system-ui, -apple-system, sans-serif;
        }
        body {
            font-family: var(--bs-font-sans-serif);
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            box-shadow: 1px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: var(--bs-body-color);
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease-in-out;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--bs-primary);
            color: white;
        }
        .sidebar .nav-link i {
            width: 1.5rem;
            text-align: center;
            margin-right: 0.5rem;
        }
    </style>
    <style>
        /* Improved Dashboard Styles */
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border-left: 4px solid #4e73df;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        .stat-card.primary { border-color: #4e73df; }
        .stat-card.success { border-color: #1cc88a; }
        .stat-card.info { border-color: #36b9cc; }
        .stat-card.warning { border-color: #f6c23e; }
        
        .match-card {
            border-left: 4px solid #4e73df;
            margin-bottom: 20px;
        }
        
        .timer-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .action-buttons .btn {
            margin: 2px;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons .btn {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
            .card-body {
                padding: 1rem;
            }
        }
        
        /* Improved form controls */
        .form-control-sm {
            padding: 0.25rem 0.5rem;
        }
        
        /* Better badge styling */
        .badge-lg {
            padding: 0.5em 0.8em;
            font-size: 0.9em;
        }

        /* Existing sidebar styles */
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
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" id="themeToggle">
                            <i class="fas fa-moon me-1"></i> Thème
                        </button>
                    </div>
                </div>
                
                <?php
                // Récupération des statistiques depuis la base de données
                try {
                    // Nombre total de matchs joués
                    $totalMatches = $pdo->query("SELECT COUNT(*) as total FROM matches WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Nombre total de buts marqués
                    $totalGoals = $pdo->query("SELECT SUM(score_home + score_away) as total FROM matches WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Nombre d'équipes enregistrées
                    $totalTeams = $pdo->query("SELECT COUNT(*) as total FROM teams")->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Taux de remplissage (exemple basé sur les matchs programmés vs joués)
                    $totalScheduled = $pdo->query("SELECT COUNT(*) as total FROM matches WHERE status != 'cancelled'")->fetch(PDO::FETCH_ASSOC)['total'];
                    $completionRate = $totalScheduled > 0 ? round(($totalMatches / $totalScheduled) * 100) : 0;
                    
                    // Statistiques du mois précédent pour la comparaison
                    $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
                    $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
                    
                    $lastMonthMatches = $pdo->prepare("
                        SELECT COUNT(*) as total 
                        FROM matches 
                        WHERE status = 'completed' 
                        AND match_date BETWEEN ? AND ?
                    ");
                    $lastMonthMatches->execute([$lastMonthStart, $lastMonthEnd]);
                    $lastMonthMatchCount = $lastMonthMatches->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                    
                    // Calcul des pourcentages d'évolution
                    $matchChange = $lastMonthMatchCount > 0 ? 
                        round((($totalMatches - $lastMonthMatchCount) / $lastMonthMatchCount) * 100) : 100;
                    
                } catch (PDOException $e) {
                    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
                    // Valeurs par défaut en cas d'erreur
                    $totalMatches = 0;
                    $totalGoals = 0;
                    $totalTeams = 0;
                    $completionRate = 0;
                    $matchChange = 0;
                }
                ?>
                
                <!-- Statistiques et indicateurs clés -->
                <div class="row mb-4">
                    <!-- KPI Cards -->
                    <div class="col-12 col-md-6 col-xl-3 mb-4">
                        <div class="card border-start border-4 border-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Matchs en cours</h6>
                                        <h3 class="mb-0"><?= count($currentMatches) ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-futbol text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                                <?php if ($matchChange != 0): ?>
                                <div class="mt-3">
                                    <span class="badge bg-<?= $matchChange > 0 ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $matchChange > 0 ? 'success' : 'danger' ?>">
                                        <i class="fas fa-arrow-<?= $matchChange > 0 ? 'up' : 'down' ?> me-1"></i> 
                                        <?= abs($matchChange) ?>% vs mois dernier
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6 col-xl-3 mb-4">
                        <div class="card border-start border-4 border-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Matchs terminés</h6>
                                        <h3 class="mb-0"><?= $totalMatches ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-flag-checkered text-success" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small">
                                        <i class="fas fa-futbol me-1"></i> <?= $totalGoals ?> buts marqués
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6 col-xl-3 mb-4">
                        <div class="card border-start border-4 border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Équipes enregistrées</h6>
                                        <h3 class="mb-0"><?= $totalTeams ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-users text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small">
                                        <i class="fas fa-trophy me-1"></i> 
                                        <?php 
                                        $activeCompetitions = $pdo->query("SELECT COUNT(DISTINCT competition) as count FROM matches WHERE status != 'completed'")->fetch(PDO::FETCH_ASSOC)['count'];
                                        echo $activeCompetitions . ' compétitions actives';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6 col-xl-3 mb-4">
                        <div class="card border-start border-4 border-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Taux de complétion</h6>
                                        <h3 class="mb-0"><?= $completionRate ?>%</h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-chart-pie text-info" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?= $completionRate ?>%;" 
                                             aria-valuenow="<?= $completionRate ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Graphique des statistiques -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Aperçu des statistiques</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <canvas id="goalsChart" height="250"></canvas>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted mb-3">Résumé des matchs</h6>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Victoires à domicile</span>
                                        <strong id="homeWins">0%</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%" 
                                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Matchs nuls</span>
                                        <strong id="draws">0%</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 0%" 
                                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Victoires à l'extérieur</span>
                                        <strong id="awayWins">0%</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 0%" 
                                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="text-muted small mt-4">
                                    <i class="fas fa-info-circle me-1"></i> Données mises à jour en temps réel
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                // Load stats data when DOM is ready
                document.addEventListener('DOMContentLoaded', function() {
                    // Get parent container - try multiple fallbacks
                    let statsContainer = document.querySelector('.card.shadow-sm.mb-4');
                    if (!statsContainer) statsContainer = document.querySelector('.card-body');
                    if (!statsContainer) statsContainer = document.querySelector('main');
                    
                    if (!statsContainer) {
                        console.error('Could not find container for stats display');
                        return;
                    }

                    // Create loading indicator container 
                    const loadingContainer = document.createElement('div');
                    loadingContainer.id = 'statsLoadingContainer';
                    
                    const loadingIndicator = document.createElement('div');
                    loadingIndicator.className = 'text-center py-4';
                    loadingIndicator.innerHTML = `
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des statistiques...</p>
                    `;
                    
                    // Function to safely append loading indicator
                    function appendLoadingIndicator() {
                        // First try to find the chart container
                        const chartElement = document.getElementById('goalsChart');
                        const chartContainer = chartElement && chartElement.parentNode;
                        
                        // Try to append to chart container first
                        if (chartContainer) {
                            try {
                                chartContainer.innerHTML = '';
                                chartContainer.appendChild(loadingIndicator);
                                return true;
                            } catch (e) {
                                console.warn('Failed to append to chart container, trying fallback:', e);
                            }
                        }
                        
                        // Fall back to stats container
                        if (statsContainer) {
                            try {
                                statsContainer.innerHTML = '';
                                statsContainer.appendChild(loadingIndicator);
                                return true;
                            } catch (e) {
                                console.error('Failed to append to stats container:', e);
                            }
                        }
                        
                        // Last resort, try document body
                        try {
                            document.body.appendChild(loadingIndicator);
                            return true;
                        } catch (e) {
                            console.error('Failed to append loading indicator to any container:', e);
                            return false;
                        }
                    }
                    
                    // Add the loading indicator to the appropriate container
                    appendLoadingIndicator();
                    
                    // Make the API request
                    fetch('get_stats_data.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }
                            return response.json().catch(() => {
                                throw new Error('Invalid JSON response');
                            });
                        })
                        .then(data => {
                            if (!data) {
                                throw new Error('No data received');
                            }
                            
                            if (data.success) {
                                // Update the goals chart if we have the element and data
                                if (data.goalsData) {
                                    try {
                                        // Remove loading indicator
                                        if (loadingIndicator && loadingIndicator.parentNode) {
                                            loadingIndicator.parentNode.removeChild(loadingIndicator);
                                        }
                                        
                                        // Create or update chart
                                        if (chartElement && chartElement.parentNode) {
                                            chartElement.parentNode.innerHTML = '<canvas id="goalsChart" height="250"></canvas>';
                                            initGoalsChart(data.goalsData);
                                        } else if (statsContainer) {
                                            // If chart element doesn't exist, create it in the container
                                            const newChart = document.createElement('canvas');
                                            newChart.id = 'goalsChart';
                                            newChart.height = 250;
                                            statsContainer.appendChild(newChart);
                                            initGoalsChart(data.goalsData);
                                        }
                                    } catch (e) {
                                        console.error('Error initializing chart:', e);
                                        showError('Erreur lors de l\'initialisation du graphique');
                                    }
                                }
                                
                                // Update match statistics if we have the data
                                if (data.matchStats) {
                                    try {
                                        updateMatchStats(data.matchStats);
                                    } catch (e) {
                                        console.error('Error updating match stats:', e);
                                    }
                                }
                                
                                // Update summary stats if they exist
                                if (data.summary) {
                                    try {
                                        if (data.summary.totalGoals !== undefined) {
                                            const totalGoalsElement = document.getElementById('totalGoals');
                                            if (totalGoalsElement) totalGoalsElement.textContent = data.summary.totalGoals;
                                        }
                                        if (data.summary.avgGoals !== undefined) {
                                            const avgGoalsElement = document.getElementById('avgGoals');
                                            if (avgGoalsElement) avgGoalsElement.textContent = data.summary.avgGoals;
                                        }
                                    } catch (e) {
                                        console.error('Error updating summary stats:', e);
                                    }
                                }
                            } else {
                                console.error('Erreur lors du chargement des données:', data.error || 'Unknown error');
                                showError('Impossible de charger les statistiques. Veuillez réessayer plus tard.');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des données:', error);
                            showError('Erreur de connexion au serveur. Les statistiques ne sont pas disponibles pour le moment.');
                            
                            // If we have a loading indicator, replace it with the error
                            if (loadingIndicator && loadingIndicator.parentNode) {
                                loadingIndicator.parentNode.removeChild(loadingIndicator);
                            }
                        });
                        
                    function showError(message) {
                        try {
                            // Try to find a container to show the error
                            let container = chartElement && chartElement.parentNode ? 
                                chartElement.parentNode : 
                                (statsContainer || document.querySelector('.card.shadow-sm.mb-4'));
                            
                            if (container) {
                                // Remove loading indicator if it exists
                                if (loadingIndicator && loadingIndicator.parentNode) {
                                    loadingIndicator.parentNode.removeChild(loadingIndicator);
                                }
                                
                                // Create error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'alert alert-warning mb-0';
                                errorDiv.innerHTML = `
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    ${message}
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="window.location.reload()">
                                        <i class="fas fa-sync-alt me-1"></i> Réessayer
                                    </button>
                                `;
                                
                                // Clear container and add error message
                                container.innerHTML = '';
                                container.appendChild(errorDiv);
                            }
                            
                            // Also update the stats summary to show N/A
                            document.querySelectorAll('.stat-value').forEach(el => {
                                if (!el.textContent || el.textContent.trim() === '') {
                                    el.textContent = 'N/A';
                                }
                            });
                        } catch (e) {
                            console.error('Error showing error message:', e);
                        }
                    }
                    
                    // Fonction pour initialiser le graphique des buts
                    function initGoalsChart(goalsData) {
                        const ctx = document.getElementById('goalsChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: goalsData.labels || [],
                                datasets: [{
                                    label: 'Buts marqués par match',
                                    data: goalsData.data || [],
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                    tension: 0.3,
                                    fill: true,
                                    borderWidth: 2,
                                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                                    pointBorderColor: '#fff',
                                    pointHoverRadius: 5,
                                    pointHoverBackgroundColor: 'rgba(54, 162, 235, 1)',
                                    pointHoverBorderColor: '#fff',
                                    pointHitRadius: 10,
                                    pointBorderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleFont: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        bodyFont: {
                                            size: 13
                                        },
                                        padding: 12,
                                        displayColors: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        },
                                        grid: {
                                            drawBorder: false,
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    }
                                },
                                elements: {
                                    line: {
                                        borderWidth: 2
                                    }
                                }
                            }
                        });
                    }
                    
                    // Fonction pour mettre à jour les statistiques des matchs
                    function updateMatchStats(stats) {
                        if (!stats) return;
                        
                        // Mettre à jour les pourcentages et les barres de progression
                        ['homeWins', 'draws', 'awayWins'].forEach((key, index) => {
                            const element = document.getElementById(key);
                            const progressBar = element.closest('.mb-3').querySelector('.progress-bar');
                            const value = stats[key] || 0;
                            
                            element.textContent = value + '%';
                            progressBar.style.width = value + '%';
                            progressBar.setAttribute('aria-valuenow', value);
                        });
                    }
                    
                    // Rafraîchir les données toutes les 5 minutes
                    setInterval(() => {
                        fetch('get_stats_data.php?cache=' + new Date().getTime())
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    updateMatchStats(data.matchStats);
                                }
                            });
                    }, 300000); // 5 minutes
                });
                </script>
                
                <!-- Alertes -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Matchs en cours -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <i class="fas fa-running me-2"></i>
                            <h5 class="mb-0">Matchs en cours</h5>
                            <span class="badge bg-white text-primary ms-2"><?= count($currentMatches) ?> actif(s)</span>
                        </div>
                        
                        <!-- Barre de recherche et filtres -->
                        <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto mt-2 mt-md-0">
                            <!-- Barre de recherche -->
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="matchSearch" class="form-control border-start-0" 
                                       placeholder="Rechercher un match..." 
                                       onkeyup="filterMatches()">
                            </div>
                            
                            <!-- Filtre par compétition -->
                            <select id="competitionFilter" class="form-select form-select-sm" style="max-width: 200px;" onchange="filterMatches()">
                                <option value="">Toutes les compétitions</option>
                                <?php
                                // Récupérer la liste des compétitions uniques
                                $competitions = [];
                                foreach ($currentMatches as $match) {
                                    if (!in_array($match['competition'], $competitions)) {
                                        $competitions[] = $match['competition'];
                                        echo "<option value='".htmlspecialchars($match['competition'])."'>".htmlspecialchars($match['competition'])."</option>";
                                    }
                                }
                                ?>
                            </select>
                            
                            <!-- Filtre par statut -->
                            <select id="statusFilter" class="form-select form-select-sm" style="max-width: 180px;" onchange="filterMatches()">
                                <option value="">Tous les statuts</option>
                                <option value="ongoing">En cours</option>
                                <option value="completed">Terminé</option>
                                <option value="waiting">En attente</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Messages d'erreur -->
                        <div id="noMatchesMessage" class="alert alert-info d-none">
                            <i class="fas fa-info-circle me-2"></i> Aucun match ne correspond à votre recherche.
                        </div>
                        
                        <!-- Indicateur de chargement -->
                        <div id="loadingIndicator" class="text-center py-4 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 mb-0">Chargement des matchs...</p>
                        </div>
                        
<?php if (empty($currentMatches)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i> Aucun match en cours trouvé, mais on s'attend à en voir.
        <div class="mt-2">
            <strong>Debug:</strong>
            <pre><?= htmlspecialchars(print_r($pdo->query("SELECT * FROM matches WHERE status IN ('ongoing', 'pending')")->fetchAll(PDO::FETCH_ASSOC), true)) ?></pre>
        </div>
    </div>
<?php else: ?>
                            <div class="row">
                                <?php foreach ($currentMatches as $match): ?>
                                <div class="col-12 col-md-6 col-lg-4 mb-4 match-card" data-match-id="<?= $match['id'] ?>" data-competition="<?= htmlspecialchars($match['competition']) ?>" data-status="<?= $match['status'] ?>">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-muted small">
                                                    <i class="fas fa-trophy me-1"></i> <?= htmlspecialchars($match['competition'] ?? 'Compétition' ) ?>
                                                </h6>
                                                <span class="badge bg-<?= in_array($match['status'], ['en_cours', 'ongoing']) ? 'success' : 'warning' ?> text-white">
                                                    <?= in_array($match['status'], ['en_cours', 'ongoing']) ? 'En cours' : 'En attente' ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body p-3">
                                            <!-- Ligne unique pour les équipes, score et minuteur -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <!-- Équipe à domicile -->
                                                <div class="d-flex flex-column align-items-center" style="width: 35%;">
                                                    <div class="d-flex align-items-center w-100">
                                                        <?php 
                                                        // Utiliser le chemin du logo depuis la base de données ou un logo par défaut
                                                        $homeLogo = !empty($match['home_logo']) ? "../" . $match['home_logo'] : "../assets/img/teams/default.png";
                                                        ?>
                                                        <img src="<?= $homeLogo ?>" 
                                                             alt="<?= htmlspecialchars($match['team_home']) ?>" 
                                                             width="24" 
                                                             height="24"
                                                             class="me-2"
                                                             onerror="this.src='../assets/img/teams/default.png'"
                                                             style="object-fit: contain;">
                                                        <div class="text-truncate" style="font-size: 0.85rem; font-weight: 500;">
                                                            <?= mb_strimwidth(htmlspecialchars($match['team_home']), 0, 20, '...') ?>
                                                        </div>
                                                    </div>
                                                    <div class="fw-bold mt-1" style="font-size: 1.1rem;">
                                                        <?= $match['score_home'] ?? '0' ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Score et minuteur au centre -->
                                                <div class="d-flex flex-column align-items-center" style="width: 30%;">
                                                    <div class="badge bg-primary mb-1" style="font-size: 0.65rem;"><?= htmlspecialchars($match['phase']) ?></div>
                                                    <div class="d-flex align-items-center justify-content-center" style="min-width: 80px;">
                                                        <span class="fw-bold" style="font-size: 1.1rem;">
                                                            <?= $match['score_home'] ?? '0' ?> - <?= $match['score_away'] ?? '0' ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center mt-1" style="font-size: 0.8rem;">
                                                        <i class="far fa-clock me-1"></i>
                                                        <span class="fw-bold" id="timer-<?= $match['id'] ?>">
                                                            <?php
                                                            if (($match['timer_status'] ?? '') === 'ended') {
                                                                echo '<span class="badge bg-danger">Terminé</span>';
                                                            } else {
                                                                $elapsed = $match['timer_elapsed'] ?? 0;
                                                                if (($match['timer_start'] ?? '') && !($match['timer_paused'] ?? true)) {
                                                                    $elapsed += (strtotime('now') - strtotime($match['timer_start']));
                                                                }
                                                                $displayMinutes = floor($elapsed / 60);
                                                                if (($match['timer_status'] ?? '') === 'second_half') {
                                                                    $firstHalfMinutes = floor(($match['first_half_duration'] ?? 0) / 60);
                                                                    $displayMinutes += $firstHalfMinutes;
                                                                }
                                                                $displaySeconds = $elapsed % 60;
                                                                echo sprintf('%02d:%02d', $displayMinutes, $displaySeconds);
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                                        <?php if (in_array($match['status'] ?? '', ['en_cours', 'ongoing'])): ?>
                                                            <?= $match['timer_status'] === 'second_half' ? '2ème mi-temps' : '1ère mi-temps' ?>
                                                        <?php else: ?>
                                                            En attente
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Équipe à l'extérieur -->
                                                <div class="d-flex flex-column align-items-center" style="width: 35%;">
                                                    <div class="d-flex align-items-center w-100 justify-content-end">
                                                        <div class="text-truncate text-end me-2" style="font-size: 0.85rem; font-weight: 500;">
                                                            <?= mb_strimwidth(htmlspecialchars($match['team_away']), 0, 20, '...') ?>
                                                        </div>
                                                        <?php 
                                                        // Utiliser le chemin du logo depuis la base de données ou un logo par défaut
                                                        $awayLogo = !empty($match['away_logo']) ? "../" . $match['away_logo'] : "../assets/img/teams/default.png";
                                                        ?>
                                                        <img src="<?= $awayLogo ?>" 
                                                             alt="<?= htmlspecialchars($match['team_away']) ?>" 
                                                             width="24" 
                                                             height="24"
                                                             class="ms-2"
                                                             onerror="this.src='../assets/img/teams/default.png'"
                                                             style="object-fit: contain;">
                                                    </div>
                                                    <div class="fw-bold mt-1" style="font-size: 1.1rem;">
                                                        <?= $match['score_away'] ?? '0' ?>
                                                    </div>
                                                </div>
                                            </div>    
                                            
                                            <!-- Barre de progression -->
                                            <div class="progress mb-3" style="height: 6px;">
                                                <?php 
                                                $progress = 0;
                                                if (isset($match['timer_elapsed']) && isset($match['timer_duration'])) {
                                                    $progress = min(100, ($match['timer_elapsed'] / $match['timer_duration']) * 100);
                                                }
                                                ?>
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?= $progress ?>%;" 
                                                     aria-valuenow="<?= $progress ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            
                                            <!-- Actions de gestion -->
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <!-- Démarrer/Arrêter le match -->
                                                <?php if ($match['timer_status'] === 'not_started'): ?>
                                                    <form method="post" class="flex-grow-1">
                                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                        <button type="submit" name="start_first_half" class="btn btn-success btn-sm w-100">
                                                            <i class="fas fa-play me-1"></i> Démarrer
                                                        </button>
                                                    </form>
                                                <?php elseif ($match['timer_status'] === 'first_half' || $match['timer_status'] === 'second_half'): ?>
                                                    <form method="post" class="flex-grow-1">
                                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                        <button type="submit" name="stop_timer" class="btn btn-danger btn-sm w-100">
                                                            <i class="fas fa-stop me-1"></i> Arrêter
                                                        </button>
                                                    </form>
                                                <?php elseif ($match['timer_status'] === 'half_time'): ?>
                                                    <form method="post" class="flex-grow-1">
                                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                        <button type="submit" name="start_second_half" class="btn btn-info btn-sm w-100">
                                                            <i class="fas fa-forward me-1"></i> 2ème mi-temps
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <!-- Finaliser le match -->
                                                <form method="post" class="flex-grow-1">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <button type="submit" name="finalize_match" class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-flag-checkered me-1"></i> Finaliser
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Formulaire de mise à jour du score -->
                                            <form method="post" class="mb-3">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="row g-2">
                                                    <div class="col-5">
                                                        <input type="number" name="score_home" class="form-control text-center" 
                                                               value="<?= $match['score_home'] ?? '0' ?>" min="0" placeholder="Score">
                                                    </div>
                                                    <div class="col-2 text-center">
                                                        <div class="h5 mb-0">-</div>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="number" name="score_away" class="form-control text-center" 
                                                               value="<?= $match['score_away'] ?? '0' ?>" min="0" placeholder="Score">
                                                    </div>
                                                    <div class="col-12">
                                                        <button type="submit" name="update_score" class="btn btn-primary btn-sm w-100">
                                                            <i class="fas fa-save me-1"></i> Mettre à jour
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                            <!-- Liste des buts -->
                                            <?php 
                                            $goals = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
                                            $goals->execute([$match['id']]);
                                            $goals = $goals->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <?php if (!empty($goals)): ?>
                                            <div class="mb-3">
                                                <h6 class="fw-bold">Buteurs:</h6>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($goals as $goal): ?>
                                                    <span class="badge bg-<?= $goal['team'] === 'home' ? 'primary' : 'danger' ?> mb-1">
                                                        <?= htmlspecialchars($goal['player']) ?> (<?= $goal['minute'] ?>')
                                                    </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Formulaire d'ajout de but -->
                                            <form method="post" class="mb-3">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="row g-2">
                                                    <div class="col-md-5">
                                                        <select name="team" class="form-select form-select-sm" required>
                                                            <option value="">Équipe</option>
                                                            <option value="home"><?= htmlspecialchars($match['team_home']) ?></option>
                                                            <option value="away"><?= htmlspecialchars($match['team_away']) ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" name="player" class="form-control form-control-sm" placeholder="Joueur" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" name="minute" class="form-control" placeholder="Min" min="1" max="120" required>
                                                            <span class="input-group-text">'</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <button type="submit" name="add_goal" class="btn btn-success btn-sm w-100">
                                                            <i class="fas fa-futbol me-1"></i> Ajouter but
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                            <!-- Formulaire d'ajout de carton -->
                                            <form method="post">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <select name="card_type" class="form-select form-select-sm" required>
                                                            <option value="">Type</option>
                                                            <option value="yellow">Jaune</option>
                                                            <option value="red">Rouge</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" name="player" class="form-control form-control-sm" placeholder="Joueur" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" name="minute" class="form-control" placeholder="Min" min="1" max="120" required>
                                                            <span class="input-group-text">'</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" name="add_card" class="btn btn-warning btn-sm w-100">
                                                            <i class="fas fa-card me-1"></i> Ajouter
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="card-footer text-muted small">
                                            <?= htmlspecialchars($match['venue']) ?> - <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Matchs récemment terminés -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success bg-gradient text-white">
                        <h5 class="mb-0"><i class="fas fa-flag-checkered me-2"></i>Matchs récemment terminés</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Récupérer les 5 derniers matchs terminés avec les logos des équipes
                        try {
                            $finishedMatches = $pdo->query("
                                SELECT m.*, 
                                       t1.team_name as team_home, 
                                       t2.team_name as team_away,
                                       t1.logo as home_logo,
                                       t2.logo as away_logo
                                FROM matches m
                                LEFT JOIN teams t1 ON m.team_home = t1.team_name
                                LEFT JOIN teams t2 ON m.team_away = t2.team_name
                                WHERE m.status = 'completed'
                                ORDER BY m.match_date DESC, m.match_time DESC
                                LIMIT 5
                            ")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            error_log("Error fetching finished matches: " . $e->getMessage());
                            $finishedMatches = [];
                        }
                        ?>

                        <?php if (empty($finishedMatches)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Aucun match terminé récemment
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Compétition</th>
                                            <th>Équipe à domicile</th>
                                            <th>Score</th>
                                            <th>Équipe à l'extérieur</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($finishedMatches as $match): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($match['match_date'])) ?></td>
                                            <td><?= htmlspecialchars($match['competition']) ?></td>
                                            <td class="text-end">
                                                <?php 
                                                // Utiliser le chemin du logo depuis la base de données ou un logo par défaut
                                                $homeLogo = !empty($match['home_logo']) ? "../" . $match['home_logo'] : "../assets/img/teams/default.png";
                                                ?>
                                                <img src="<?= $homeLogo ?>" 
                                                     alt="<?= htmlspecialchars($match['team_home']) ?>" 
                                                     width="24" 
                                                     height="24"
                                                     class="me-2"
                                                     style="object-fit: contain;"
                                                     onerror="this.src='../assets/img/teams/default.png'">
                                                <?= htmlspecialchars($match['team_home']) ?>
                                            </td>
                                            <td class="text-center fw-bold">
                                                <?= $match['score_home'] ?? '0' ?> - <?= $match['score_away'] ?? '0' ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // Utiliser le chemin du logo depuis la base de données ou un logo par défaut
                                                $awayLogo = !empty($match['away_logo']) ? "../" . $match['away_logo'] : "../assets/img/teams/default.png";
                                                ?>
                                                <img src="<?= $awayLogo ?>" 
                                                     alt="<?= htmlspecialchars($match['team_away']) ?>" 
                                                     width="24" 
                                                     height="24"
                                                     class="me-2"
                                                     style="object-fit: contain;"
                                                     onerror="this.src='../assets/img/teams/default.png'">
                                                <?= htmlspecialchars($match['team_away']) ?>
                                            </td>
                                            <td>
                                                <a href="match_details.php?id=<?= $match['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-end mt-3">
                                <a href="matches.php?status=completed" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-list me-1"></i> Voir tous les matchs terminés
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Périodes d'Inscription -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Périodes d'Inscription</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch all registration periods
                        try {
                            $registrationPeriods = $pdo->query("SELECT * FROM registration_periods ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            error_log("Error fetching registration periods: " . $e->getMessage());
                            $registrationPeriods = [];
                        }
                        ?>

                        <!-- Form to add/edit registration period -->
                        <form method="post" class="mb-4">
                            <input type="hidden" name="period_id" id="period_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Date de début</label>
                                    <input type="datetime-local" name="start_date" id="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">Date de fin</label>
                                    <input type="datetime-local" name="end_date" id="end_date" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label for="closed_message" class="form-label">Message lorsque fermé</label>
                                    <textarea name="closed_message" id="closed_message" class="form-control" rows="3" placeholder="Ex: Les inscriptions sont fermées. La prochaine période d'inscription débutera le..."></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active">
                                        <label class="form-check-label" for="is_active">Activer cette période</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                <button type="submit" name="add_period" id="add_period" class="btn btn-primary">Ajouter</button>
                                <button type="submit" name="update_period" id="update_period" class="btn btn-primary d-none">Mettre à jour</button>
                                <button type="button" id="reset_period_form" class="btn btn-outline-secondary d-none">Annuler</button>
                            </div>
                        </form>

                        <!-- Liste des périodes existantes -->
                        <?php if (!empty($registrationPeriods)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Période</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrationPeriods as $period): ?>
                                        <tr>
                                            <td>
                                                <strong><?= date('d/m/Y H:i', strtotime($period['start_date'])) ?></strong> au 
                                                <strong><?= date('d/m/Y H:i', strtotime($period['end_date'])) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($period['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-period"
                                                        data-id="<?= $period['id'] ?>"
                                                        data-start-date="<?= date('Y-m-d\TH:i', strtotime($period['start_date'])) ?>"
                                                        data-end-date="<?= date('Y-m-d\TH:i', strtotime($period['end_date'])) ?>"
                                                        data-closed-message="<?= htmlspecialchars($period['closed_message']) ?>"
                                                        data-is-active="<?= $period['is_active'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                                                    <button type="submit" name="delete_period" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette période ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Aucune période d'inscription configurée
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Gestion du thème
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Vérifier le thème sauvegardé ou utiliser le préféré du système
    const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    html.setAttribute('data-bs-theme', savedTheme);
    
    themeToggle.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Animation de transition de thème
        html.style.transition = 'background-color 0.3s, color 0.3s';
        
        // Déclencher le reflow pour forcer l'animation
        void html.offsetWidth;
        
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Mettre à jour l'icône du bouton
        const icon = themeToggle.querySelector('i');
        icon.className = newTheme === 'dark' ? 'fas fa-sun me-1' : 'fas fa-moon me-1';
        
        // Notifier l'utilisateur du changement de thème
        const themeName = newTheme === 'dark' ? 'sombre' : 'clair';
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        Toast.fire({
            icon: 'success',
            title: `Thème ${themeName} activé`
        });
    });
    
    // Mettre à jour l'icône du bouton au chargement
    document.addEventListener('DOMContentLoaded', () => {
        const currentTheme = html.getAttribute('data-bs-theme');
        const icon = themeToggle.querySelector('i');
        icon.className = currentTheme === 'dark' ? 'fas fa-sun me-1' : 'fas fa-moon me-1';
        
        // Initialisation des tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Fonction pour filtrer les matchs
    function filterMatches() {
        const searchTerm = document.getElementById('matchSearch').value.toLowerCase();
        const competitionFilter = document.getElementById('competitionFilter').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const matches = document.querySelectorAll('.match-card');
        let visibleCount = 0;
        
        matches.forEach(match => {
            const matchText = match.textContent.toLowerCase();
            const matchCompetition = match.getAttribute('data-competition').toLowerCase();
            const matchStatus = match.getAttribute('data-status');
            
            const matchesSearch = searchTerm === '' || matchText.includes(searchTerm);
            const matchesCompetition = competitionFilter === '' || matchCompetition.includes(competitionFilter);
            const matchesStatus = statusFilter === '' || matchStatus === statusFilter;
            
            if (matchesSearch && matchesCompetition && matchesStatus) {
                match.style.display = '';
                visibleCount++;
            } else {
                match.style.display = 'none';
            }
        });
        
        // Afficher/masquer le message "Aucun résultat"
        const noMatchesMessage = document.getElementById('noMatchesMessage');
        if (visibleCount === 0) {
            noMatchesMessage.classList.remove('d-none');
        } else {
            noMatchesMessage.classList.add('d-none');
        }
    }
    
    // Gestion des timers des matchs en cours
    function updateTimers() {
        document.querySelectorAll('.match-card').forEach(card => {
            const matchId = card.getAttribute('data-match-id');
            const timerElement = document.getElementById(`timer-${matchId}`);
            
            if (!timerElement) return;
            
            // Simuler la mise à jour du timer (dans une vraie application, cela serait géré par le serveur)
            const currentTime = timerElement.textContent;
            if (currentTime.includes(':')) {
                const [minutes, seconds] = currentTime.split(':').map(Number);
                let newSeconds = seconds + 1;
                let newMinutes = minutes;
                
                if (newSeconds >= 60) {
                    newSeconds = 0;
                    newMinutes++;
                }
                
                timerElement.textContent = `${newMinutes.toString().padStart(2, '0')}:${newSeconds.toString().padStart(2, '0')}`;
            }
        });
    }
    
    // Mettre à jour les timers toutes les secondes
    setInterval(updateTimers, 1000);
    
    // Gestion du formulaire des périodes d'inscription
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle edit period button clicks
        document.querySelectorAll('.edit-period').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('period_id').value = this.dataset.id;
                document.getElementById('start_date').value = this.dataset.startDate;
                document.getElementById('end_date').value = this.dataset.endDate;
                document.getElementById('closed_message').value = this.dataset.closedMessage;
                document.getElementById('is_active').checked = this.dataset.isActive === '1';
                
                document.getElementById('add_period').classList.add('d-none');
                document.getElementById('update_period').classList.remove('d-none');
                document.getElementById('reset_period_form').classList.remove('d-none');
                
                // Scroll to form
                document.querySelector('.card-header').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Reset period form button
        document.getElementById('reset_period_form').addEventListener('click', function() {
            document.querySelector('form').reset();
            document.getElementById('period_id').value = '';
            document.getElementById('add_period').classList.remove('d-none');
            document.getElementById('update_period').classList.add('d-none');
            document.getElementById('reset_period_form').classList.add('d-none');
        });
    });
    </script>
</body>
</html>
