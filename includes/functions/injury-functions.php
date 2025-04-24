<?php
function getInjuriesLeague(){
    // Fix path to be absolute instead of relative
    $cacheFile = dirname(__DIR__, 2) . '/cache/injuries.json';
    $cacheTime = 30 * 30; // Cache time in seconds

    // Make sure cache directory exists
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }

    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        $injuryData = json_decode(file_get_contents($cacheFile), true);
    } else {
        $ApiUrl = 'https://datacrunch.9c9media.ca/statsapi/sports/hockey/leagues/nhl/playerInjuries?type=json';
        $curl = curlInit($ApiUrl);
        $injuryData = json_decode($curl, true);

        // Write the data to the cache file
        file_put_contents($cacheFile, json_encode($injuryData));
    }
    
    $currentDate = strtotime('now');
    $threeDaysAgo = strtotime('-3 days');

    ob_start();

    foreach ($injuryData as $entry) {
        $playerInjuries = $entry['playerInjuries'];

        if (empty($playerInjuries)) {
            continue;
        }

        $competitor = $entry['competitor'];
        $teamName = $competitor['name'];
        $shortName = $competitor['shortName'];
        $teamID = abbrevToTeamId($shortName);
        $teamColor = teamToColor($teamID);

        $hasRecentInjuries = false;

        foreach ($playerInjuries as $injury) {
            $injuryDate = strtotime($injury['date']);
            
            if ($injuryDate >= $threeDaysAgo && $injuryDate <= $currentDate) {
                if (!$hasRecentInjuries) {
                    echo "<div class='team-injuries item'>";
                    echo "<div class='team-logo' style='background-image: url(". BASE_URL ."/assets/img/teams/". $teamID .".svg);'></div>";
                    echo "<div class='team-fill' style='background: linear-gradient(142deg, ".$teamColor." 0%, var(--dark-bg-color) 60%);: ". $teamColor ."'></div>";
                    echo "<div class='content'>";
                    echo "<div class='head'>";
                    echo "<h3>$teamName</h3>";
                    echo "</div>";
                    echo "<ul>";
                    $hasRecentInjuries = true;
                }

                $player = $injury['player'];
                $playerName = $player['displayName'];
                $playerStatus = $injury['status'];
                $injuryDateFormatted = date('Y-m-d', $injuryDate);
                $injuryDescription = $injury['description'];

                echo "<li><strong>$playerName</strong> - $injuryDateFormatted - $playerStatus - $injuryDescription</li>";
            }
        }

        if ($hasRecentInjuries) {
            echo "</ul>";
            echo "</div>";
            echo "</div>";
        }
    }

    $output = ob_get_clean();
    echo $output;
}

function getInjuredPlayerIds($teamAbbrev) {
    $cacheFile = dirname(__DIR__, 2) . '/cache/injuries.json';
    $cacheTime = 30 * 30;

    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        $injuryData = json_decode(file_get_contents($cacheFile), true);
    } else {
        $ApiUrl = 'https://datacrunch.9c9media.ca/statsapi/sports/hockey/leagues/nhl/playerInjuries?type=json';
        $curl = curlInit($ApiUrl);
        $injuryData = json_decode($curl, true);
        file_put_contents($cacheFile, json_encode($injuryData));
    }

    $injuredPlayerIds = [];

    if (!is_array($injuryData)) {
        return $injuredPlayerIds;
    }

    foreach ($injuryData as $entry) {
        $playerInjuries = $entry['playerInjuries'];
        if (empty($playerInjuries)) {
            continue;
        }

        $competitor = $entry['competitor'];
        $shortName = $competitor['shortName'];

        if ($shortName != $teamAbbrev) {
            continue;
        }

        foreach ($playerInjuries as $injury) {
            $player = $injury['player'];
            $playerName = $player['displayName'];
            
            // Search NHL API for player ID
            $searchUrl = 'https://search.d3.nhle.com/api/v1/search/player?culture=en-us&limit=1&q=' . urlencode($playerName) . '&active=true';
            $searchCurl = curl_init();
            curl_setopt($searchCurl, CURLOPT_URL, $searchUrl);
            curl_setopt($searchCurl, CURLOPT_RETURNTRANSFER, true);
            $searchResult = curl_exec($searchCurl);
            curl_close($searchCurl);
            
            $searchData = json_decode($searchResult, true);
            if (isset($searchData[0]['playerId'])) {
                $injuredPlayerIds[] = $searchData[0]['playerId'];
            }
        }
    }

    return $injuredPlayerIds;
}

function getInjuriesTeam($teamAbbrev){
    // Fix path to be absolute instead of relative
    $cacheFile = dirname(__DIR__, 2) . '/cache/injuries.json';
    $cacheTime = 30 * 30; // Cache time in seconds

    // Make sure cache directory exists
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }

    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        $injuryData = json_decode(file_get_contents($cacheFile), true);
    } else {
        $ApiUrl = 'https://datacrunch.9c9media.ca/statsapi/sports/hockey/leagues/nhl/playerInjuries?type=json';
        $curl = curlInit($ApiUrl);
        $injuryData = json_decode($curl, true);

        file_put_contents($cacheFile, json_encode($injuryData));
    }

    if (!is_array($injuryData)) {
        echo "<div class='content'>No injury data available.</div>";
        return;
    }

    foreach ($injuryData as $entry) {
        $playerInjuries = $entry['playerInjuries'];

        if (empty($playerInjuries)) {
            continue;
        }

        $competitor = $entry['competitor'];
        $teamName = $competitor['name'];
        $shortName = $competitor['shortName'];

        if ($shortName != $teamAbbrev) {
            continue;
        }

        $hasTeamInjuries = false;

        foreach ($playerInjuries as $injury) {
            if (!$hasTeamInjuries) {
                echo "<div class='content'>";
                echo "<ul>";
                $hasTeamInjuries = true;
            }

            $player = $injury['player'];
            $playerName = $player['displayName'];
            $playerStatus = $injury['status'];
            $injuryDateFormatted = date('Y-m-d', strtotime($injury['date']));
            $injuryDescription = $injury['description'];

            echo "<li><strong>$playerName</strong> - $injuryDateFormatted - $playerStatus - $injuryDescription</li>";
        }

        if ($hasTeamInjuries) {
            echo "</ul>";
            echo "</div>";
        }
    }
}

function getInjuriesTeamCount($teamAbbrev){
    // Fix path to be absolute instead of relative
    $cacheFile = dirname(__DIR__, 2) . '/cache/injuries.json';
    $cacheTime = 30 * 30;

    // Make sure cache directory exists
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }

    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
        $injuryData = json_decode(file_get_contents($cacheFile), true);
    } else {
        $ApiUrl = 'https://datacrunch.9c9media.ca/statsapi/sports/hockey/leagues/nhl/playerInjuries?type=json';
        $curl = curlInit($ApiUrl);
        $injuryData = json_decode($curl, true);

        file_put_contents($cacheFile, json_encode($injuryData));
    }

    if (!is_array($injuryData)) {
        return 0;
    }
    
    $injuryCount = 0;

    foreach ($injuryData as $entry) {
        $playerInjuries = $entry['playerInjuries'];

        if (empty($playerInjuries)) {
            continue;
        }

        $competitor = $entry['competitor'];
        $shortName = $competitor['shortName'];

        if ($shortName != $teamAbbrev) {
            continue;
        }

        foreach ($playerInjuries as $injury) {
            $injuryCount++;
        }
    }

    return $injuryCount;
}
