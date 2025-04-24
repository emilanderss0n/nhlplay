<?php
// Make sure BASE_URL is defined
if (!defined('BASE_URL')) {
    // If being called directly, include path.php
    include_once dirname(dirname(__DIR__)) . '/path.php';
}

function renderStatHolder($type, $category, $season, $loadOnDemand = false) {
    // Build API URL based on type and category
    $ApiUrl = "https://api.nhle.com/stats/rest/en/leaders/{$type}/{$category}?cayenneExp=season={$season}%20and%20gameType=2";
    
    // Special cases for different player types
    if ($type === 'goalies') {
        $ApiUrl = "https://api.nhle.com/stats/rest/en/leaders/goalies/{$category}?cayenneExp=season={$season}%20and%20gamesPlayed%20>=%205";
    } elseif ($type === 'defense') {
        $ApiUrl = "https://api.nhle.com/stats/rest/en/leaders/skaters/{$category}?cayenneExp=season={$season}%20and%20gameType=2%20and%20player.positionCode%20=%20%27D%27";
    } elseif ($type === 'rookies') {
        $ApiUrl = "https://api.nhle.com/stats/rest/en/leaders/skaters/{$category}?cayenneExp=season={$season}%20and%20gameType=2%20and%20isRookie%20=%20%27Y%27";
    }

    // Handle caching
    $cacheDir = dirname(__DIR__, 2) . '/cache/';
    $fileName = "{$type}_{$category}";
    $cacheFile = $cacheDir . $fileName . '.json';
    $cacheTime = 60 * 60; // 1 hour

    // Make sure cache directory exists
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    // Get stats data from cache or API
    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        $statPoints = json_decode(file_get_contents($cacheFile));
    } else {
        $curl = curlInit($ApiUrl);
        $statPoints = json_decode($curl);
        if ($statPoints) {
            file_put_contents($cacheFile, json_encode($statPoints));
        } else {
            error_log("Failed to fetch data from API URL: $ApiUrl");
        }
    }

    // Handle error
    if (!$statPoints) {
        return '<div class="error">Failed to load data</div>';
    }

    // Find the max value for this category to highlight the leader
    $maxPoints = ($category === 'gaa') ? PHP_INT_MAX : 0;
    foreach ($statPoints->data as $statPoint) {
        if ($category === 'gaa') {
            if ($statPoint->gaa < $maxPoints) {
                $maxPoints = $statPoint->gaa;
            }
        } elseif ($category === 'savePctg') {
            if ($statPoint->savePctg > $maxPoints) {
                $maxPoints = $statPoint->savePctg;
            }
        } else {
            if ($statPoint->{$category} > $maxPoints) {
                $maxPoints = $statPoint->{$category};
            }
        }
    }

    // Build HTML output
    $i = 1;
    ob_start();
    foreach ($statPoints->data as $statPoint) {
        // Determine if this is the winner/leader
        $winnerClass = '';
        if ($category === 'gaa') {
            $winnerClass = ($statPoint->gaa == $maxPoints) ? 'winner' : '';
        } elseif ($category === 'savePctg') {
            $winnerClass = ($statPoint->savePctg == $maxPoints) ? 'winner' : '';
        } else {
            $winnerClass = ($statPoint->{$category} == $maxPoints) ? 'winner' : '';
        }
        
        // Format stat value for display
        $formattedStat = formatStatValue($statPoint, $category);
        
        // Render the stat holder with absolute image URLs
        ?>
        <a id="player-link" data-link="<?= $statPoint->player->id ?>" href="#" class="player <?= $winnerClass ?>">
            <div class="rank"><?= $i++; ?></div>
            <img class="head" height="240" width="240" src="https://assets.nhle.com/mugs/nhl/<?= $season ?>/<?= $statPoint->team->triCode ?>/<?= $statPoint->player->id ?>.png" />
            <img class="team-img" src="assets/img/teams/<?= $statPoint->team->id ?>.svg" width="200" height="200">
            <div class="team-color" style="background: linear-gradient(142deg, <?= teamToColor($statPoint->team->id) ?> 0%, rgba(255,255,255,0) 58%); right: 0;"></div>

            <div class="info">
                <h3><?= $statPoint->player->fullName ?></h3>
                <div class="main-stat"><?= $formattedStat ?></div>
            </div>
        </a>
        <?php
    }
    $output = ob_get_clean();
    return $output;
}

function formatStatValue($statPoint, $category) {
    switch ($category) {
        case 'savePctg':
            return number_format($statPoint->savePctg, 3) . ' Save %';
        case 'gaa':
            return number_format($statPoint->gaa, 2) . ' GAA';
        case 'goals':
            return $statPoint->goals . ' Goals';
        case 'assists':
            return $statPoint->assists . ' Assists';
        case 'points':
            return $statPoint->points . ' Points';
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
        $ApiUrl = "https://api.nhle.com/stats/rest/en/{$endpoint}?limit=" . ($seasonCompare === '<=' ? '30' : '1') . 
                 "&cayenneExp=gameTypeId=2%20and%20playerId={$playerId}%20and%20seasonId{$seasonCompare}{$season}";
        
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
        $ApiUrl = "https://api.nhle.com/stats/rest/en/{$endpoint}?limit=1&cayenneExp=gameTypeId={$gameType}%20and%20playerId={$playerId}%20and%20seasonId={$season}";
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
function getSkaterLeadersTable($season = '20242025', $type = 'skater', $gameType = 2, $limit = 100) {
    $ApiUrl = "https://api.nhle.com/stats/rest/en/{$type}/summary?isAggregate=false&isGame=false&sort=%5B%7B%22property%22:%22points%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22goals%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22assists%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22playerId%22,%22direction%22:%22ASC%22%7D%5D&start=0&limit={$limit}&cayenneExp=gameTypeId={$gameType}%20and%20seasonId={$season}";
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
            $chartData["tooltips"][] = $data["tooltip"];
            
            // Calculate score on 0-100 scale
            $normalizedValue = normalizeToPercentile($data["value"], $data["benchmark"], $data["elite"]);
            $chartData["values"][] = $normalizedValue;
            
            // Also provide benchmark and elite level normalized values for visual reference
            $chartData["benchmarks"][] = 50; // Benchmark is always 50 on our normalized scale
            $chartData["elite"][] = 80; // Elite level is always 85 on our normalized scale
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