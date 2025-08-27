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
    // For simplicity, we'll call the NHL APIs used elsewhere to gather leaders
    $data['skaters_points'] = json_decode(curlInit(NHLApi::skaterStatsLeaders($season, $playoffs ? '3' : '2', ['points'])));
    $data['skaters_goals'] = json_decode(curlInit(NHLApi::skaterStatsLeaders($season, $playoffs ? '3' : '2', ['goals'])));
    $data['goalies_savePct'] = json_decode(curlInit(NHLApi::goalieStatsLeaders($season, $playoffs ? '3' : '2', ['savePct'])));
    return $data;
}
