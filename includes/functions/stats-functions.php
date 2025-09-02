<?php
// Make sure BASE_URL is defined
if (!defined('BASE_URL')) {
    // If being called directly, include path.php
    include_once dirname(dirname(__DIR__)) . '/path.php';
}

function renderStatHolder($type, $category, $season, $playoffs, $loadOnDemand = false) {
    // Build API URL based on type and category
    $gameType = $playoffs ? 3 : 2;
    $ApiUrl = buildStatLeaderApiUrl($type, $category, $season, $gameType);

    // Handle caching with season-specific structure
    $baseCacheDir = dirname(__DIR__, 2) . '/cache/stat-leaders/';
    $seasonCacheDir = $baseCacheDir . $season . '/';
    $fileName = "{$type}_{$category}_" . ($playoffs ? 'playoffs' : 'regular');
    $cacheFile = $seasonCacheDir . $fileName . '.json';
    $cacheTime = 60 * 60; // 1 hour

    // Make sure cache directories exist
    if (!is_dir($baseCacheDir)) {
        mkdir($baseCacheDir, 0755, true);
    }
    if (!is_dir($seasonCacheDir)) {
        mkdir($seasonCacheDir, 0755, true);
    }

    // Use the shared fetchData helper which handles caching, fetching and fallbacks
    // fetchData expects ($apiUrl, $cacheFile, $cacheLifetime)
    $statPoints = fetchData($ApiUrl, $cacheFile, $cacheTime);

    // Handle error or missing data
    if (!$statPoints || !isset($statPoints->data) || empty($statPoints->data)) {
        return '<div class="error">No data available for this season</div>';
    }

    $displayRank = 0; // For displaying rank with T for ties
    $previousValue = null;

    // Build HTML output
    ob_start();
    
    // Process only first 10 players
    $playerCount = min(count($statPoints->data), 10);

    // Get first player for the holder
    $firstPlayer = $statPoints->data[0] ?? null;
    $firstPlayerHtml = '';
    if ($firstPlayer) {
        $playerId = $firstPlayer->player->id ?? null;
        $teamId = $firstPlayer->team->id ?? null;
        $logo = $firstPlayer->team->logos ?? null;
        $triCode = $firstPlayer->team->triCode ?? null;
        $positionCode = $firstPlayer->player->positionCode ?? null;
        $playerName = $firstPlayer->player->fullName ?? 'Unknown';
        $formattedStat = formatStatValue($firstPlayer, $category);
        $sweaterNumber = $firstPlayer->player->sweaterNumber ?? '';
        
        // Find the latest logo
        if (is_array($logo) && !empty($logo)) {
            $latestLogo = null;
            $latestEndSeason = 0;
            foreach ($logo as $logoEntry) {
                if (isset($logoEntry->endSeason) && isset($logoEntry->secureUrl)) {
                    if ((int)$logoEntry->endSeason > $latestEndSeason) {
                        $latestEndSeason = (int)$logoEntry->endSeason;
                        $latestLogo = $logoEntry->secureUrl;
                    }
                }
            }
            $logo = $latestLogo;
        } else {
            $logo = null;
        }

        $randomId = substr(str_shuffle("0123456789"), 0, 2);
        
        $firstPlayerHtml = '
            <div class="player-card" data-player-id="' . $playerId . '" data-team-id="' . $teamId . '" data-tricode="' . $triCode . '" data-name="' . htmlspecialchars($playerName) . '" data-position="' . $positionCode . '" data-logo="' . $logo . '" data-bg-color="' . teamToColor($teamId ?? 0) . '" data-stat="' . $formattedStat . '" data-headshot="https://assets.nhle.com/mugs/nhl/' . htmlspecialchars($season) . '/' . htmlspecialchars($triCode) . '/' . htmlspecialchars($playerId) . '.png" data-jersey="'. $sweaterNumber .'">
                <a class="headshot" href="#" id="player-link" data-link="' . $playerId . '">
                    <svg class="headshot_wrap" width="128" height="128">
                        <mask id="circleMask:r'. $randomId .':">
                            <svg>
                                <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                            </svg>
                        </mask>
                        <image mask="url(#circleMask:r'. $randomId .':)" fill="#000000" id="canTop" height="128" href="https://assets.nhle.com/mugs/nhl/'. htmlspecialchars($season) .'/'. htmlspecialchars($triCode) .'/'. htmlspecialchars($playerId) .'.png"></image>
                    </svg>
                    <svg class="team-fill" width="128" height="128">
                        <circle cx="64" cy="72" r="56" fill="'. teamToColor($teamId ?? 0) .'"></circle>
                        <defs>
                            <linearGradient id="gradient:r'. $randomId .':" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                                <stop offset="65%" stop-opacity="0.35" stop-color="#000000"></stop>
                            </linearGradient>
                        </defs>
                        <circle cx="64" cy="72" r="56" fill="url(#gradient:r'. $randomId .':)"></circle>
                    </svg>
                </a>
                <div class="player-info">
                    <div class="mob-flex">
                        <div class="player-name">' . htmlspecialchars($playerName) . '</div>
                        <div class="player-meta">
                            <img src="' . $logo . '" alt="' . $triCode . '" class="team-logo">
                            <span class="team-code">' . $triCode . '</span>
                            <span class="player-number">#' . $sweaterNumber . '</span>
                            <div class="player-position">' . $positionCode . '</div>
                        </div>
                    </div>
                    <div class="player-stat"><h2>' . $formattedStat . '</h2><span>' . $category . '</span></div>
                </div>
            </div>
        ';
    }

    ?>
    <div class="stat-leader-holder"><?php echo $firstPlayerHtml; ?></div>
    <div class="stat-leader-list">
    <?php for ($i = 0; $i < $playerCount; $i++) {
        $statPoint = $statPoints->data[$i];
        
        // Format stat value for display
        $formattedStat = formatStatValue($statPoint, $category);
        if ($formattedStat === null) {
            $formattedStat = 'N/A';
        }
        
        // Update ranking logic
        $currentValue = getPlayerStatValue($statPoint, $category);
        $isTiedWithPrevious = ($i > 0 && $currentValue === $previousValue);
        $isTiedWithNext = ($i < $playerCount - 1 && $currentValue === getPlayerStatValue($statPoints->data[$i+1], $category));
        
        if (!$isTiedWithPrevious) {
            $displayRank = $i + 1;
        }
        
        $rankText = ($isTiedWithPrevious || $isTiedWithNext) ? 'T' . $displayRank : $displayRank;
        $previousValue = $currentValue;
        
        // Show full card design for the first 3 players only, regardless of rank

            // Full card design
            ?>
            <?php
            // Defensive extraction of fields to avoid undefined property notices
            $playerId = $statPoint->player->id ?? null;
            $teamId = $statPoint->team->id ?? null;
            $logo = $statPoint->team->logos ?? null;
            $triCode = $statPoint->team->triCode ?? null;
            $positionCode = $statPoint->player->positionCode ?? null;
            $playerName = $statPoint->player->fullName ?? 'Unknown';
            $sweaterNumber = $statPoint->player->sweaterNumber ?? '';

            // Find the maximum value for $logo->endSeason and use the latest logo available (secureUrl)
            if (is_array($logo) && !empty($logo)) {
                $latestLogo = null;
                $latestEndSeason = 0;
                foreach ($logo as $logoEntry) {
                    if (isset($logoEntry->endSeason) && isset($logoEntry->secureUrl)) {
                        if ((int)$logoEntry->endSeason > $latestEndSeason) {
                            $latestEndSeason = (int)$logoEntry->endSeason;
                            $latestLogo = $logoEntry->secureUrl;
                        }
                    }
                }
                $logo = $latestLogo;
            } else {
                $logo = null;
            }
            ?>
            <button type="button" class="stat-leader-list-item" data-player-id="<?= $playerId ?>" data-team-id="<?= $teamId ?>" data-tricode="<?= $triCode ?>" data-rank="<?= $rankText; ?>" data-name="<?= htmlspecialchars($playerName) ?>" data-position="<?= $positionCode; ?>" data-logo="<?= $logo ?>" data-bg-color="<?= teamToColor($teamId ?? 0) ?>" data-stat="<?= $formattedStat ?>" data-headshot="https://assets.nhle.com/mugs/nhl/<?= htmlspecialchars($season) ?>/<?= htmlspecialchars($triCode) ?>/<?= htmlspecialchars($playerId) ?>.png" data-jersey="<?= $sweaterNumber ?>">
                <div class="rank"><?= $rankText; ?></div>
                <div class="name"><?= htmlspecialchars($playerName) ?></div>
                <div class="main-stat"><?= $formattedStat ?></div>
            </button>
            <?php
    }
    ?>
    </div>
    <?php
    $output = ob_get_clean();
    return $output;
}

