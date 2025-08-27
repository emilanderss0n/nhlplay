<?php
/**
 * Standings controller - returns standings data and caching logic
 */
function standings_get_data($app = null)
{
    // Use cache under project root (ajax files expect ../cache when run from ajax)
    $cacheFile = __DIR__ . '/../../cache/standings-league.json';
    $cacheTime = 30 * 30;
    $ApiUrl = NHLApi::standingsNow();

    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        $standing = json_decode(file_get_contents($cacheFile));
    } else {
        $curl = curlInit($ApiUrl);
        $standing = json_decode($curl);
        // safe write
        @file_put_contents($cacheFile, json_encode($standing));
    }

    return $standing;
}
