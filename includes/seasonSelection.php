<?php
include_once '../path.php';
include_once '../includes/functions.php';

// Use the new NHL API utility
$ApiUrl = NHLApi::season();
$curl = curlInit($ApiUrl);
$seasons = json_decode($curl);

// Get the last 6 seasons and reverse the order
$lastSeasons = array_slice($seasons, -6);
$lastSeasons = array_reverse($lastSeasons);

foreach ($lastSeasons as $seasonId) {
    $isSelected = (isset($season) && $season == $seasonId) ? ' selected' : '';
    echo '<option class="season-select-option" value="'. $seasonId .'"' . $isSelected . '>'. $seasonId .'</option>';
}