/**
 * Get the actual stat value for a player for comparison purposes
 * 
 * @param object $statPoint Player stat object
 * @param string $category Stat category
 * @return mixed The actual numeric value of the stat
 */
function getPlayerStatValue($statPoint, $category) {
    // For GAA, lower is better so we need to handle it specially
    if ($category === 'gaa') {
        return $statPoint->gaa;
    } else if ($category === 'savePctg') {
        return $statPoint->savePctg;
    } else {
        return $statPoint->{$category};
    }
}

/**
 * Build API URL for stat leader requests
 *
 * @param string $type Player type (skaters, goalies, defense, rookies)
 * @param string $category Stat category (points, goals, etc.)
 * @param string $season Season ID
 * @param int $gameType Game type (2=Regular, 3=Playoffs)
 * @return string Complete API URL
 */
function buildStatLeaderApiUrl($type, $category, $season, $gameType) {
    // Use the new NHL API utility without limit to match old behavior
    return NHLApi::statLeaders($type, $category, $season, $gameType);
    
    // Start with basic URL structure
    if ($type === 'goalies') {
        $url = $baseUrl . "goalies/{$category}?cayenneExp=season={$season}%20and%20gameType={$gameType}";
        $url .= "%20and%20gamesPlayed%20>=%205";
    } else {
        $url = $baseUrl . "skaters/{$category}?cayenneExp=season={$season}%20and%20gameType={$gameType}";
        
        // Add position or rookie filters
        if ($type === 'defense') {
            $url .= "%20and%20player.positionCode%20=%20%27D%27";
        } elseif ($type === 'rookies') {
            $url .= "%20and%20isRookie%20=%20%27Y%27";
        }
    }
    
    return $url;
}

