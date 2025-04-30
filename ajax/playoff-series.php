<?php
include_once '../path.php';
include_once '../includes/functions.php';

if (!isset($_GET['season']) || !isset($_GET['series'])) {
    http_response_code(400);
    echo 'Missing required parameters';
    exit;
}

$season = $_GET['season'];
$seriesLetter = $_GET['series'];

$seriesData = getPlayoffSeriesGames($season, $seriesLetter);
echo renderPlayoffSeriesModal($seriesData);