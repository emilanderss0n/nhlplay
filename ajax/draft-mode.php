<?php
include_once '../path.php';
include_once '../includes/functions.php';
// Include team builder specific helper functions
include_once __DIR__ . '/../includes/functions/team-builder-functions.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Only handle AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    exit('Bad Request');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_draft_players':
        $result = getDraftPlayers();
        if (!$result || (isset($result['error']))) {
            send_error($result['error'] ?? 'Failed to fetch draft players', 500);
        } else {
            send_success(['players' => $result['players'], 'position' => $result['position'], 'round' => $result['round']], 200);
        }
        break;
    case 'get_round_players':
        $result = getRoundPlayers();
        if (!$result || (isset($result['error']))) {
            send_error($result['error'] ?? 'Failed to fetch round players', 500);
        } else {
            send_success(['players' => $result['players'], 'position' => $result['position'], 'round' => $result['round']], 200);
        }
        break;
    default:
        send_error('Invalid action', 400);
        break;
}