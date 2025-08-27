<?php
// Player page template - expects $pageContext or $app['context'] to be set
$pageContext = $pageContext ?? ($app['context'] ?? []);
$playerId = $pageContext['player_id'] ?? null;

if (!$playerId && isset($_GET['playerId'])) {
    $playerId = $_GET['playerId'];
}

if (!$playerId) {
    http_response_code(404);
    echo '<h1>Player not found</h1>';
    return;
}

// Make player id available as expected by ajax/player-view.php
if (!defined('IN_PAGE')) define('IN_PAGE', true);
$_POST['player'] = $playerId;
include __DIR__ . '/../ajax/player-view.php';
