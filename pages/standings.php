<?php
// Standings page wrapper - uses league, conference and divisions handlers as needed
if (!defined('IN_PAGE')) define('IN_PAGE', true);
$appContext = $app ?? $GLOBALS['app'] ?? null;
include __DIR__ . '/../ajax/standings-league.php';
