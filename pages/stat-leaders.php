<?php
// Stat leaders page wrapper
if (!defined('IN_PAGE')) define('IN_PAGE', true);
$appContext = $app ?? $GLOBALS['app'] ?? null;
include __DIR__ . '/../ajax/stat-leaders.php';