/**
 * Find the maximum value for a stat category
 *
 * @param array $data Array of player stat objects
 * @param string $category The stat category to find max value for
 * @return mixed Maximum value found
 */
function findMaxStatValue($data, $category) {
    // Check if data is valid - it could be an array of objects or empty
    if (empty($data)) {
        return 0;
    }
    
    // Convert to array if it's not already (handles both arrays and objects)
    if (!is_array($data)) {
        $data = (array) $data;
    }
    
    // If still empty after conversion, return 0
    if (empty($data)) {
        return 0;
    }
    
    // Special case for categories where lower is better
    if ($category === 'gaa') {
        return min(array_column($data, $category));
    } elseif ($category === 'savePctg') {
        return max(array_column($data, $category));
    } else {
        return max(array_column($data, $category));
    }
}

/**
 * Check if a player is the stat leader
 *
 * @param object $statPoint Player stat object
 * @param string $category Stat category
 * @param mixed $maxValue Maximum value for this category
 * @return boolean True if player is the leader
 */
function isStatLeader($statPoint, $category, $maxValue) {
    if ($category === 'gaa') {
        return $statPoint->gaa == $maxValue;
    } elseif ($category === 'savePctg') {
        return $statPoint->savePctg == $maxValue;
    } else {
        return $statPoint->{$category} == $maxValue;
    }
}

function formatStatValue($statPoint, $category) {
    switch ($category) {
        case 'savePctg':
            return number_format($statPoint->savePctg, 3);
        case 'gaa':
            return number_format($statPoint->gaa, 2);
        case 'goals':
            return $statPoint->goals;
        case 'assists':
            return $statPoint->assists;
        case 'points':
            return $statPoint->points;
        default:
            return $statPoint->{$category};
    }
}

