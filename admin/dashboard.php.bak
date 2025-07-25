<?php
require __DIR__ . '/admin_header.php';

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
        // Ensure UTF-8 encoding
        $actionDetails = mb_convert_encoding((string)$actionDetails, 'UTF-8', 'auto');
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
    $currentMatches = $pdo->query("SELECT * FROM matches WHERE status = 'ongoing' ORDER BY match_date DESC")->fetchAll(PDO::FETCH_ASSOC);
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
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            if (!$matchId) {
                error_log("Invalid match_id for finalize_match: $matchId");
                $_SESSION['error'] = "ID de match invalide.";
                header("Location: dashboard.php");
                exit();
            }

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
                error_log("Invalid match data for finalization: match_id=$matchId, data=" . json_encode($match));
                $_SESSION['error'] = "Données du match incomplètes pour la finalisation.";
                header("Location: dashboard.php");
                exit();
            }

            $oldStatus = $match['status'];
            $stmt = $pdo->prepare("UPDATE matches SET status = 'completed', timer_start = NULL, timer_status = 'ended' WHERE id = ?");
            $stmt->execute([$matchId]);

            // Log the action
            logAction(
                $pdo,
                $matchId,
                'FINALIZE_MATCH',
                "Match finalisé",
                ['status' => $oldStatus],
                ['status' => 'completed']
            );

            // Update standings
            updateStandings($matchId, $pdo);

            $pdo->commit();
            $_SESSION['message'] = "Match finalisé avec succès !";
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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - UJEM</title>
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
                <h2 class="h3 mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                
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

                <div class="row">
                    <!-- Quick Stats -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Matchs en cours</h6>
                                        <h2 class="mb-0"><?= count($currentMatches) ?></h2>
                                    </div>
                                    <i class="fas fa-running fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Matchs terminés</h6>
                                        <h2 class="mb-0"><?= $pdo->query("SELECT COUNT(*) FROM matches WHERE status = 'completed'")->fetchColumn() ?></h2>
                                    </div>
                                    <i class="fas fa-flag-checkered fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Équipes</h6>
                                        <h2 class="mb-0"><?= $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn() ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Joueurs</h6>
                                        <h2 class="mb-0"><?= $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn() ?></h2>
                                    </div>
                                    <i class="fas fa-user fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ongoing Matches -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-running me-2"></i>Matchs en cours</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($currentMatches)): ?>
                            <div class="alert alert-info">Aucun match en cours actuellement</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($currentMatches as $match): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card match-card h-100">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?>
                                                <span class="badge bg-primary float-end"><?= htmlspecialchars($match['phase']) ?></span>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="text-center">
                                                    <img src="../assets/img/teams/<?= htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_home']))) ?>.png" 
                                                         alt="<?= htmlspecialchars($match['team_home']) ?>" width="50" onerror="this.src='../assets/img/teams/default.png'">
                                                    <div class="fw-bold mt-2"><?= htmlspecialchars($match['team_home']) ?></div>
                                                </div>
                                                
                                                <div class="text-center mx-3">
                                                    <div class="display-4 fw-bold">
                                                        <?= $match['score_home'] ?? '0' ?> - <?= $match['score_away'] ?? '0' ?>
                                                    </div>
                                                    <small class="text-muted"><?= date('H:i', strtotime($match['match_time'])) ?></small>
                                                    <div class="timer-display mt-2" id="timer-<?= $match['id'] ?>">
                                                        <?php
                                                        if ($match['timer_status'] === 'ended') {
                                                            echo 'Terminé';
                                                        } else {
                                                            $elapsed = $match['timer_elapsed'];
                                                            if ($match['timer_start'] && !$match['timer_paused']) {
                                                                $elapsed += (strtotime('now') - strtotime($match['timer_start']));
                                                            }
                                                            $displayMinutes = floor($elapsed / 60);
                                                            if ($match['timer_status'] === 'second_half') {
                                                                $firstHalfMinutes = floor(($match['first_half_duration'] ?? 0) / 60);
                                                                $displayMinutes += $firstHalfMinutes;
                                                            }
                                                            $displaySeconds = $elapsed % 60;
                                                            echo sprintf('%02d:%02d', $displayMinutes, $displaySeconds);
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center">
                                                    <img src="../assets/img/teams/<?= htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_away']))) ?>.png" 
                                                         alt="<?= htmlspecialchars($match['team_away']) ?>" width="50" onerror="this.src='../assets/img/teams/default.png'">
                                                    <div class="fw-bold mt-2"><?= htmlspecialchars($match['team_away']) ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <!-- Set match duration -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <div class="input-group input-group-sm" style="width: 200px;">
<input type="number" name="match_duration" class="form-control" 
       placeholder="Durée (min)" value="<?= ($match['timer_duration'] ?? 3000) / 60 ?>" min="1" max="120">
                                                        <button type="submit" name="set_duration" class="btn btn-primary">
                                                            <i class="fas fa-clock me-1"></i> Durée
                                                        </button>
                                                    </div>
                                                </form>
                                                                                                
                                                <!-- Set first half extra time -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <input type="number" name="first_half_extra" class="form-control form-control-sm d-inline" style="width:80px;display:inline-block"
                                                        placeholder="+min" min="0" max="15" value="<?= (int)($match['first_half_extra'] ?? 0)/60 ?>">
                                                    <button type="submit" name="set_first_half_extra" class="btn btn-secondary btn-sm">+Temps add. 1ère</button>
                                                </form>

                                                <!-- Set second half extra time -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <input type="number" name="second_half_extra" class="form-control form-control-sm d-inline" style="width:80px;display:inline-block"
                                                        placeholder="+min" min="0" max="15" value="<?= (int)($match['second_half_extra'] ?? 0)/60 ?>">
                                                    <button type="submit" name="set_second_half_extra" class="btn btn-secondary btn-sm">+Temps add. 2ème</button>
                                                </form>

                                                <!-- Start first half -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <button type="submit" name="start_first_half" class="btn btn-success btn-sm"
                                                            <?= ($match['timer_status'] !== 'not_started') ? 'disabled' : '' ?>>
                                                        <i class="fas fa-play me-1"></i> 1ère mi-temps
                                                    </button>
                                                </form>

                                                <!-- Start second half -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <button type="submit" name="start_second_half" class="btn btn-info btn-sm"
                                                        <?= ($match['timer_status'] !== 'half_time') ? 'disabled' : '' ?>>
                                                        <i class="fas fa-forward me-1"></i> 2ème mi-temps
                                                    </button>
                                                </form>

                                                <!-- Stop timer -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                    <button type="submit" name="stop_timer" class="btn btn-danger btn-sm"
                                                            <?= !in_array($match['timer_status'], ['first_half', 'second_half']) ? 'disabled' : '' ?>>
                                                        <i class="fas fa-stop me-1"></i> Arrêter
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Update score form -->
                                            <form method="post" class="mb-3">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-5">
                                                        <label class="form-label">Score <?= htmlspecialchars($match['team_home']) ?></label>
                                                        <input type="number" name="score_home" class="form-control" value="<?= $match['score_home'] ?? 0 ?>" min="0" required>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label">Score <?= htmlspecialchars($match['team_away']) ?></label>
                                                        <input type="number" name="score_away" class="form-control" value="<?= $match['score_away'] ?? 0 ?>" min="0" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" name="update_score" class="btn btn-primary btn-sm w-100">
                                                            <i class="fas fa-sync me-1"></i> Mettre à jour
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                            <!-- List goals -->
                                            <?php 
                                            $goals = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
                                            $goals->execute([$match['id']]);
                                            $goals = $goals->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <?php if (!empty($goals)): ?>
                                            <div class="mb-3">
                                                <h6 class="fw-bold">Buteurs:</h6>
                                                <?php foreach ($goals as $goal): ?>
                                                <span class="badge bg-<?= $goal['team'] === 'home' ? 'primary' : 'danger' ?> goal-badge">
                                                    <?= htmlspecialchars($goal['player']) ?> (<?= $goal['minute'] ?>')
                                                </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Add goal form -->
                                            <form method="post" class="mb-3">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-4">
                                                        <select name="team" class="form-select" required>
                                                            <option value="">Sélectionner équipe</option>
                                                            <option value="home"><?= htmlspecialchars($match['team_home']) ?></option>
                                                            <option value="away"><?= htmlspecialchars($match['team_away']) ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" name="player" class="form-control" placeholder="Nom du buteur" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="number" name="minute" class="form-control" placeholder="Minute" min="1" max="120" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" name="add_goal" class="btn btn-success btn-sm w-100">
                                                            <i class="fas fa-plus me-1"></i> Ajouter but
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                            <!-- List cards -->
                                            <?php 
                                            $cards = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
                                            $cards->execute([$match['id']]);
                                            $cards = $cards->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <?php if (!empty($cards)): ?>
                                            <div class="mb-3">
                                                <h6 class="fw-bold">Cartons:</h6>
                                                <?php foreach ($cards as $card): ?>
                                                <span class="badge bg-<?= $card['card_type'] === 'yellow' ? 'warning' : ($card['card_type'] === 'red' ? 'danger' : 'info') ?> card-badge">
                                                    <?= htmlspecialchars($card['player']) ?> (<?= $card['minute'] ?>' - <?= ucfirst($card['card_type']) ?>)
                                                </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Add card form -->
                                            <form method="post" class="mb-3">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-3">
                                                        <select name="team" class="form-select" required>
                                                            <option value="">Sélectionner équipe</option>
                                                            <option value="home"><?= htmlspecialchars($match['team_home']) ?></option>
                                                            <option value="away"><?= htmlspecialchars($match['team_away']) ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="text" name="player" class="form-control" placeholder="Nom du joueur" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <select name="card_type" class="form-select" required>
                                                            <option value="">Type</option>
                                                            <option value="yellow">Jaune</option>
                                                            <option value="red">Rouge</option>
                                                            <option value="blue">Bleu</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="number" name="minute" class="form-control" placeholder="Minute" min="1" max="120" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" name="add_card" class="btn btn-warning btn-sm w-100">
                                                            <i class="fas fa-id-card me-1"></i> Ajouter carton
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                            <!-- Finalize match -->
                                            <form method="post">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <div class="d-grid">
                                                    <button type="submit" name="finalize_match" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-flag-checkered me-1"></i> Finaliser le match
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="card-footer text-muted small">
                                            <?= htmlspecialchars($match['venue']) ?> - <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registration Periods Management -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
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
                                <button type="button" id="reset_period_form" class="btn btn-secondary d-none">Annuler</button>
                            </div>
                        </form>

                        <?php if (!empty($registrationPeriods)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Période</th>
                                            <th>Statut</th>
                                            <th>Message</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrationPeriods as $period): ?>
                                        <tr>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($period['start_date'])) ?> - 
                                                <?= date('d/m/Y H:i', strtotime($period['end_date'])) ?>
                                            </td>
                                            <td>
                                                <?php if ($period['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($period['closed_message'] ?? '') ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-period" 
                                                        data-id="<?= $period['id'] ?>"
                                                        data-start-date="<?= date('Y-m-d\TH:i', strtotime($period['start_date'])) ?>"
                                                        data-end-date="<?= date('Y-m-d\TH:i', strtotime($period['end_date'])) ?>"
                                                        data-closed-message="<?= htmlspecialchars($period['closed_message'] ?? '') ?>"
                                                        data-is-active="<?= $period['is_active'] ? '1' : '0' ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette période d\'inscription?');">
                                                    <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                                                    <button type="submit" name="delete_period" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Aucune période d'inscription configurée</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Completed Matches -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Derniers matchs terminés</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        try {
                            $completedMatches = $pdo->query("SELECT * FROM matches 
                                                            WHERE status = 'completed' 
                                                            ORDER BY match_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            error_log("Error fetching completed matches: " . $e->getMessage());
                            $completedMatches = [];
                        }
                        ?>
                        
                        <?php if (empty($completedMatches)): ?>
                            <div class="alert alert-info">Aucun match terminé récemment</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Match</th>
                                            <th>Score</th>
                                            <th>Phase</th>
                                            <th>Terrain</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completedMatches as $match): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($match['match_date'])) ?></td>
                                            <td>
                                                <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?>
                                            </td>
                                            <td class="fw-bold"><?= $match['score_home'] ?> - <?= $match['score_away'] ?></td>
                                            <td><?= htmlspecialchars($match['phase']) ?></td>
                                            <td><?= htmlspecialchars($match['venue']) ?></td>
                                            <td>
                                                <a href="match_details.php?id=<?= $match['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Détails
                                                </a>
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
    // Initialize tooltips
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

    // Manage timers
    var stoppedTimers = new Set();

    <?php foreach ($currentMatches as $match): ?>
    <?php if ($match['timer_start'] && !$match['timer_paused'] && in_array($match['timer_status'], ['first_half', 'second_half'])): ?>
    (function() {
        var matchId = <?= $match['id'] ?>;
        if (stoppedTimers.has(matchId)) return;

        var timerElement = document.getElementById('timer-<?= $match['id'] ?>');
        var startTime = new Date('<?= $match['timer_start'] ?>').getTime() / 1000;
        var elapsed = <?= $match['timer_elapsed'] ?>;
        var duration = <?= $match['timer_duration'] ?: 5400 ?>;
        var halfDuration = Math.floor(duration / 2);
        var additionalTime = <?= $match['timer_status'] === 'first_half' ? (isset($match['first_half_extra']) ? (int)$match['first_half_extra'] : 0) : (isset($match['second_half_extra']) ? (int)$match['second_half_extra'] : 0) ?>;
        var firstHalfDuration = <?= $match['first_half_duration'] ?? 0 ?>;
        var isSecondHalf = <?= $match['timer_status'] === 'second_half' ? 'true' : 'false' ?>;
        var timerStatus = '<?= $match['timer_status'] ?>';
        var intervalId = null;

        function stopTimer() {
            if (stoppedTimers.has(matchId)) return;
            stoppedTimers.add(matchId);
            if (intervalId) clearInterval(intervalId);
            fetch('dashboard.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'match_id=' + matchId + '&stop_timer=1'
            }).then(() => {
                if (timerElement) {
                    timerElement.textContent = isSecondHalf ? 'Terminé' : 'Mi-temps';
                }
                setTimeout(() => location.reload(), 500);
            }).catch(error => {
                console.error('Error stopping timer:', error);
            });
        }

        function updateTimer() {
            if (stoppedTimers.has(matchId)) return;

            // Display "Terminé" for ended matches
            if (timerStatus === 'ended') {
                timerElement.textContent = 'Terminé';
                return;
            }

            var now = Math.floor(Date.now() / 1000);
            var totalSeconds = elapsed + (now - startTime);
            if (totalSeconds < 0) totalSeconds = 0;

            // Cap elapsed time
            var limit = halfDuration + additionalTime;
            if (totalSeconds > limit) {
                totalSeconds = limit;
                stopTimer();
                return;
            }

            // Calculate display time
            var minutes = Math.floor(totalSeconds / 60);
            var seconds = Math.floor(totalSeconds % 60);

            // Adjust for second half
            if (isSecondHalf) {
                var firstHalfMinutes = Math.floor(firstHalfDuration / 60);
                minutes += firstHalfMinutes;
            }

            // Update display
            timerElement.textContent =
                (minutes < 10 ? '0' : '') + minutes + ':' +
                (seconds < 10 ? '0' : '') + seconds;
        }

        updateTimer();
        intervalId = setInterval(updateTimer, 1000);
    })();
    <?php endif; ?>
    <?php endforeach; ?>
});
</script>
</body>
</html>
