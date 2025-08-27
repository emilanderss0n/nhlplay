<?php
include_once __DIR__ . '/../includes/controllers/ajax.php';
include_once __DIR__ . '/../includes/controllers/game.php';

$gameId = $_GET['gameId'] ?? '';
if (empty($gameId)) {
    send_error('Game ID required', 400);
}

$response = game_fetch_boxscore_json($gameId);
if ($response === null) {
    send_error('Failed to fetch data', 500);
}

// The boxscore response is already JSON; return as success with data property
send_success(['data' => json_decode($response)], 200);