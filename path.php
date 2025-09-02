<?php

// Function to determine base URL consistently
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Handle NHL PLAY project structure
    // On localhost: should be /nhl
    // On production: should be / (root)
    if ($host === 'localhost' || strpos($host, 'localhost') !== false) {
        // Localhost environment - always use /nhl
        return $protocol . $host . '/nhl';
    } else {
        // Production environment - use root
        return $protocol . $host;
    }
}

define('ROOT_PATH', realpath(dirname(__FILE__)));
define('BASE_URL', getBaseUrl());

$season = '20242025';
$lastSeason = '20242025';
$draftYear = '2025';
$draftYearLast = '2024';
$playoffs = false;
$seasonBreak = true;

// Ensure these variables are available globally
$GLOBALS['season'] = $season;
$GLOBALS['lastSeason'] = $lastSeason;
$GLOBALS['draftYear'] = $draftYear;
$GLOBALS['draftYearLast'] = $draftYearLast;
$GLOBALS['playoffs'] = $playoffs;
$GLOBALS['seasonBreak'] = $seasonBreak;