<?php

// Function to determine base URL consistently
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseDir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $protocol . $host . ($baseDir ? '/' . $baseDir : '');
}

define('ROOT_PATH', realpath(dirname(__FILE__)));
define('BASE_URL', getBaseUrl());

$season = '20242025';
$lastSeason = '20242025';
$draftYear = '2025';
$draftYearLast = '2024';
$playoffs = false;
$seasonBreak = true;