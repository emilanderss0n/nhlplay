<?php
function teamToName($id){
    global $teamNames;
    return $teamNames[$id];
}

function teamToColor($id){
    global $teamColors;
    return isset($teamColors[$id]) ? $teamColors[$id] : '#000000';
}

function idToTeamAbbrev($teamId){
    global $teamIdToAbbrev;
    return $teamIdToAbbrev[$teamId];
}

function idToTeamAbbrevInjuries($teamId){
    global $teamIdToAbbrev2;
    return $teamIdToAbbrev2[$teamId];
}

function abbrevToTeamId($abbrev){
    global $teamAbbrev;
    return $teamAbbrev[$abbrev];
}

function abbrevToTeamIdInjuries($abbrev){
    global $teamAbbrev2;
    return $teamAbbrev2[$abbrev];
}

function teamNameToIdConvert($teamName){
    global $teamNameToId;
    return $teamNameToId[$teamName];
}

function teamSNtoID($id){
    global $teamIdConvertSN;
    return $teamIdConvertSN[$id];
}

function getTeamRosterStats($teamAbbrev, $season) {
    // Use the new NHL API utility instead of building URL manually
    $ApiUrl = NHLApi::teamStats($teamAbbrev, $season, '2');
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

function getTeamRosterInfo($teamAbbrev, $season) {
    // Use caching to avoid repeated API calls
    $cacheFile = 'cache/team-roster-' . strtolower($teamAbbrev) . '-' . $season . '.json';
    $cacheLifetime = 3600; // 1 hour cache
    
    // Use the new NHL API utility
    $apiUrl = NHLApi::teamRoster($teamAbbrev, $season);
    
    return fetchData(
        $apiUrl,
        $cacheFile,
        $cacheLifetime
    );
}

function getTeamStats($teamAbbrev) {
    // Use the new NHL API utility
    $ApiUrl = NHLApi::standingsNow();
    $curl = curlInit($ApiUrl);
    $teamStats = json_decode($curl);
    if (isset($teamStats->standings) && is_array($teamStats->standings)) {
        foreach ($teamStats->standings as $standing) {
            if (isset($standing->teamAbbrev->default) && $standing->teamAbbrev->default == $teamAbbrev) {
                return $standing;
            }
        }
    }
    return null;
}

function getTeamStatsAdv($activeTeam, $season) {
    // Build the exact URL that was working before, manually
    $baseUrl = 'https://api.nhle.com/stats/rest/en/team/summary';
    $sort = urlencode('[{"property":"points","direction":"DESC"},{"property":"wins","direction":"DESC"},{"property":"teamId","direction":"ASC"}]');
    $cayenneExp = urlencode("gameTypeId=2 and teamId={$activeTeam} and seasonId<={$season} and seasonId>={$season}");
    $factCayenneExp = urlencode('gamesPlayed>=1');
    
    $ApiUrl = $baseUrl . 
              '?isAggregate=false' .
              '&isGame=false' .
              '&sort=' . $sort .
              '&start=0' .
              '&limit=50' .
              '&factCayenneExp=' . $factCayenneExp .
              '&cayenneExp=' . $cayenneExp;
    
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

function getTeamMedianAge($teamRosterInfo) {
    $playerAges = array();
    foreach (['forwards', 'defensemen', 'goalies'] as $position) {
        foreach ($teamRosterInfo->{$position} as $player) {
            $birthDate = $player->birthDate;
            $age = date_diff(date_create($birthDate), date_create('today'))->y;
            array_push($playerAges, $age);
        }
    }
    sort($playerAges);
    $count = count($playerAges);
    return ($count % 2 == 0) ? ($playerAges[$count/2-1] + $playerAges[$count/2])/2 : $playerAges[floor($count/2)];
}

function getTeamSchedules($teamAbbrev) {
    // Use the new NHL API utility
    $ApiUrl = NHLApi::teamScoreboard($teamAbbrev);
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

function getTeamLogo($teamId) {
    return 'assets/img/teams/' . $teamId . '.svg';
}

function renderAtlanticDivision($standing, $detect) {
    renderDivisionTable('A', 'Atlantic', $standing, $detect);
}

function renderCentralDivision($standing, $detect) {
    renderDivisionTable('C', 'Central', $standing, $detect);
}

function renderMetropolitanDivision($standing, $detect) {
    renderDivisionTable('M', 'Metropolitan', $standing, $detect);
}

function renderPacificDivision($standing, $detect) {
    renderDivisionTable('P', 'Pacific', $standing, $detect);
}

function renderDivisionTable($divisionAbbrev, $divisionName, $standing, $detect) {
    ?>
    <h3 class="header-text-gradient"><?= $divisionName ?></h3>
    <table class="divisionTable hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>POS</td>
            <td>TEAMS</td>
            <td>GP</td>
            <td>W</td>
            <td>L</td>
            <td>OT</td>
            <td>PTS</td>
            <?php if (!$detect->isMobile()) { echo '<td>GS</td><td>GA</td><td>L10R</td><td class="no-sort">ST</td>'; } ?>
        </thead>
        <tbody>
            <?php 
            foreach ($standing->standings as $teamStand) {
            if ($teamStand->divisionAbbrev == $divisionAbbrev) { ?>
            <tr class="team">
                <td class="position"><strong><?= $teamStand->divisionSequence ?></strong></td>
                <td class="name">
                    <img height="32" width="32" src="<?= $teamStand->teamLogo ?>" alt="<?= $teamStand->teamName->default ?>" />
                    <a id="team-link" href="#" data-link="<?= abbrevToTeamId($teamStand->teamAbbrev->default) ?>"><?= $teamStand->teamName->default ?></a>
                </td>
                <td class="gp"><?= $teamStand->gamesPlayed ?></td>
                <td class="wins"><?= $teamStand->wins ?></td>
                <td class="losses"><?= $teamStand->losses ?></td>
                <td class="ot"><?= $teamStand->roadOtLosses + $teamStand->homeOtLosses ?></td>
                <td class="points"><strong><?= $teamStand->points ?></strong></td>
                <?php if (!$detect->isMobile()) { echo '
                    <td class="goalsScored">'. $teamStand->goalFor .'</td>
                    <td class="goalsagainst">'. $teamStand->goalAgainst .'</td>
                    <td class="lastRank" title="Last 10 games, rank in league">'. $teamStand->leagueL10Sequence .'</td>
                    <td class="streak">'. $teamStand->streakCode . $teamStand->streakCount .'</td>
                '; } ?>
            </tr>
            <?php }} ?>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>GP</strong> - Games Played</p>
        <p><strong>W</strong> - Wins</p>
        <p><strong>L</strong> - Losses</p>
        <p><strong>OT</strong> - Overtime Losses</p>
        <p><strong>PTS</strong> - Points</p>
        <p><strong>GS</strong> - Goals Scored</p>
        <p><strong>GA</strong> - Goals Against</p>
        <p><strong>ST</strong> - Streak</p>
    </div>
    <?php
}

function renderLeagueTable($standing, $detect) {
    ?>
    <table id="leagueTable" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>POS</td>
            <td>TEAMS</td>
            <td>GP</td>
            <td>W</td>
            <td>L</td>
            <td>OT</td>
            <td>PTS</td>
            <?php if (!$detect->isMobile()) { echo '<td>GS</td><td>GA</td><td>L10R</td><td class="no-sort">STR</td>'; } ?>
        </thead>
        <tbody>
            <?php 
            foreach ($standing->standings as $teamStand) { ?>
            <tr class="team">
                <td class="position"><strong><?= $teamStand->leagueSequence ?></strong></td>
                <td class="name">
                    <img height="32" width="32" src="<?= $teamStand->teamLogo ?>" alt="<?= $teamStand->teamName->default ?>" />
                    <a id="team-link" href="#" data-link="<?= abbrevToTeamId($teamStand->teamAbbrev->default) ?>"><?= $teamStand->teamName->default ?></a>
                </td>
                <td class="gp"><?= $teamStand->gamesPlayed ?></td>
                <td class="wins"><?= $teamStand->wins ?></td>
                <td class="losses"><?= $teamStand->losses ?></td>
                <td class="ot"><?= $teamStand->roadOtLosses + $teamStand->homeOtLosses ?></td>
                <td class="points"><strong><?= $teamStand->points ?></strong></td>
                <?php if (!$detect->isMobile()) { ?>
                    <td class="goalsScored"><?= $teamStand->goalFor ?></td>
                    <td class="goalsagainst"><?= $teamStand->goalAgainst ?></td>
                    <td class="lastRank" title="Last 10 games, rank in league"><?= $teamStand->leagueL10Sequence ?></td>
                    <td class="streak">
                    <?php 
                    if(isset($teamStand->streakCode)) {
                        echo $teamStand->streakCode . $teamStand->streakCount;
                    }
                    ?>
                    </td>
                <?php } ?>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>GP</strong> - Games Played</p>
        <p><strong>W</strong> - Wins</p>
        <p><strong>L</strong> - Losses</p>
        <p><strong>OT</strong> - Overtime Losses</p>
        <p><strong>PTS</strong> - Points</p>
        <p><strong>GS</strong> - Goals Scored</p>
        <p><strong>GA</strong> - Goals Against</p>
        <p><strong>ST</strong> - Streak</p>
    </div>
    <?php
}

function renderConferenceTable($conferenceAbbrev, $conferenceName, $standing, $detect) {
    ?>
    <h3 class="header-text-gradient"><?= $conferenceName ?></h3>
    <table class="conferenceTable hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>POS</td>
            <td>TEAMS</td>
            <td>GP</td>
            <td>W</td>
            <td>L</td>
            <td>OT</td>
            <td>PTS</td>
            <?php if (!$detect->isMobile()) { echo '<td>GS</td><td>GA</td><td>L10R</td><td class="no-sort">ST</td>'; } ?>
        </thead>
        <tbody>
            <?php 
            foreach ($standing->standings as $teamStand) {
            if ($teamStand->conferenceAbbrev == $conferenceAbbrev) { ?>
            <tr class="team">
                <td class="position"><strong><?= $teamStand->conferenceSequence ?></strong></td>
                <td class="name">
                    <img height="32" width="32" src="<?= $teamStand->teamLogo ?>" alt="<?= $teamStand->teamName->default ?>" />
                    <a id="team-link" href="#" data-link="<?= abbrevToTeamId($teamStand->teamAbbrev->default) ?>"><?= $teamStand->teamName->default ?></a>
                </td>
                <td class="gp"><?= $teamStand->gamesPlayed ?></td>
                <td class="wins"><?= $teamStand->wins ?></td>
                <td class="losses"><?= $teamStand->losses ?></td>
                <td class="ot"><?= $teamStand->roadOtLosses + $teamStand->homeOtLosses ?></td>
                <td class="points"><strong><?= $teamStand->points ?></strong></td>
                <?php if (!$detect->isMobile()) { echo '
                    <td class="goalsScored">'. $teamStand->goalFor .'</td>
                    <td class="goalsagainst">'. $teamStand->goalAgainst .'</td>
                    <td class="lastRank" title="Last 10 games, rank in league">'. $teamStand->leagueL10Sequence .'</td>
                    <td class="streak">'. $teamStand->streakCode . $teamStand->streakCount .'</td>
                '; } ?>
            </tr>
            <?php }} ?>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>GP</strong> - Games Played</p>
        <p><strong>W</strong> - Wins</p>
        <p><strong>L</strong> - Losses</p>
        <p><strong>OT</strong> - Overtime Losses</p>
        <p><strong>PTS</strong> - Points</p>
        <p><strong>GS</strong> - Goals Scored</p>
        <p><strong>GA</strong> - Goals Against</p>
        <p><strong>ST</strong> - Streak</p>
    </div>
    <?php
}

function renderTeamRoster($teamRosterInfo, $teamRosterStats, $activeTeam, $injuredPlayerIds = []) {
    foreach ($teamRosterInfo->forwards as $player) {
        $matchingStats = null;
        foreach ($teamRosterStats->skaters as $stat) {
            if ($stat->playerId === $player->id) {
                $matchingStats = $stat;
                break;
            }
        }
        renderPlayerCard($player, $matchingStats, $activeTeam, 'forward', $injuredPlayerIds);
    }

    foreach ($teamRosterInfo->defensemen as $player) {
        $matchingStats = null;
        foreach ($teamRosterStats->skaters as $stat) {
            if ($stat->playerId === $player->id) {
                $matchingStats = $stat;
                break;
            }
        }
        renderPlayerCard($player, $matchingStats, $activeTeam, 'defenseman', $injuredPlayerIds);
    }

    foreach ($teamRosterInfo->goalies as $player) {
        $matchingStats = null;
        foreach ($teamRosterStats->goalies as $stat) {
            if ($stat->playerId === $player->id) {
                $matchingStats = $stat;
                break;
            }
        }
        renderPlayerCard($player, $matchingStats, $activeTeam, 'goalie', $injuredPlayerIds);
    }
}

function calculateAdvantagePoints($home, $away, $homeRecord, $awayRecord, $homeLast10, $awayLast10) {
    $homePoints = 0;
    $awayPoints = 0;
    $totalPossiblePoints = 8.5; // Total possible points (4 regular + 1 season record + 2 last 10 + 0.5 faceoff)
    
    // Compare PP%
    if($home->ppPctg > $away->ppPctg) $homePoints++;
    else if($away->ppPctg > $home->ppPctg) $awayPoints++;
    
    // Compare PK%
    if($home->pkPctg > $away->pkPctg) $homePoints++;
    else if($away->pkPctg > $home->pkPctg) $awayPoints++;
    
    // Compare Goals For per Game
    if($home->goalsForPerGamePlayed > $away->goalsForPerGamePlayed) $homePoints++;
    else if($away->goalsForPerGamePlayed > $home->goalsForPerGamePlayed) $awayPoints++;
    
    // Compare Goals Against per Game (lower is better)
    if($home->goalsAgainstPerGamePlayed < $away->goalsAgainstPerGamePlayed) $homePoints++;
    else if($away->goalsAgainstPerGamePlayed < $home->goalsAgainstPerGamePlayed) $awayPoints++;
    
    // Compare Faceoff Win % (0.5 points)
    if($home->faceoffWinningPctg > $away->faceoffWinningPctg) $homePoints += 0.5;
    else if($away->faceoffWinningPctg > $home->faceoffWinningPctg) $awayPoints += 0.5;
    
    // Compare season record (1 point)
    $homeRecParts = explode("-", $homeRecord);
    $awayRecParts = explode("-", $awayRecord);
    $homeWinPct = intval($homeRecParts[0]) / (array_sum($homeRecParts));
    $awayWinPct = intval($awayRecParts[0]) / (array_sum($awayRecParts));
    if($homeWinPct > $awayWinPct) $homePoints += 1;
    else if($awayWinPct > $homeWinPct) $awayPoints += 1;
    
    // Compare last 10 record (2 points)
    $homeLast10Parts = explode("-", $homeLast10);
    $awayLast10Parts = explode("-", $awayLast10);
    $homeLast10WinPct = intval($homeLast10Parts[0]) / (array_sum($homeLast10Parts));
    $awayLast10WinPct = intval($awayLast10Parts[0]) / (array_sum($awayLast10Parts));
    if($homeLast10WinPct > $awayLast10WinPct) $homePoints += 3;
    else if($awayLast10WinPct > $homeLast10WinPct) $awayPoints += 3;
    
    // Convert to percentages and normalize them to always total 100%
    $homePercentage = ($homePoints / $totalPossiblePoints) * 100;
    $awayPercentage = ($awayPoints / $totalPossiblePoints) * 100;
    
    // If neither team has points, give them 50-50
    if ($homePoints == 0 && $awayPoints == 0) {
        return [50, 50];
    }
    
    // Normalize percentages to total 100%
    $total = $homePercentage + $awayPercentage;
    if ($total > 0) {
        $homePercentage = ($homePercentage / $total) * 100;
        $awayPercentage = ($awayPercentage / $total) * 100;
    }
    
    return [$awayPercentage, $homePercentage];
}

function getTeamRedditSub($teamAbbrev) {
    global $teamRedditSubs;
    return isset($teamRedditSubs[$teamAbbrev]) ? $teamRedditSubs[$teamAbbrev] : null;
}

/**
 * Fetch and render team prospects section
 * Uses the new NHLApi::teamProspects endpoint and caches the result
 * @param string $teamAbbrev Team abbreviation (e.g., 'BOS')
 */
function teamProspects($teamAbbrev) {
    if (empty($teamAbbrev)) return;

    $cacheFile = 'cache/team-prospects-' . strtolower($teamAbbrev) . '.json';
    $cacheLifetime = 12 * 3600; // 12 hours cache

    // Build API URL using NHLApi helper
    $apiUrl = NHLApi::teamProspects($teamAbbrev);

    $data = fetchData($apiUrl, $cacheFile, $cacheLifetime);

    if (!$data) {
        echo '<div class="item">No prospect data available</div>';
        return;
    }

    // The API frequently returns prospects grouped by position: forwards, defensemen, goalies
    $prospects = [];

    // Helper to merge arrays if present
    $maybeArray = function($obj, $key) {
        if (isset($obj->{$key}) && is_array($obj->{$key})) return $obj->{$key};
        return [];
    };

    // Check common locations for grouped data
    $prospects = array_merge(
        $maybeArray($data, 'forwards'),
        $maybeArray($data, 'defensemen'),
        $maybeArray($data, 'goalies')
    );

    // Some responses nest actual payload under data
    if (empty($prospects) && isset($data->data) && is_object($data->data)) {
        $prospects = array_merge(
            $maybeArray($data->data, 'forwards'),
            $maybeArray($data->data, 'defensemen'),
            $maybeArray($data->data, 'goalies')
        );
    }

    // Fall back to other shapes
    if (empty($prospects)) {
        if (isset($data->data) && is_array($data->data)) {
            $prospects = $data->data;
        } elseif (is_array($data)) {
            $prospects = $data;
        } elseif (isset($data->prospects) && is_array($data->prospects)) {
            $prospects = $data->prospects;
        }
    }

    if (empty($prospects)) {
        echo '<div class="item">No prospects found</div>';
        return;
    }

    // Normalize and render each prospect
    foreach ($prospects as $p) {
        // ID
        $playerId = isset($p->id) ? $p->id : (isset($p->playerId) ? $p->playerId : '');

        // Names: many endpoints use nested objects with a 'default' property
        $firstName = '';
        $lastName = '';
        if (isset($p->firstName)) {
            $firstName = is_object($p->firstName) && isset($p->firstName->default) ? $p->firstName->default : $p->firstName;
        } elseif (isset($p->player) && isset($p->player->firstName)) {
            $firstName = is_object($p->player->firstName) && isset($p->player->firstName->default) ? $p->player->firstName->default : $p->player->firstName;
        }

        if (isset($p->lastName)) {
            $lastName = is_object($p->lastName) && isset($p->lastName->default) ? $p->lastName->default : $p->lastName;
        } elseif (isset($p->player) && isset($p->player->lastName)) {
            $lastName = is_object($p->player->lastName) && isset($p->player->lastName->default) ? $p->player->lastName->default : $p->player->lastName;
        }

        // Position code
        $positionCode = isset($p->positionCode) ? $p->positionCode : (isset($p->position) ? $p->position : '');

        // Headshot
        $headshot = 'assets/img/player-placeholder.png';
        if (isset($p->headshot)) {
            $headshot = $p->headshot;
        } elseif (isset($p->headshotUrl)) {
            $headshot = $p->headshotUrl;
        } elseif (isset($p->player) && isset($p->player->headshot)) {
            $headshot = $p->player->headshot;
        }

        $number = isset($p->sweaterNumber) ? $p->sweaterNumber : (isset($p->player) && isset($p->player->sweaterNumber) ? $p->player->sweaterNumber : '00');

        echo '<a class="prospect" id="player-link" href="#" data-link="' . htmlspecialchars($playerId) . '">';
        
        echo '<div class="info">';
        echo '<img class="head" src="' . htmlspecialchars($headshot) . '"/>';
        echo '<div class="text">';
        echo '<div class="top">';
        echo '<div class="jersey"><span>#</span>' . htmlspecialchars($number) . '</div>';
        echo '<div class="position">' . htmlspecialchars(positionCodeToName($positionCode)) . '</div>';
        echo '</div>'; // .top
        echo '<div class="name">' . htmlspecialchars($firstName) . ' ' . htmlspecialchars($lastName) . '</div>';
        echo '</div>'; // .text
        echo '</div>'; // .info
        echo '</a>';
    }
}