function getPlayerSeasonStats($playerId, $season, $type = 'skater', $seasonCompare = '=') {
    $stats = [];
    
    if ($type === 'skater') {
        $endpoints = [
            'puckPossession' => 'skater/puckPossessions',
            'goalsForAgainst' => 'skater/goalsForAgainst',
            'realtime' => 'skater/realtime',
            'scoringRates' => 'skater/scoringRates',
            'shottype' => 'skater/shottype',
            'percentages' => 'skater/percentages',
            'scoringpergame' => 'skater/scoringpergame',
            'summary' => 'skater/summary'
        ];
    } else {
        $endpoints = [
            'summary' => 'goalie/summary',
            'advanced' => 'goalie/advanced',
            'savesByStrength' => 'goalie/savesByStrength'
        ];
    }

    foreach ($endpoints as $key => $endpoint) {
        // Use the new NHL API utility - extract player type from endpoint
        $playerType = explode('/', $endpoint)[0];
        $endpointPath = explode('/', $endpoint)[1];
        
        $conditions = [
            'gameTypeId' => '2',
            'playerId' => $playerId,
            'seasonId' => [$seasonCompare => $season]
        ];
        $params = ['limit' => ($seasonCompare === '<=' ? 30 : 1)];
        $ApiUrl = NHLApi::playerStats($playerType, $endpointPath, $conditions, $params);
        
        $curl = curlInit($ApiUrl);
        $result = json_decode($curl);
        
        if ($result && isset($result->data[0])) {
            // If getting multiple seasons, return full data array
            $stats[$key] = $seasonCompare === '<=' ? $result->data : $result->data[0];
        }
    }

    return $stats;
}

function calculateAdvancedStats($playerStats) {
    $stats = [
        'formattedSAT' => '',
        'formattedUSAT' => '',
        'evenStrengthGoalDiff' => ''
    ];
    
    // Calculate SAT% (Corsi)
    if (isset($playerStats['puckPossession']->satPct)) {
        $stats['formattedSAT'] = number_format($playerStats['puckPossession']->satPct * 100, 1);
    }
    
    // Calculate USAT% (Fenwick)
    if (isset($playerStats['puckPossession']->usatPct)) {
        $stats['formattedUSAT'] = number_format($playerStats['puckPossession']->usatPct * 100, 1);
    }
    
    // Get Even Strength Goal Differential
    if (isset($playerStats['goalsForAgainst']->evenStrengthGoalDifference)) {
        $stats['evenStrengthGoalDiff'] = $playerStats['goalsForAgainst']->evenStrengthGoalDifference;
    }
    
    return $stats;
}

/**
 * Gets advanced stats for a player for both regular season and playoffs
 * @param int $playerId Player ID
 * @param string $season Season ID
 * @param int $gameType Game type (2=Regular Season, 3=Playoffs)
 * @return array Advanced stats including SAT%, USAT%, and goal differential
 */
function getPlayerAdvancedStats($playerId, $season, $gameType = 2) {
    $endpoints = [
        'puckPossession' => 'skater/puckPossessions',
        'goalsForAgainst' => 'skater/goalsForAgainst'
    ];
    
    $advancedStats = [];
    foreach ($endpoints as $key => $endpoint) {
        // Use the new NHL API utility
        $playerType = explode('/', $endpoint)[0];
        $endpointPath = explode('/', $endpoint)[1];
        
        $conditions = [
            'gameTypeId' => $gameType,
            'playerId' => $playerId,
            'seasonId' => $season
        ];
        $ApiUrl = NHLApi::playerStats($playerType, $endpointPath, $conditions, ['limit' => 1]);
        $curl = curlInit($ApiUrl);
        $result = json_decode($curl);
        
        if ($result && isset($result->data[0])) {
            $advancedStats[$key] = $result->data[0];
        }
    }
    
    // Calculate stats
    $formattedSAT = isset($advancedStats['puckPossession']->satPct) 
        ? number_format($advancedStats['puckPossession']->satPct * 100, 1) 
        : 'N/A';
    
    $formattedUSAT = isset($advancedStats['puckPossession']->usatPct) 
        ? number_format($advancedStats['puckPossession']->usatPct * 100, 1) 
        : 'N/A';
    
    $evenStrengthGoalDiff = isset($advancedStats['goalsForAgainst']->evenStrengthGoalDifference) 
        ? $advancedStats['goalsForAgainst']->evenStrengthGoalDifference 
        : '0';
    
    return [
        'formattedSAT' => $formattedSAT,
        'formattedUSAT' => $formattedUSAT,
        'evenStrengthGoalDiff' => $evenStrengthGoalDiff
    ];
}

