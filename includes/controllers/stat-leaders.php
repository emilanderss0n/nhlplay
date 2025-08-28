<?php
/**
 * Controller to fetch stat leaders data and expose helper functions for views
 */
function statleaders_get_leaders($season = null, $playoffs = false)
{
    $season = $season ?? date('Y');
    // This controller delegates to existing functions that already handle caching
    $data = [];
    // Use renderStatHolder to build HTML fragments if needed, but for controller return raw structures
    // Prefer using fetchData/cache helpers where possible and guard against failures
    try {
        $gameType = $playoffs ? '3' : '2';

        $apiUrlPoints = NHLApi::skaterStatsLeaders($season, $gameType, ['points']);
        $apiUrlGoals = NHLApi::skaterStatsLeaders($season, $gameType, ['goals']);
        $apiUrlGoalies = NHLApi::goalieStatsLeaders($season, $gameType, ['savePct']);

        // Use curlInit but guard empty responses
        $pointsRaw = curlInit($apiUrlPoints);
        $goalsRaw = curlInit($apiUrlGoals);
        $goaliesRaw = curlInit($apiUrlGoalies);

        $data['skaters_points'] = $pointsRaw ? json_decode($pointsRaw) : null;
        $data['skaters_goals'] = $goalsRaw ? json_decode($goalsRaw) : null;
        $data['goalies_savePct'] = $goaliesRaw ? json_decode($goaliesRaw) : null;
    } catch (Exception $e) {
        error_log('statleaders_get_leaders exception: ' . $e->getMessage());
        $data['skaters_points'] = null;
        $data['skaters_goals'] = null;
        $data['goalies_savePct'] = null;
    }

    return $data;
}
