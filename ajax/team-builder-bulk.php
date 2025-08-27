<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/team-builder.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Only handle AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    exit('Bad Request');
}

// Get team IDs from POST data
$teamIds = $_POST['team_ids'] ?? [];
if (!is_array($teamIds) || empty($teamIds)) {
    send_error('No team IDs provided', 400);
}

// Validate and sanitize team IDs - limit to 32 teams max for safety
$teamIds = array_slice(array_filter(array_map('intval', $teamIds)), 0, 32);
if (empty($teamIds)) {
    send_error('Invalid team IDs', 400);
}

$result = [];
$errors = [];

// Load roster data for all requested teams
foreach ($teamIds as $teamId) {
    try {
        $html = teambuilder_get_team_roster_html($teamId, $season);
        if (!$html) {
            $errors[] = "Failed to load roster for team: $teamId";
            continue;
        }
        $teamAbbrev = idToTeamAbbrev($teamId);
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
if (empty($result) && !empty($errors)) {
    send_error('Failed to load any team rosters', 500);
} else {
    $response = ['teams' => $result];
    if (!empty($errors)) $response['warnings'] = $errors;
    send_success($response, 200);
}
?>
