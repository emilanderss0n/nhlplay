<?php
include_once '../path.php';
include_once '../includes/functions.php';

// Get player ID and data from the request
$playerID = $_POST['player'] ?? null;
$playerData = $_POST['playerData'] ?? null;

if (!$playerID || !$playerData) {
    echo json_encode(['error' => 'No player ID or data provided']);
    exit;
}

// Decode the player data
$player = json_decode($playerData);

if (!$player) {
    echo json_encode(['error' => 'Invalid player data']);
    exit;
}

// Determine player type
$isGoalie = !($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D');

if (!$isGoalie) {
    // Get all skater stats using new function
    $playerStats = getPlayerSeasonStats($playerID, $season, 'skater');
    
    // Calculate advanced stats
    $advancedStats = calculateAdvancedStats($playerStats);
    
    // Determine if player is forward or defenseman
    $isForward = ($player->position == 'C' || $player->position == 'L' || $player->position == 'R');
    
    // Define position-specific metrics and league benchmarks
    if ($isForward) {
        // Forward metrics with position-specific benchmarks
        $metrics = [
            "scoring" => [
                "Points/60" => [
                    "value" => isset($playerStats['scoringRates']->pointsPer605v5) ? $playerStats['scoringRates']->pointsPer605v5 : 0,
                    "benchmark" => 2.0,
                    "elite" => 3.0,
                    "tooltip" => "Points produced per 60 minutes of 5v5 play"
                ],
                "Goals/60" => [
                    "value" => isset($playerStats['scoringRates']->goalsPer605v5) ? $playerStats['scoringRates']->goalsPer605v5 : 0,
                    "benchmark" => 0.8,
                    "elite" => 1.2,
                    "tooltip" => "Goals scored per 60 minutes of 5v5 play"
                ],
                "Shooting %" => [
                    "value" => isset($playerStats['summary']->shootingPct) ? $playerStats['summary']->shootingPct * 100 : 0,
                    "benchmark" => 10.0,
                    "elite" => 15.0,
                    "tooltip" => "Percentage of shots that result in goals"
                ]
            ],
            "playmaking" => [
                "Primary Assists/60" => [
                    "value" => isset($playerStats['scoringRates']->primaryAssistsPer605v5) ? $playerStats['scoringRates']->primaryAssistsPer605v5 : 0,
                    "benchmark" => 0.8,
                    "elite" => 1.3,
                    "tooltip" => "Direct pass leading to goal per 60 minutes of 5v5 play"
                ],
                "Shot Generation" => [
                    "value" => isset($playerStats['puckPossession']->individualSatForPer60) ? $playerStats['puckPossession']->individualSatForPer60 : 0,
                    "benchmark" => 12.0,
                    "elite" => 18.0,
                    "tooltip" => "Individual shot attempts generated per 60 minutes"
                ],
                "On-Ice Shooting %" => [
                    "value" => isset($playerStats['percentages']->shootingPct5v5) ? $playerStats['percentages']->shootingPct5v5 * 100 : 0,
                    "benchmark" => 8.0,
                    "elite" => 11.0,
                    "tooltip" => "Team shooting percentage while player is on ice"
                ]
            ],
            "possession" => [
                "Shot Attempt %" => [
                    "value" => isset($playerStats['puckPossession']->satPct) ? $playerStats['puckPossession']->satPct * 100 : 0,
                    "benchmark" => 50.0,
                    "elite" => 55.0,
                    "tooltip" => "Percentage of total shot attempts in team's favor"
                ],
                "Zone Starts %" => [
                    "value" => isset($playerStats['puckPossession']->zoneStartPct) ? $playerStats['puckPossession']->zoneStartPct * 100 : 0,
                    "benchmark" => 50.0,
                    "elite" => 65.0,
                    "tooltip" => "Percentage of faceoffs in offensive zone vs. defensive zone"
                ],
                "Goal Differential" => [
                    "value" => getGoalDifferential($playerStats),
                    "benchmark" => 0,
                    "elite" => 15,
                    "tooltip" => "Plus/minus for goals at even strength"
                ]
            ],
            "defense" => [
                "Takeaways/60" => [
                    "value" => isset($playerStats['realtime']->takeawaysPer60) ? $playerStats['realtime']->takeawaysPer60 : 0,
                    "benchmark" => 1.0,
                    "elite" => 2.0,
                    "tooltip" => "Pucks stolen from opponents per 60 minutes"
                ],
                "Defensive Impact" => [
                    "value" => calculateDefensiveImpact($playerStats),
                    "benchmark" => 50.0,
                    "elite" => 70.0,
                    "tooltip" => "Overall defensive impact (higher is better)"
                ]
            ]
        ];
    } else {
        // Defenseman metrics with position-specific benchmarks
        $metrics = [
            "offense" => [
                "Points/60" => [
                    "value" => isset($playerStats['scoringRates']->pointsPer605v5) ? $playerStats['scoringRates']->pointsPer605v5 : 0,
                    "benchmark" => 1.0,
                    "elite" => 1.8,
                    "tooltip" => "Points produced per 60 minutes of 5v5 play"
                ],
                "Shot Generation" => [
                    "value" => isset($playerStats['puckPossession']->individualSatForPer60) ? $playerStats['puckPossession']->individualSatForPer60 : 0,
                    "benchmark" => 10.0,
                    "elite" => 15.0,
                    "tooltip" => "Individual shot attempts generated per 60 minutes"
                ]
            ],
            "transition" => [
                "Exit Success %" => [
                    "value" => calculateRelativeMetric('exit', $playerStats),
                    "benchmark" => 50.0,
                    "elite" => 65.0,
                    "tooltip" => "Successful zone exits relative to position average"
                ],
                "Entry Denial %" => [
                    "value" => calculateRelativeMetric('denial', $playerStats),
                    "benchmark" => 50.0,
                    "elite" => 65.0,
                    "tooltip" => "Successful opponent zone entry denials relative to average"
                ]
            ],
            "defense" => [
                "Shot Attempt %" => [
                    "value" => isset($playerStats['puckPossession']->satPct) ? $playerStats['puckPossession']->satPct * 100 : 0,
                    "benchmark" => 50.0,
                    "elite" => 55.0,
                    "tooltip" => "Percentage of total shot attempts in team's favor"
                ],
                "Blocks/60" => [
                    "value" => isset($playerStats['realtime']->blockedShotsPer60) ? $playerStats['realtime']->blockedShotsPer60 : 0,
                    "benchmark" => 4.0,
                    "elite" => 6.0,
                    "tooltip" => "Shots blocked per 60 minutes"
                ],
                "Takeaways/60" => [
                    "value" => isset($playerStats['realtime']->takeawaysPer60) ? $playerStats['realtime']->takeawaysPer60 : 0,
                    "benchmark" => 0.8,
                    "elite" => 1.5,
                    "tooltip" => "Pucks stolen from opponents per 60 minutes"
                ],
                "Defensive Impact" => [
                    "value" => calculateDefensiveImpact($playerStats),
                    "benchmark" => 50.0, 
                    "elite" => 70.0,
                    "tooltip" => "Overall defensive impact (higher is better)"
                ]
            ],
            "physical" => [
                "Hits/60" => [
                    "value" => isset($playerStats['realtime']->hitsPer60) ? $playerStats['realtime']->hitsPer60 : 0,
                    "benchmark" => 4.0,
                    "elite" => 8.0,
                    "tooltip" => "Physical hits delivered per 60 minutes"
                ],
                "Goal Differential" => [
                    "value" => getGoalDifferential($playerStats),
                    "benchmark" => 0,
                    "elite" => 10,
                    "tooltip" => "Plus/minus for goals at even strength"
                ]
            ]
        ];
    }
    
    // Calculate normalized scores for each metric
    $chartData = normalizeMetrics($metrics);
    
    echo json_encode([
        "chartType" => "radar",
        "playerPosition" => $isForward ? "forward" : "defenseman",
        "chartData" => $chartData,
        "lastGames_skater" => true
    ]);
} else {
    // Handle goalie stats
    $playerStats = getPlayerSeasonStats($playerID, $season, 'goalie');
    $summary = $playerStats['summary'] ?? null;
    $advanced = $playerStats['advanced'] ?? null;
    $savesByStrength = $playerStats['savesByStrength'] ?? null;
    
    // Define goalie-specific metrics and benchmarks
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
            ],
            "Shutout Rate" => [
                "value" => isset($summary->shutouts) && isset($summary->gamesPlayed) ? 
                    ($summary->shutouts / max(1, $summary->gamesPlayed)) * 100 : 0,
                "benchmark" => 10.0,
                "elite" => 20.0,
                "tooltip" => "Percentage of games that are shutouts"
            ]
        ],
        "workload" => [
            "Shots Faced/60" => [
                "value" => isset($advanced->shotsAgainstPer60) ? 
                    min(100, ($advanced->shotsAgainstPer60 / 35) * 100) : 0,
                "benchmark" => 50.0,
                "elite" => 70.0,
                "tooltip" => "Average shots faced per 60 minutes (scaled)"
            ],
            "Win %" => [
                "value" => isset($summary->wins) && isset($summary->gamesPlayed) ? 
                    ($summary->wins / max(1, $summary->gamesPlayed)) * 100 : 0,
                "benchmark" => 50.0,
                "elite" => 65.0,
                "tooltip" => "Percentage of games that are wins"
            ]
        ]
    ];

    // Calculate normalized scores for each metric
    $chartData = normalizeMetrics($metrics);
    
    echo json_encode([
        "chartType" => "radar",
        "playerPosition" => "goalie",
        "chartData" => $chartData,
        "lastGames_skater" => false
    ]);
}
?>
