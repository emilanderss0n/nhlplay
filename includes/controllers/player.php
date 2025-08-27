<?php
/**
 * Player controller - wrappers around NHL API calls and computations
 */
function player_fetch_landing($playerId)
{
    $ApiUrl = NHLApi::playerLanding($playerId);
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

function player_fetch_gamelog($playerId, $seasonSelection, $seasonType)
{
    $ApiUrl = NHLApi::playerGameLog($playerId, $seasonSelection, $seasonType);
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

function player_fetch_career($playerId)
{
    // landing contains career totals and seasonTotals
    return player_fetch_landing($playerId);
}

function player_compute_radar($playerId, $playerData, $season = null)
{
    // delegate to existing helper functions used elsewhere
    // expect $playerData to be decoded object or null
    $player = is_string($playerData) ? json_decode($playerData) : $playerData;
    $isGoalie = !($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D');

    if (!$isGoalie) {
        $playerStats = getPlayerSeasonStats($playerId, $season, 'skater');
        $advancedStats = calculateAdvancedStats($playerStats);
        $isForward = ($player->position == 'C' || $player->position == 'L' || $player->position == 'R');
        // Build metrics exactly as previous implementation expects
        ob_start();
        // Build a simplified metrics structure similar to prior implementation
        $metrics = [];
        // scoring
        $metrics['scoring'] = [
            'Points/60' => [
                'value' => $playerStats['scoringRates']->pointsPer605v5 ?? 0,
                'benchmark' => 2.0, 'elite' => 3.0
            ],
            'Goals/60' => [
                'value' => $playerStats['scoringRates']->goalsPer605v5 ?? 0,
                'benchmark' => 0.8, 'elite' => 1.2
            ],
            'Shooting %' => [
                'value' => ($playerStats['summary']->shootingPct ?? 0) * 100,
                'benchmark' => 10.0, 'elite' => 15.0
            ]
        ];
        // possession / playmaking
        $metrics['playmaking'] = [
            'Primary Assists/60' => ['value' => $playerStats['scoringRates']->primaryAssistsPer605v5 ?? 0,'benchmark'=>0.8,'elite'=>1.3],
            'Shot Generation' => ['value' => $playerStats['puckPossession']->individualSatForPer60 ?? 0,'benchmark'=>12.0,'elite'=>18.0],
            'On-Ice Shooting %' => ['value' => ($playerStats['percentages']->shootingPct5v5 ?? 0) * 100,'benchmark'=>8.0,'elite'=>11.0]
        ];
        // possession / defense
        $metrics['possession'] = [
            'Shot Attempt %' => ['value' => ($playerStats['puckPossession']->satPct ?? 0) * 100,'benchmark'=>50.0,'elite'=>55.0],
            'Zone Starts %' => ['value' => ($playerStats['puckPossession']->zoneStartPct ?? 0) * 100,'benchmark'=>50.0,'elite'=>65.0],
            'Goal Differential' => ['value' => getGoalDifferential($playerStats),'benchmark'=>0,'elite'=>15]
        ];
        // defense / physical
        $metrics['defense'] = [
            'Takeaways/60' => ['value' => $playerStats['realtime']->takeawaysPer60 ?? 0,'benchmark'=>1.0,'elite'=>2.0],
            'Defensive Impact' => ['value' => calculateDefensiveImpact($playerStats),'benchmark'=>50.0,'elite'=>70.0]
        ];

        $chartData = normalizeMetrics($metrics);
        return [
            'chartType' => 'radar',
            'playerPosition' => $isForward ? 'forward' : 'defenseman',
            'chartData' => $chartData,
            'lastGames_skater' => true
        ];
    } else {
        $playerStats = getPlayerSeasonStats($playerId, $season, 'goalie');
        $summary = $playerStats['summary'] ?? null;
        $advanced = $playerStats['advanced'] ?? null;
        $savesByStrength = $playerStats['savesByStrength'] ?? null;
        // goalie metrics
        $metrics = [
            'saves' => [
                'Overall SV%' => ['value' => ($savesByStrength->savePct ?? 0) * 100,'benchmark'=>90.0,'elite'=>92.5],
                'Even Strength SV%' => ['value' => ($savesByStrength->evSavePct ?? 0) * 100,'benchmark'=>91.0,'elite'=>93.0],
                'Power Play SV%' => ['value' => ($savesByStrength->ppSavePct ?? 0) * 100,'benchmark'=>85.0,'elite'=>88.0]
            ],
            'consistency' => [
                'Quality Start %' => ['value' => ($advanced->qualityStartsPct ?? 0) * 100,'benchmark'=>55.0,'elite'=>70.0],
                'Complete Game %' => ['value' => ($advanced->completeGamePct ?? 0) * 100,'benchmark'=>70.0,'elite'=>90.0],
                'Shutout Rate' => ['value' => isset($summary->shutouts,$summary->gamesPlayed) ? ($summary->shutouts / max(1,$summary->gamesPlayed)) * 100 : 0,'benchmark'=>10.0,'elite'=>20.0]
            ],
            'workload' => [
                'Shots Faced/60' => ['value' => isset($advanced->shotsAgainstPer60) ? min(100, ($advanced->shotsAgainstPer60 / 35) * 100) : 0,'benchmark'=>50.0,'elite'=>70.0],
                'Win %' => ['value' => isset($summary->wins,$summary->gamesPlayed) ? ($summary->wins / max(1,$summary->gamesPlayed)) * 100 : 0,'benchmark'=>50.0,'elite'=>65.0]
            ]
        ];
        $chartData = normalizeMetrics($metrics);
        return [
            'chartType' => 'radar',
            'playerPosition' => 'goalie',
            'chartData' => $chartData,
            'lastGames_skater' => false
        ];
    }
}

function player_compute_advanced($playerId, $isSkater = true, $season = null)
{
    if ($isSkater) {
        $playerStats = getPlayerSeasonStats($playerId, $season, 'skater');
        $advancedStats = calculateAdvancedStats($playerStats);
        return [
            'success' => true,
            'advancedStats' => [
                'formattedSAT' => $advancedStats['formattedSAT'] ?? 'N/A',
                'formattedUSAT' => $advancedStats['formattedUSAT'] ?? 'N/A',
                'evenStrengthGoalDiff' => $advancedStats['evenStrengthGoalDiff'] ?? '0'
            ]
        ];
    } else {
        $playerStats = getPlayerSeasonStats($playerId, $season, 'goalie');
        $advanced = $playerStats['advanced'] ?? null;
        $savesByStrength = $playerStats['savesByStrength'] ?? null;
        return [
            'success' => true,
            'advancedStats' => [
                'qualityStartsPct' => isset($advanced->qualityStartsPct) ? number_format((float)$advanced->qualityStartsPct * 100, 2, '.', '') : 'N/A',
                'shotsAgainstPer60' => isset($advanced->shotsAgainstPer60) ? number_format((float)$advanced->shotsAgainstPer60, 2, '.', '') : 'N/A',
                'completeGamePct' => isset($advanced->completeGamePct) ? number_format((float)$advanced->completeGamePct * 100, 2, '.', '') : 'N/A',
                'evSavePct' => isset($savesByStrength->evSavePct) ? number_format((float)$savesByStrength->evSavePct * 100, 2, '.', '') : 'N/A',
                'ppSavePct' => isset($savesByStrength->ppSavePct) ? number_format((float)$savesByStrength->ppSavePct * 100, 2, '.', '') : 'N/A',
                'shSavePct' => isset($savesByStrength->shSavePct) ? number_format((float)$savesByStrength->shSavePct * 100, 2, '.', '') : 'N/A'
            ]
        ];
    }
}
