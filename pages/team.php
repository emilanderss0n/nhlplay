<?php
// Team page template - expects $pageContext or $app['context'] to be set
$pageContext = $pageContext ?? ($app['context'] ?? []);
$teamAbbr = $pageContext['team_abbr'] ?? null;
$teamId = $pageContext['team_id'] ?? null;

if (!$teamId && $teamAbbr) {
    $teamId = abbrevToTeamId($teamAbbr);
}

if (!$teamId) {
    http_response_code(404);
    echo '<h1>Team not found</h1>';
    return;
}

// Defer to existing ajax/team-view.php logic but included in a page context
// Make sure ajax file knows it's embedded
if (!defined('IN_PAGE')) define('IN_PAGE', true);
// Set expected variables used by ajax file
$_GET['team_abbr'] = $teamAbbr ?? null;
$_GET['active_team'] = $teamId;
include __DIR__ . '/../ajax/team-view.php';
