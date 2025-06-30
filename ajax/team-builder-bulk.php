<?php
include_once '../path.php';
include_once '../includes/functions.php';

// Only handle AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    exit('Bad Request');
}

// Get team IDs from POST data
$teamIds = $_POST['team_ids'] ?? [];
if (!is_array($teamIds) || empty($teamIds)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No team IDs provided']);
    exit;
}

// Validate and sanitize team IDs - limit to 32 teams max for safety
$teamIds = array_slice(array_filter(array_map('intval', $teamIds)), 0, 32);
if (empty($teamIds)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid team IDs']);
    exit;
}

$result = [];
$errors = [];

// Load roster data for all requested teams
foreach ($teamIds as $teamId) {
    try {
        $teamAbbrev = idToTeamAbbrev($teamId);
        if (!$teamAbbrev) {
            $errors[] = "Invalid team ID: $teamId";
            continue;
        }
        
        $teamRosterInfo = getTeamRosterInfo($teamAbbrev, $season);
        if (!$teamRosterInfo || isset($teamRosterInfo->error)) {
            $errors[] = "Failed to load roster for team: $teamId";
            continue;
        }
        
        // Start output buffering to capture the rendered roster
        ob_start();
        ?>
        <div class="team-roster-data" data-team-id="<?= $teamId ?>">
            <div class="forwards-data">
                <?php renderTeamBuilderRoster($teamRosterInfo, $teamId, 'forwards'); ?>
            </div>
            <div class="defensemen-data">
                <?php renderTeamBuilderRoster($teamRosterInfo, $teamId, 'defensemen'); ?>
            </div>
            <div class="goalies-data">
                <?php renderTeamBuilderRoster($teamRosterInfo, $teamId, 'goalies'); ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        $result[$teamId] = [
            'teamId' => $teamId,
            'teamAbbrev' => $teamAbbrev,
            'html' => $html
        ];
    } catch (Exception $e) {
        error_log("Error loading team $teamId: " . $e->getMessage());
        $errors[] = "Error loading team $teamId: " . $e->getMessage();
        continue;
    }
}

// Return JSON response
header('Content-Type: application/json');

if (empty($result) && !empty($errors)) {
    echo json_encode(['error' => 'Failed to load any team rosters', 'details' => $errors]);
} else {
    $response = $result;
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }
    echo json_encode($response);
}
?>
