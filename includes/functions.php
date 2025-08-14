<?php
// Include global data arrays
require_once __DIR__ . '/data/team-data.php';
require_once __DIR__ . '/data/position-data.php';
require_once __DIR__ . '/data/advanced-season-stats.php';

// Include function files by category
require_once __DIR__ . '/functions/nhl-api.php';  // NHL API utilities - loaded first
require_once __DIR__ . '/functions/api-functions.php';
require_once __DIR__ . '/functions/team-functions.php';
require_once __DIR__ . '/functions/player-functions.php';
require_once __DIR__ . '/functions/game-functions.php';
require_once __DIR__ . '/functions/injury-functions.php';
require_once __DIR__ . '/functions/stats-functions.php';
require_once __DIR__ . '/functions/utility-functions.php';
require_once __DIR__ . '/functions/team-builder-functions.php';
require_once __DIR__ . '/functions/trade-functions.php';