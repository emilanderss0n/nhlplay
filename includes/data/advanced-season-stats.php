<?php
// Skater endpoint field definitions
$NHL_API_ENDPOINTS = [
    'skater' => [
        'scoringRates' => [
            'description' => '5v5 scoring rates and offensive contributions',
            'fields' => [
                'assists5v5' => ['type' => 'int', 'desc' => 'Total 5v5 assists'],
                'assistsPer605v5' => ['type' => 'float', 'desc' => 'Assists per 60 minutes at 5v5'],
                'goals5v5' => ['type' => 'int', 'desc' => 'Goals at 5v5'],
                'goalsPer605v5' => ['type' => 'float', 'desc' => 'Goals per 60 at 5v5'],
                'points5v5' => ['type' => 'int', 'desc' => 'Total 5v5 points'],
                'pointsPer605v5' => ['type' => 'float', 'desc' => 'Points per 60 at 5v5'],
                'primaryAssists5v5' => ['type' => 'int', 'desc' => 'Primary assists at 5v5'],
                'primaryAssistsPer605v5' => ['type' => 'float', 'desc' => 'Primary assists per 60 at 5v5'],
                'secondaryAssists5v5' => ['type' => 'int', 'desc' => 'Secondary assists at 5v5'],
                'secondaryAssistsPer605v5' => ['type' => 'float', 'desc' => 'Secondary assists per 60 at 5v5'],
                'shootingPct5v5' => ['type' => 'float', 'desc' => 'Shooting % at 5v5'],
                'onIceShootingPct5v5' => ['type' => 'float', 'desc' => 'On-ice shooting % at 5v5'],
                'offensiveZoneStartPct5v5' => ['type' => 'float', 'desc' => 'OZ start % at 5v5'],
                'netMinorPenaltiesPer60' => ['type' => 'float', 'desc' => 'Net minor penalties per 60']
            ]
        ],
        'realtime' => [
            'description' => 'Live in-game tracked stats',
            'fields' => [
                'blockedShots' => ['type' => 'int', 'desc' => 'Total blocked shots'],
                'blockedShotsPer60' => ['type' => 'float', 'desc' => 'Blocks per 60 mins'],
                'hits' => ['type' => 'int', 'desc' => 'Total hits'],
                'hitsPer60' => ['type' => 'float', 'desc' => 'Hits per 60 mins'],
                'takeaways' => ['type' => 'int', 'desc' => 'Total takeaways'],
                'takeawaysPer60' => ['type' => 'float', 'desc' => 'Takeaways per 60 mins'],
                'giveaways' => ['type' => 'int', 'desc' => 'Total giveaways'],
                'giveawaysPer60' => ['type' => 'float', 'desc' => 'Giveaways per 60 mins']
            ]
        ],
        'puckPossessions' => [
            'description' => 'Puck control and usage stats',
            'fields' => [
                'faceoffPct5v5' => ['type' => 'float', 'desc' => 'FO win % at 5v5'],
                'goalsPct' => ['type' => 'float', 'desc' => '% of team goals when on ice'],
                'individualSatForPer60' => ['type' => 'float', 'desc' => 'Individual shot attempts per 60'],
                'individualShotsForPer60' => ['type' => 'float', 'desc' => 'Shots per 60'],
                'satPct' => ['type' => 'float', 'desc' => 'Corsi For %'],
                'usatPct' => ['type' => 'float', 'desc' => 'Fenwick For %'],
                'zoneStartPct' => ['type' => 'float', 'desc' => 'OZ start %']
            ]
        ],
        'goalsForAgainst' => [
            'description' => 'On-ice goal impacts',
            'fields' => [
                'evenStrengthGoalDifference' => ['type' => 'int', 'desc' => 'ES GF - GA'],
                'evenStrengthGoalsFor' => ['type' => 'int', 'desc' => 'Goals for at ES'],
                'evenStrengthGoalsAgainst' => ['type' => 'int', 'desc' => 'Goals against at ES'],
                'evenStrengthGoalsForPct' => ['type' => 'float', 'desc' => '% of GF at ES'],
                'powerPlayGoalFor' => ['type' => 'int', 'desc' => 'PP goals while on ice'],
                'powerPlayGoalsAgainst' => ['type' => 'int', 'desc' => 'PP GA while on ice'],
                'shortHandedGoalsFor' => ['type' => 'int', 'desc' => 'SH GF while on ice'],
                'shortHandedGoalsAgainst' => ['type' => 'int', 'desc' => 'SH GA while on ice']
            ]
        ],
        'summary' => [
            'description' => 'Season summary stats',
            'fields' => [
                // Basic stats
                'goals' => ['type' => 'int', 'desc' => 'Total goals'],
                'assists' => ['type' => 'int', 'desc' => 'Total assists'],
                'points' => ['type' => 'int', 'desc' => 'Total points'],
                'pointsPerGame' => ['type' => 'float', 'desc' => 'Points per game'],
                
                // Even strength
                'evGoals' => ['type' => 'int', 'desc' => 'Even strength goals'],
                'evPoints' => ['type' => 'int', 'desc' => 'Even strength points'],
                
                // Special teams
                'ppGoals' => ['type' => 'int', 'desc' => 'Power play goals'],
                'ppPoints' => ['type' => 'int', 'desc' => 'Power play points'],
                'shGoals' => ['type' => 'int', 'desc' => 'Shorthanded goals'],
                'shPoints' => ['type' => 'int', 'desc' => 'Shorthanded points'],
                
                // Shooting
                'plusMinus' => ['type' => 'int', 'desc' => 'Plus/minus rating'],
                'shootingPct' => ['type' => 'float', 'desc' => 'Shooting percentage'],
                'shots' => ['type' => 'int', 'desc' => 'Total shots'],
                
                // Other
                'faceoffWinPct' => ['type' => 'float', 'desc' => 'Faceoff win percentage'],
                'penaltyMinutes' => ['type' => 'int', 'desc' => 'Penalty minutes'],
                'otGoals' => ['type' => 'int', 'desc' => 'Overtime goals'],
                'gameWinningGoals' => ['type' => 'int', 'desc' => 'Game winning goals'],
                'timeOnIcePerGame' => ['type' => 'float', 'desc' => 'Time on ice per game (seconds)']
            ]
        ]
    ],
    'goalie' => [
        'summary' => [
            'description' => 'Basic goalie stats',
            'fields' => [
                'gamesPlayed' => ['type' => 'int', 'desc' => 'Games played'],
                'gamesStarted' => ['type' => 'int', 'desc' => 'Starts'],
                'wins' => ['type' => 'int', 'desc' => 'Total wins'],
                'losses' => ['type' => 'int', 'desc' => 'Reg losses'],
                'otLosses' => ['type' => 'int', 'desc' => 'OT/SO losses'],
                'savePct' => ['type' => 'float', 'desc' => 'Save percentage'],
                'goalsAgainstAverage' => ['type' => 'float', 'desc' => 'GAA'],
                'shutouts' => ['type' => 'int', 'desc' => 'Shutouts total'],
                'saves' => ['type' => 'int', 'desc' => 'Total saves'],
                'shotsAgainst' => ['type' => 'int', 'desc' => 'Total shots faced']
            ]
        ],
        'advanced' => [
            'description' => 'Advanced goalie stats',
            'fields' => [
                'completeGamePct' => ['type' => 'float', 'desc' => '% of starts that were full games'],
                'completeGames' => ['type' => 'int', 'desc' => 'Complete games played'],
                'qualityStart' => ['type' => 'int', 'desc' => 'Quality starts'],
                'qualityStartsPct' => ['type' => 'float', 'desc' => 'QS%'],
                'goalsForAverage' => ['type' => 'float', 'desc' => 'GFA during games started'],
                'shotsAgainstPer60' => ['type' => 'float', 'desc' => 'SA per 60 mins'],
                'regulationWins' => ['type' => 'int', 'desc' => 'Reg wins'],
                'regulationLosses' => ['type' => 'int', 'desc' => 'Reg losses']
            ]
        ]
    ]
];

// Helper function to get available fields for an endpoint
function getNHLApiEndpointFields($playerType, $endpoint) {
    global $NHL_API_ENDPOINTS;
    return $NHL_API_ENDPOINTS[$playerType][$endpoint]['fields'] ?? null;
}

// Helper function to get endpoint description
function getNHLApiEndpointDescription($playerType, $endpoint) {
    global $NHL_API_ENDPOINTS;
    return $NHL_API_ENDPOINTS[$playerType][$endpoint]['description'] ?? '';
}

// Helper function to build endpoint URL
function getNHLApiEndpointUrl($playerType, $endpoint, $playerId, $season) {
    // Use the new NHL API utility
    $conditions = [
        'gameTypeId' => '2',
        'playerId' => $playerId,
        'seasonId' => $season
    ];
    return NHLApi::playerStats($playerType, $endpoint, $conditions, ['limit' => 1]);
}
