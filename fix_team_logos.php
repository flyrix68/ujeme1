&lt;?php
require 'includes/db-config.php';

function verifyTeamLogos($pdo) {
    // Get all teams from database
    $teams = $pdo->query("SELECT id, team_name, logo_path FROM teams")->fetchAll(PDO::FETCH_ASSOC);
    
    $report = [];
    $basePath = __DIR__.'/../uploads/logos/';
    $defaultLogo = 'default.png';
    
    foreach ($teams as $team) {
        $status = [
            'team_id' => $team['id'],
            'team_name' => $team['team_name'],
            'current_logo' => $team['logo_path'] ?? 'none',
            'status' => 'ok'
        ];
        
        // Check if logo exists in uploads/logos/
        $logoPath = $basePath . ($team['logo_path'] ?? '');
        $logoExists = file_exists($logoPath) && is_file($logoPath);
        
        if (!$logoExists) {
            // Try to find logo by cleaning team name
            $cleanedName = strtolower(preg_replace('/[^a-z0-9]/', '-', $team['team_name']));
            $possibleFiles = [
                $cleanedName.'.png',
                $cleanedName.'.jpg',
                $cleanedName.'.jpeg'
            ];
            
            $found = false;
            foreach ($possibleFiles as $file) {
                if (file_exists($basePath.$file)) {
                    // Update database with found logo
                    $stmt = $pdo->prepare("UPDATE teams SET logo_path = ? WHERE id = ?");
                    $stmt->execute([$file, $team['id']]);
                    $status['new_logo'] = $file;
                    $status['status'] = 'fixed';
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $status['status'] = 'missing';
            }
        }
        
        $report[] = $status;
    }
    
    return $report;
}

try {
    $pdo = DatabaseConfig::getConnection();
    $report = verifyTeamLogos($pdo);
    
    // Generate HTML report
    $html = '&lt;!DOCTYPE html&gt;
    &lt;html&gt;
    &lt;head&gt;
        &lt;title&gt;Team Logo Verification Report&lt;/title&gt;
        &lt;style&gt;
            table { border-collapse: collapse; width: 100%; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .ok { color: green; }
            .fixed { color: blue; }
            .missing { color: red; }
        &lt;/style&gt;
    &lt;/head&gt;
    &lt;body&gt;
        &lt;h1&gt;Team Logo Verification Report&lt;/h1&gt;
        &lt;table&gt;
            &lt;tr&gt;
                &lt;th&gt;Team ID&lt;/th&gt;
                &lt;th&gt;Team Name&lt;/th&gt;
                &lt;th&gt;Current Logo&lt;/th&gt;
                &lt;th&gt;Status&lt;/th&gt;
                &lt;th&gt;New Logo&lt;/th&gt;
            &lt;/tr&gt;';

    foreach ($report as $entry) {
        $html .= "&lt;tr&gt;
            &lt;td&gt;{$entry['team_id']}&lt;/td&gt;
            &lt;td&gt;{$entry['team_name']}&lt;/td&gt;
            &lt;td&gt;{$entry['current_logo']}&lt;/td&gt;
            &lt;td class='{$entry['status']}'&gt;{$entry['status']}&lt;/td&gt;
            &lt;td&gt;" . ($entry['new_logo'] ?? '') . "&lt;/td&gt;
        &lt;/tr&gt;";
    }

    $html .= '&lt;/table&gt;
    &lt;/body&gt;
    &lt;/html&gt;';

    file_put_contents('logo_verification_report.html', $html);
    echo "Logo verification complete. Report saved to logo_verification_report.html\n";
