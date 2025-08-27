<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/player.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Get player ID and data from the request
$playerID = $_POST['player'] ?? null;
$playerData = $_POST['playerData'] ?? null;

if (!$playerID) {
    send_error('No player ID provided', 400);
}

// Try to decode playerData if it's a JSON string
if ($playerData) {
    $decoded = json_decode($playerData);
    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents($logFile, date('c') . " - playerData JSON decode error: " . json_last_error_msg() . "\n", FILE_APPEND);
    }
} else {
    $decoded = null;
    file_put_contents($logFile, date('c') . " - playerData empty\n", FILE_APPEND);
}

// If decoded player data is missing or lacks essential fields, fetch landing server-side
if (!$decoded || !isset($decoded->position) || !isset($decoded->featuredStats)) {
    $landing = player_fetch_landing($playerID);
    // If landing returned, use its people[0] object as playerData for computation
    if ($landing && isset($landing->people) && isset($landing->people[0])) {
        $decoded = $landing->people[0];
    }
}

// Delegate radar computation to controller (pass decoded object)
// Compute radar and return payload
$radar = player_compute_radar($playerID, $decoded ?? $playerData, $season ?? null);
send_success(['radar' => $radar], 200);