/**
 * Get skater leaders table data for a given season and game type
 * @param string $season
 * @param int $gameType (2=Regular, 3=Playoffs)
 * @param int $limit
 * @return array|null
 */
function getSkaterLeadersTable($season, $type = 'skater', $gameType = 2, $limit = 300) {
    // Use the new NHL API utility
    $conditions = [
        'gameTypeId' => $gameType,
        'seasonId' => $season
    ];
    $params = [
        'limit' => $limit,
        'sort' => NHLApi::buildSort([
            ['property' => 'points', 'direction' => 'DESC'],
            ['property' => 'goals', 'direction' => 'DESC'],
            ['property' => 'assists', 'direction' => 'DESC'],
            ['property' => 'playerId', 'direction' => 'ASC']
        ])
    ];
    $ApiUrl = NHLApi::playerStats($type, 'summary', $conditions, $params);
    $curl = curlInit($ApiUrl);
    $standing = json_decode($curl);
    return $standing;
}

/**
 * Player Metrics Helper Functions
 * Provides functions for calculating advanced player metrics
 */

/**
 * Converts a raw stat value to a percentile score (0-100)
 * 
 * @param float $value Raw stat value
 * @param float $benchmark League average benchmark
 * @param float $elite Elite level benchmark
 * @return float Normalized score (0-100)
 */
function normalizeToPercentile($value, $benchmark, $elite) {
    // Special handling for metrics where lower is better (like Expected Goals Against)
    $invertedMetric = false;
    if ($elite < $benchmark) {
        $invertedMetric = true;
        // For inverted metrics, we'll handle them specially below
    }
    
    // If benchmark and elite are the same, we need to handle this specially
    if ($benchmark == $elite) {
        // When benchmark and elite are the same, we just check if value meets/exceeds that point
        if ($benchmark == 0) {
            return ($value == 0) ? 50 : 0; // If both are zero, value of 0 = 50%, anything else = 0%
        }
        return ($value >= $benchmark) ? 85 : (($value / max(0.001, $benchmark)) * 50);
    }

    // Special case for goal differential (benchmark is 0, and higher values are better)
    if ($benchmark == 0 && !$invertedMetric && isset($_POST['debug'])) {
        // Log the goal differential value for debugging
        error_log("Goal differential raw value: $value (benchmark: $benchmark, elite: $elite)");
    }
    
    // Handle metrics where the benchmark is zero (like Goal Differential)
    if ($benchmark == 0 && !$invertedMetric) {
        if ($value == 0) return 50; // At benchmark = 50%
        
        if ($value > 0) {
            // Positive value (good for non-inverted metrics)
            $ratio = min(1, $value / max(1, $elite)); // Avoid division by zero
            return 50 + ($ratio * 35);
        } else {
            // Negative value (bad for non-inverted metrics)
            $ratio = min(1, abs($value) / max(1, abs($elite * 0.5))); // Scale negative values differently
            return max(0, 50 - ($ratio * 50));
        }
    }
    
    // Handle metrics where negative values are better (like Expected Goals Against)
    if ($invertedMetric) {
        if ($value == $benchmark) return 50; // At benchmark = 50%
        
        if ($value < $benchmark) {
            // Better than benchmark (for inverted metrics, lower is better)
            $ratio = min(1, ($benchmark - $value) / ($benchmark - $elite));
            return 50 + ($ratio * 35);
        } else {
            // Worse than benchmark
            $ratio = min(1, ($value - $benchmark) / abs($benchmark * 0.5));
            return max(0, 50 - ($ratio * 50));
        }
    }
    
    // Standard metrics where higher values are better and benchmark is non-zero
    if ($value <= 0 && $benchmark > 0) {
        return 0; // For standard positive metrics, zero or negative is 0 percentile
    }
    
    if ($value <= $benchmark) {
        // Below benchmark: 0-50 range
        $ratio = max(0, $value / max(0.001, $benchmark)); // Prevent division by zero
        return $ratio * 50;
    } else if ($value >= $elite) {
        // Elite or better: 85-100 range
        $denominator = max(0.001, $elite - $benchmark); // Prevent division by zero
        $excessRatio = min(1, ($value - $elite) / $denominator);
        return 85 + ($excessRatio * 15);
    } else {
        // Between benchmark and elite: 50-85 range
        $denominator = max(0.001, $elite - $benchmark); // Prevent division by zero
        $ratio = ($value - $benchmark) / $denominator;
        return 50 + ($ratio * 35);
    }
}

