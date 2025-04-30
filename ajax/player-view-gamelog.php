<?php
include_once '../path.php';
include_once '../includes/functions.php';
$playerID = $_POST['player'];
$seasonSelection = $_POST['season-selection'];
$seasonType = $_POST['season-type'];

$ApiUrl = 'https://api-web.nhle.com/v1/player/'. $playerID .'/game-log/'. $seasonSelection .'/'. $seasonType;
$curl = curlInit($ApiUrl);
$playerGameLog = json_decode($curl);
?>