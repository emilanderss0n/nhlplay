<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/player.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Get player ID and data from the request
$playerID = $_POST['player'] ?? null;
$playerData = $_POST['playerData'] ?? null;

if (!$playerID || !$playerData) {
    send_error('No player ID or data provided', 400);
}

// Delegate radar computation to controller
$radar = player_compute_radar($playerID, $playerData, $season ?? null);
send_success(['radar' => $radar], 200);
