<?php
// Scores page wrapper
if (!defined('IN_PAGE')) define('IN_PAGE', true);
$appContext = $app ?? $GLOBALS['app'] ?? null;
// include ajax/scores.php which will render content when embedded
include __DIR__ . '/../ajax/scores.php';
