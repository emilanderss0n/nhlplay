<?php
include_once __DIR__ . '/schedule.php';

function recaps_get_recent($daysBack = 4)
{
    $date = date('Y-m-d', strtotime("-{$daysBack} days"));
    $ApiUrl = NHLApi::scheduleByDate($date);
    $cacheFile = __DIR__ . '/../../cache/recaps-' . $date . '.json';
    $cacheTime = 60 * 10; // 10 minutes
    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        return json_decode(file_get_contents($cacheFile));
    }
    $curl = curlInit($ApiUrl);
    $res = json_decode($curl);
    @file_put_contents($cacheFile, json_encode($res));
    return normalize_schedule($res);
}
