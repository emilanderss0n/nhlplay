<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/player.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Get player ID from the request
$playerID = $_POST['player'] ?? null;

if (!$playerID) {
    send_error('No player ID provided', 400);
}

$isSkater = isset($_POST['isSkater']) ? $_POST['isSkater'] === 'true' : false;
$res = player_compute_advanced($playerID, $isSkater, $season ?? null);
send_success(['advancedStats' => $res['advancedStats']]);