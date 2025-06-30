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
    $ApiUrl = 'https://api-web.nhle.com/v1/club-stats/'. $teamAbbrev .'/'. $season .'/2/';
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

function getTeamRosterInfo($teamAbbrev, $season) {
    // Use caching to avoid repeated API calls
    $cacheFile = 'cache/team-roster-' . strtolower($teamAbbrev) . '-' . $season . '.json';
    $cacheLifetime = 3600; // 1 hour cache
    
    return fetchData(
        'https://api-web.nhle.com/v1/roster/' . $teamAbbrev . '/' . $season,
        $cacheFile,
        $cacheLifetime
    );
}

function getTeamStats($teamAbbrev) {
    $ApiUrl = 'https://api-web.nhle.com/v1/standings/now';
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
    $ApiUrl = 'https://api.nhle.com/stats/rest/en/team/summary?isAggregate=false&isGame=false&sort=%5B%7B%22property%22:%22points%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22wins%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22teamId%22,%22direction%22:%22ASC%22%7D%5D&start=0&limit=50&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $activeTeam .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E=' . $season;
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
    $ApiUrl = 'https://api-web.nhle.com/v1/scoreboard/'. $teamAbbrev .'/now';
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