/**
 * Calculates a relative metric where no direct stat exists
 * This is a placeholder that would normally use more complex calculations
 * based on tracking data or aggregated stats
 * 
 * @param string $metricType Type of metric to calculate
 * @param array $playerStats Player statistics array
 * @return float Calculated metric value
 */
function calculateRelativeMetric($metricType, $playerStats) {
    // In a real implementation, these would be calculated from detailed tracking data
    // or using more advanced statistical models
    switch ($metricType) {
        case 'exit':
            // Zone exit success % (estimated from available stats)
            $baseValue = 50.0; // League average baseline
            
            // Adjust based on possession metrics - players with better possession 
            // stats likely have better zone exit success
            if (isset($playerStats['puckPossession']->satPct)) {
                $satAdjustment = ($playerStats['puckPossession']->satPct - 0.5) * 50;
                $baseValue += $satAdjustment;
            }
            
            // Adjust for giveaways (negative impact on exits)
            if (isset($playerStats['realtime']->giveawaysPer60)) {
                $giveawayFactor = max(0, 2 - $playerStats['realtime']->giveawaysPer60) / 2;
                $baseValue *= (0.9 + (0.2 * $giveawayFactor));
            }
            
            return min(100, max(0, $baseValue));
            
        case 'denial':
            // Entry denial % (estimated)
            $baseValue = 50.0;
            
            // Adjust based on defensive metrics
            if (isset($playerStats['realtime']->blockedShotsPer60)) {
                $blockAdjustment = ($playerStats['realtime']->blockedShotsPer60 / 5) * 5;
                $baseValue += $blockAdjustment;
            }
            
            if (isset($playerStats['realtime']->takeawaysPer60)) {
                $takeawayAdjustment = $playerStats['realtime']->takeawaysPer60 * 3;
                $baseValue += $takeawayAdjustment;
            }
            
            if (isset($playerStats['realtime']->hitsPer60)) {
                $hitAdjustment = ($playerStats['realtime']->hitsPer60 / 6) * 2;
                $baseValue += $hitAdjustment;
            }
            
            return min(100, max(0, $baseValue));
            
        case 'xga':
            // Expected Goals Against impact (proxy calculation)
            // Negative values mean player reduces expected goals against (good)
            $impact = 0;
            
            // If the player has good defensive numbers, they likely reduce xGA
            if (isset($playerStats['puckPossession']->satPct)) {
                // Scale the impact more significantly
                $impact -= ($playerStats['puckPossession']->satPct - 0.5) * 3;
            }
            
            if (isset($playerStats['goalsForAgainst']->evenStrengthGoalsForPct)) {
                // Scale the impact more significantly 
                $impact -= ($playerStats['goalsForAgainst']->evenStrengthGoalsForPct - 0.5) * 2.5;
            }
            
            // Add additional factors to make this metric more visible
            if (isset($playerStats['realtime']->takeawaysPer60)) {
                $impact -= ($playerStats['realtime']->takeawaysPer60 / 2);
            }
            
            if (isset($playerStats['realtime']->blockedShotsPer60)) {
                $impact -= ($playerStats['realtime']->blockedShotsPer60 / 5);
            }
            
            // Ensure we're generating values that will show variation
            return max(-2.0, min(2.0, $impact)); // Clamp to reasonable range

        default:
            return 0;
    }
}

