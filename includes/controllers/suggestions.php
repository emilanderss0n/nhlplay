<?php
/**
 * Suggestions controller - player search helper
 */
function suggestions_search_players($keystroke, $season = null, $limit = 20)
{
    $season = $season ?? date('Y');
    $q = urlencode($keystroke);
    $ApiUrl = "https://search.d3.nhle.com/api/v1/search/player?culture=en-us&limit={$limit}&q={$q}&active=true";
    $curl = curlInit($ApiUrl);
    $players = json_decode($curl);
    return $players ?: [];
}
