<?php
include_once '../path.php';
include_once '../includes/functions.php';
// Include team builder specific helper functions
include_once __DIR__ . '/../includes/functions/team-builder-functions.php';

// Only handle AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    exit('Bad Request');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_draft_players':
        getDraftPlayers();
        break;
    case 'get_round_players':
        getRoundPlayers();
        break;
    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        break;
}