/**
 * Normalizes metrics to 0-100 scale for visualization
 * 
 * @param array $metricsGroups Groups of metrics with values and benchmarks
 * @return array Normalized data for charts
 */
function normalizeMetrics($metricsGroups) {
    $chartData = [
        "categories" => [],
        "metrics" => [],
        "values" => [],
        "benchmarks" => [],
        "elite" => [],
        "tooltips" => []
    ];
    
    foreach ($metricsGroups as $category => $metrics) {
        foreach ($metrics as $name => $data) {
            $chartData["categories"][] = $category;
            $chartData["metrics"][] = $name;
            $chartData["tooltips"][] = isset($data["tooltip"]) ? $data["tooltip"] : null;
            
            // Calculate score on 0-100 scale
            $normalizedValue = normalizeToPercentile($data["value"], $data["benchmark"], $data["elite"]);
            $chartData["values"][] = $normalizedValue;
            
            // Also provide benchmark and elite level normalized values for visual reference
            $chartData["benchmarks"][] = 50; // Benchmark is always 50 on our normalized scale
            $chartData["elite"][] = 80; // Elite level is always 80 on our normalized scale
        }
    }
    
    return $chartData;
}

/**
 * Get a player's goal differential value using the most reliable source
 * 
 * @param array $playerStats Player statistics array
 * @return int Goal differential value
 */
function getGoalDifferential($playerStats) {
    // Check for direct evenStrengthGoalDifference property
    if (isset($playerStats['goalsForAgainst']->evenStrengthGoalDifference)) {
        return $playerStats['goalsForAgainst']->evenStrengthGoalDifference;
    }
    
    // Alternative: calculate from evenStrengthGoalsFor and evenStrengthGoalsAgainst
    if (isset($playerStats['goalsForAgainst']->evenStrengthGoalsFor) && 
        isset($playerStats['goalsForAgainst']->evenStrengthGoalsAgainst)) {
        return $playerStats['goalsForAgainst']->evenStrengthGoalsFor - 
               $playerStats['goalsForAgainst']->evenStrengthGoalsAgainst;
    }
    
    // Alternative: Use the player's plusMinus as a fallback
    if (isset($playerStats['summary']->plusMinus)) {
        return $playerStats['summary']->plusMinus;
    }
    
    // If all else fails, return 0
    return 0;
}

/**
 * Calculate defensive impact metric - converts Expected Goals Against
 * into a positive scale where higher values are better (for consistency in radar charts)
 * 
 * @param array $playerStats Player statistics array
 * @return float Defensive impact score (higher = better defense)
 */
function calculateDefensiveImpact($playerStats) {
    // Start with a baseline value
    $impact = 50.0;
    
    // If the player has good defensive numbers, they get a higher score
    if (isset($playerStats['puckPossession']->satPct)) {
        // Shot attempt ratio - higher values mean better defense
        $satImpact = ($playerStats['puckPossession']->satPct - 0.5) * 40;
        $impact += $satImpact;
    }
    
    if (isset($playerStats['goalsForAgainst']->evenStrengthGoalsForPct)) {
        // Goals for percentage - higher values mean better defense
        $goalImpact = ($playerStats['goalsForAgainst']->evenStrengthGoalsForPct - 0.5) * 30;
        $impact += $goalImpact;
    }
    
    // Add additional factors
    if (isset($playerStats['realtime']->takeawaysPer60)) {
        // Takeaways per 60 - more takeaways = better defense
        $takeawayImpact = $playerStats['realtime']->takeawaysPer60 * 5;
        $impact += $takeawayImpact;
    }
    
    if (isset($playerStats['realtime']->blockedShotsPer60)) {
        // Blocked shots per 60 - more blocks = better defense
        $blockImpact = $playerStats['realtime']->blockedShotsPer60;
        $impact += $blockImpact;
    }
    
    // Ensure the final score is within a reasonable range
    return max(30, min(80, $impact));
}

