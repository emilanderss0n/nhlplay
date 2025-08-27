<?php
// filepath: f:\wamp64\www\nhl\ajax\team-reddit-feed.php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/team.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    exit('Direct access not allowed');
}

// Get requested subreddit (required)
if (!isset($_GET['subreddit']) || empty($_GET['subreddit'])) {
    send_error('Subreddit parameter required', 400);
}

$subreddit = $_GET['subreddit'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
$result = team_fetch_reddit_posts($subreddit, $limit);
send_success(['posts' => $result], 200);
