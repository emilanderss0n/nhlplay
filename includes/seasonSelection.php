<?php
include_once '../path.php';
include_once '../includes/functions.php';

$ApiUrl = "https://api-web.nhle.com/v1/season";
$curl = curlInit($ApiUrl);
$seasons = json_decode($curl);

// Get the last 10 seasons and reverse the order
$lastSeasons = array_slice($seasons, -10);
$lastSeasons = array_reverse($lastSeasons);

foreach ($lastSeasons as $season) {
    echo '<a href="javascript:void(0)" class="season-select-link" data-season="'. $season .'">'. $season .'</a>';
}