/**
 * Calculate normalized values for goalie stats
 * 
 * @param array $playerStats Goalie statistics array
 * @return array Radar chart data for goalie display
 */
function calculateGoalieMetrics($playerStats) {
    $summary = $playerStats['summary'] ?? null;
    $advanced = $playerStats['advanced'] ?? null;
    $savesByStrength = $playerStats['savesByStrength'] ?? null;
    
    $metrics = [
        "saves" => [
            "Overall SV%" => [
                "value" => isset($savesByStrength->savePct) ? $savesByStrength->savePct * 100 : 0,
                "benchmark" => 90.0,
                "elite" => 92.5,
                "tooltip" => "Overall save percentage across all situations"
            ],
            "Even Strength SV%" => [
                "value" => isset($savesByStrength->evSavePct) ? $savesByStrength->evSavePct * 100 : 0,
                "benchmark" => 91.0,
                "elite" => 93.0,
                "tooltip" => "Save percentage during even-strength play"
            ],
            "Power Play SV%" => [
                "value" => isset($savesByStrength->ppSavePct) ? $savesByStrength->ppSavePct * 100 : 0,
                "benchmark" => 85.0,
                "elite" => 88.0,
                "tooltip" => "Save percentage while team is on power play"
            ],
            "Shorthand SV%" => [
                "value" => isset($savesByStrength->shSavePct) ? $savesByStrength->shSavePct * 100 : 0,
                "benchmark" => 87.0,
                "elite" => 90.0, 
                "tooltip" => "Save percentage while team is short-handed"
            ]
        ],
        "consistency" => [
            "Quality Start %" => [
                "value" => isset($advanced->qualityStartsPct) ? $advanced->qualityStartsPct * 100 : 0,
                "benchmark" => 55.0,
                "elite" => 70.0,
                "tooltip" => "Percentage of starts with .885+ SV% or ≤3 GA"
            ],
            "Complete Game %" => [
                "value" => isset($advanced->completeGamePct) ? $advanced->completeGamePct * 100 : 0,
                "benchmark" => 70.0,
                "elite" => 90.0,
                "tooltip" => "Percentage of games played to completion"
            ]
        ],
        "context" => [
            "Goals Support" => [
                "value" => isset($advanced->goalsForAverage) ? $advanced->goalsForAverage : 0,
                "benchmark" => 2.8,
                "elite" => 3.5,
                "tooltip" => "Average team goal support"
            ],
            "Shots Against/60" => [
                "value" => isset($advanced->shotsAgainstPer60) ? $advanced->shotsAgainstPer60 : 0,
                "benchmark" => 30.0,
                "elite" => 25.0, // Lower is better for shots against
                "tooltip" => "Average shots faced per 60 minutes (lower is better)"
            ]
        ]
    ];
    
    return normalizeMetrics($metrics);
}

/**
 * Check if a season has stat leaders data available
 * @param string $season The season ID (e.g., '20242025')
 * @param bool $playoffs Whether to check playoffs or regular season
 * @return bool True if data is available
 */
function seasonHasStatData($season, $playoffs = false) {
    $gameType = $playoffs ? 3 : 2;
    $apiUrl = buildStatLeaderApiUrl('skaters', 'points', $season, $gameType);
    
    $baseCacheDir = dirname(__DIR__, 2) . '/cache/stat-leaders/';
    $seasonCacheDir = $baseCacheDir . $season . '/';
    $fileName = "skaters_points_" . ($playoffs ? 'playoffs' : 'regular');
    $cacheFile = $seasonCacheDir . $fileName . '.json';
    $cacheTime = 60 * 60; // 1 hour
    
    try {
        $data = fetchData($apiUrl, $cacheFile, $cacheTime);
        return $data && isset($data->data) && !empty($data->data);
    } catch (Exception $e) {
        return false;
    }
}