<?php 
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once dirname(__FILE__) . '/functions/api-functions.php';

// Log function to help debug
function debug_log($message) {
    error_log(print_r($message, true));
}

// These functions shouldn't be executed directly in debug.php
// They should only be called via the EventSource stream setup in header.php
if (!isset($_SERVER['HTTP_ACCEPT']) || $_SERVER['HTTP_ACCEPT'] !== 'text/event-stream') {
    return;
}

function testAPIEndpoint($endpoint, $playerId = null, $seasonId = null, $teamId = null, $teamAbbrev = null, $gameId = null) {
    global $usedEndpoints, $season;
    
    if (!isset($usedEndpoints[$endpoint])) {
        return "Endpoint not found";
    }

    $url = $usedEndpoints[$endpoint];
    // Replace parameters in URL
    $url = str_replace('{$playerId}', $playerId ?? '', $url);
    $url = str_replace('{$season}', $seasonId ?? $season, $url);
    
    $response = curlInit($url);
    $data = json_decode($response, true);
    
    // Safely encode for JavaScript
    $jsonData = json_encode($data);
    
    // Add JavaScript to update the debug-content after page load
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        var debugContent = document.querySelector('#debug-output .debug-content');
        if (debugContent) {
            var data = " . $jsonData . ";
            debugContent.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            document.getElementById('debug-output').style.display = 'block';
        }
    });
    </script>";
    
    return $data;
}

$usedEndpoints = array(
    'playerBio'=>'https://api.nhle.com/stats/rest/en/skater/bios?limit=1&cayenneExp=seasonId={$season}%20and%20playerId={$playerId}',
    'allTeamsInfo'=>'https://api-web.nhle.com/v1/standings/now',
    'playoffBracket'=>'https://api-web.nhle.com/v1/playoff-bracket/2025',
    'playoffCarousel'=>'https://api-web.nhle.com/v1/playoff-series/carousel/{$season}',
    'leagueSeasons'=>'https://api-web.nhle.com/v1/season',
    'draftRankingsNow'=>'https://api-web.nhle.com/v1/draft/rankings/now',
    'draftTracker'=>'https://api-web.nhle.com/v1/draft-tracker/picks/now',
    'playerBannerImage'=>'https://api-web.nhle.com/v1/meta?players={$playerId}',
    'teamFranchisePlayersMostPointsAll'=>'https://records.nhl.com/site/api/skater-career-scoring-regular-season?cayenneExp=points%20%3E=%20200%20and%20franchiseId=6&sort=[{%22property%22:%22points%22,%22direction%22:%22DESC%22},{%22property%22:%22gamesPlayed%22,%22direction%22:%22ASC%22},{%22property%22:%22lastName%22,%22direction%22:%22ASC%22}]&start=0&limit=5',
    'teamFranchisePlayersMostPointsActive'=>'https://records.nhl.com/site/api/skater-career-scoring-regular-season?cayenneExp=points%20%3E=%20200%20and%20teamAbbrevs=%22PIT%22%20and%20activePlayer=true&sort=[{%22property%22:%22points%22,%22direction%22:%22DESC%22},{%22property%22:%22gamesPlayed%22,%22direction%22:%22ASC%22},{%22property%22:%22lastName%22,%22direction%22:%22ASC%22}]&start=0&limit=5',
);

// Function to check if files have changed
function checkForChanges() {
    static $lastModified = [];
    $watchDirs = [
        dirname(__DIR__) . '/assets/css',
    ];
    
    $changed = false;
    $changedFile = '';
    
    foreach ($watchDirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $path = $file->getPathname();
                $mtime = $file->getMTime();
                
                if (!isset($lastModified[$path]) || $lastModified[$path] < $mtime) {
                    $lastModified[$path] = $mtime;
                    $changed = true;
                    $changedFile = $path;
                    break 2;
                }
            }
        }
    }
    
    return [$changed, $changedFile];
}