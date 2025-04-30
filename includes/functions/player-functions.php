<?php
function positionCodeToName($position){
    global $positionCodes;
    return $positionCodes[$position];
}

function positionCodeToName2($position){
    global $positionCodes2;
    return $positionCodes2[$position];
}

function positionCodeToName3($position){
    global $positionCodes3;
    return $positionCodes3[$position];
}

function getPlayerBioStat($playerID, $season) {
    $ch = curl_init();
    $ApiUrl = 'https://api.nhle.com/stats/rest/en/skater/bios?limit=1&playerId='. $playerID .'&cayenneExp=seasonId="'. $season .'"';
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.snoop.com/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $ApiUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $results = curl_exec($ch);
    curl_close($ch);
    
    $playerBios = json_decode($results);
    foreach($playerBios->data as $playerBio) {
        $stat = $playerBio;
        return $stat;
    }
}

function renderPlayerCard($player, $matchingStats, $activeTeam, $type, $injuredPlayerIds = []) {
    $isInjured = in_array($player->id, $injuredPlayerIds);
    ?>
    <a class="player <?= strtolower(positionCodeToName2($player->positionCode)) ?><?php if (isset($player->rookie) == 'true') { echo ' rookie'; } ?><?php if ($isInjured) { echo ' injured'; } ?>" id="player-link" data-link="<?= $player->id ?>" href="#" data-points="<?= $matchingStats ? $matchingStats->points : 0 ?>">
        <div class="jersey"><span>#</span><?= $player->sweaterNumber ?></div>
        <div class="info">
            <div class="headshot">
                <img class="head" id="canTop" height="400" width="400" src="<?= $player->headshot ?>"></img>
                <img class="team-img" height="400" width="400" src="<?= getTeamLogo($activeTeam) ?>" />
                <div class="team-fill" style="background: linear-gradient(142deg, <?= teamToColor($activeTeam) ?> 0%, rgba(255,255,255,0) 58%);"></div>
            </div>
            <div class="text">
                <div class="position"><?= positionCodeToName($player->positionCode) ?></div>
                <div class="name"><?php if ($isInjured) { echo '<i class="bi bi-bandaid"></i>'; } ?><?= $player->firstName->default ?> <?= $player->lastName->default ?></div>
            </div>
        </div>
        <div class="stats">
            <?php if ($type === 'goalie') { ?>
                <div class="gamesplayed">GP: <strong><?= $matchingStats ? $matchingStats->gamesPlayed : '0' ?></strong></div>
                <div class="goals">SV%: <strong><?= $matchingStats ? number_format($matchingStats->savePercentage, 3, '.', '') : '0.000' ?></strong></div>
                <div class="assists">GAA: <strong><?= $matchingStats ? number_format($matchingStats->goalsAgainstAverage, 2, '.', '') : '0.00' ?></strong></div>
                <div class="points">W: <strong><?= $matchingStats ? $matchingStats->wins : '0' ?></strong></div>
            <?php } else { ?>
                <div class="gamesplayed">GP: <strong><?= $matchingStats ? $matchingStats->gamesPlayed : '0' ?></strong></div>
                <div class="goals">Goals: <strong><?= $matchingStats ? $matchingStats->goals : '0' ?></strong></div>
                <div class="assists">Assists: <strong><?= $matchingStats ? $matchingStats->assists : '0' ?></strong></div>
                <div class="points">Points: <strong><?= $matchingStats ? $matchingStats->points : '0' ?></strong></div>
            <?php } ?>
        </div>
    </a>
    <?php
}

function renderPlayerStatsRow($stats, $formattedSAT = 'N/A', $formattedUSAT = 'N/A', $evenStrengthGoalDiff = '0') {
    if (!$stats) return '<tr><td colspan="12">No stats available</td></tr>';
    
    return sprintf('
        <tr>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
        </tr>',
        $stats->gamesPlayed ?? '',
        $stats->goals ?? '',
        $stats->assists ?? '',
        $stats->points ?? '',
        isset($stats->points) && isset($stats->gamesPlayed) ? number_format((float)$stats->points / $stats->gamesPlayed, 2, '.', '') : '',
        $stats->plusMinus ?? '',
        $stats->pim ?? '',
        $stats->shots ?? '',
        isset($stats->shootingPctg) ? number_format((float)$stats->shootingPctg * 100, 1, '.', '') : '',
        $formattedSAT,
        $formattedUSAT,
        $evenStrengthGoalDiff
    );
}

function renderGoalieStatsRow($stats) {
    if (!$stats) return '<tr><td colspan="5">No stats available</td></tr>';
    
    return sprintf('
        <tr>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s (%s)</td>
        </tr>',
        $stats->gamesPlayed ?? '',
        isset($stats->savePctg) ? number_format((float)$stats->savePctg * 100, 2, '.', '') : '',
        isset($stats->goalsAgainstAvg) ? number_format((float)$stats->goalsAgainstAvg, 2, '.', '') : '',
        isset($stats->wins) ? (float)$stats->wins : '',
        isset($stats->losses) ? (float)$stats->losses : '',
        isset($stats->otLosses) ? (float)$stats->otLosses : ''
    );
}

function renderPhoneStatsDisplay($stats, $formattedSAT = 'N/A', $formattedUSAT = 'N/A', $evenStrengthGoalDiff = '0', $isSkater = true) {
    if ($isSkater) {
        return sprintf('
            <div class="stat">
                <div class="label">Games</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">Goals</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">Assists</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">Points</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">PPG</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">+/-</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">PIM</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">Shots</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">S%%</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">SAT%%</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">USAT%%</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">EV GD</div>
                <div class="value">%s</div>
            </div>',
            $stats->gamesPlayed ?? '',
            $stats->goals ?? '',
            $stats->assists ?? '',
            $stats->points ?? '',
            isset($stats->points) && isset($stats->gamesPlayed) ? number_format((float)$stats->points / $stats->gamesPlayed, 2, '.', '') : '',
            $stats->plusMinus ?? '',
            $stats->pim ?? '',
            $stats->shots ?? '',
            isset($stats->shootingPctg) ? number_format((float)$stats->shootingPctg * 100, 1, '.', '') : '',
            $formattedSAT,
            $formattedUSAT,
            $evenStrengthGoalDiff
        );
    } else {
        return sprintf('
            <div class="stat">
                <div class="label">Games</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">SV%%</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">GAA</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">Wins</div>
                <div class="value">%s</div>
            </div>
            <div class="stat">
                <div class="label">Losses (OT)</div>
                <div class="value">%s (%s)</div>
            </div>',
            $stats->gamesPlayed ?? '',
            isset($stats->savePctg) ? number_format((float)$stats->savePctg * 100, 2, '.', '') : '',
            isset($stats->goalsAgainstAvg) ? number_format((float)$stats->goalsAgainstAvg, 2, '.', '') : '',
            isset($stats->wins) ? (float)$stats->wins : '',
            isset($stats->losses) ? (float)$stats->losses : '',
            isset($stats->otLosses) ? (float)$stats->otLosses : ''
        );
    }
}

function getThreeStars($season) {
    $ApiUrl = 'https://records.nhl.com/site/api/media-three-star?include=threeStarType&mapBy=seasonId&include=player.fullName&include=player.id&include=player.sweaterNumber&include=player.position&include=team.fullName&include=team.triCode&include=team.id&cayenneExp=seasonId="'. $season .'"&sort=id&dir=ASC';
    $curl = curlInit($ApiUrl);
    $result = json_decode($curl);
    $output = '';
    $i = 0;

    if (isset($result->data->{$season}) && is_array($result->data->{$season})) {
        foreach (array_reverse($result->data->{$season}) as $stars) {
            if ($stars->threeStarType->id == 1 || $stars->threeStarType->id == 2 || $stars->threeStarType->id == 3) {
                if($i <= 2) {
                    $fullName = $stars->player->fullName;
                    $nameParts = explode(" ", $fullName);
                    $initial = strtoupper($nameParts[0][0]);
                    $lastName = $nameParts[1];
                    $formattedName = $initial . ". " . $lastName;
                    
                    $output .= '<a href="#" id="player-link" class="item player star-' . $stars->threeStarType->id . '" data-link="' . $stars->player->id . '">';
                    $output .= '<div class="place"></div>';
                    $output .= '<img class="head" height="200" width="200" src="https://assets.nhle.com/mugs/nhl/' . $season . '/' . $stars->team->triCode . '/' . $stars->player->id . '.png" />';
                    $output .= '<img class="team-img" src="assets/img/teams/'. $stars->team->id .'.svg" width="200" height="200" />';
                    $output .= '<div class="team-color" style="background: linear-gradient(142deg, '. teamToColor($stars->team->id) .' 0%, rgba(255,255,255,0) 58%); right: 0;"></div>';
                    $output .= '<div class="player-desc">';
                    $output .= '<h3>' . $formattedName . '</h3>';
                    $output .= '<div class="stats">' . $stars->team->triCode . ' - #' . $stars->player->sweaterNumber . ' - ' . $stars->player->position . '</div>';
                    $output .= '</div></a>';
                    $i++;
                }
            }
        }
    } else {
        $output = "<p>No data available for the selected season.</p>";
    }
    return $output;
}

function renderLastGames($lastGames, $isSkater) {
    ob_start();
    ?>
    <div class="title stats">
        <h3 class="header-text">Last Games</h3>
    </div>
    <table id="last-games">
        <?php if ($isSkater) { ?>
            <thead>
                <td>Against</td>
                <td>Date</td>
                <td>G</td>
                <td>A</td>
                <td>P</td>
                <td>+/-</td>
                <td>PIM</td>
                <td class="tablet-show">Shifts</td>
                <td>TOI</td>
            </thead>
            <tbody>
            <?php
            foreach (array_slice($lastGames, 0, 10) as $lastGame) {
                $gameDate = date_create($lastGame->gameDate);
                ?>
                <tr>
                    <td class="image"><img class="opp-img" src="<?= BASE_URL ?>/assets/img/teams/<?= abbrevToTeamId($lastGame->opponentAbbrev) ?>.svg" width="30" height="30" /> <p><?= $lastGame->opponentAbbrev ?></p></td>
                    <td><?= date_format($gameDate, 'F j'); ?></td>
                    <td><?= $lastGame->goals ?? '' ?></td>
                    <td><?= $lastGame->assists ?? '' ?></td>
                    <td><?= $lastGame->points ?? '' ?></td>
                    <td><?= $lastGame->plusMinus ?? '' ?></td>
                    <td><?= $lastGame->pim ?? '' ?></td>
                    <td class="tablet-show"><?= $lastGame->shifts ?? '' ?></td>
                    <td><?= $lastGame->toi ?? '' ?></td>
                </tr>
            <?php } ?>
            </tbody>
        <?php } else { ?>
            <thead>
                <td>Against</td>
                <td>Date</td>
                <td>Result</td>
                <td>SV%</td>
                <td>Saves</td>
                <td>GA</td>
                <td>TOI</td>
            </thead>
            <tbody>
            <?php 
            foreach (array_slice($lastGames, 0, 10) as $lastGame) {
                $gameDate = date_create($lastGame->gameDate);
                ?>
                <tr>
                    <td class="image"><img class="opp-img" src="<?= BASE_URL ?>/assets/img/teams/<?= abbrevToTeamId($lastGame->opponentAbbrev) ?>.svg" width="30" height="30" /> <p><?= $lastGame->opponentAbbrev ?></p></td>
                    <td><?= date_format($gameDate, 'F j'); ?></td>
                    <td><?php if (isset($lastGame->decision) && $lastGame->decision == 'W') { echo 'Win'; } else { echo 'Loss'; } ?></td>
                    <td><?= isset($lastGame->savePctg) ? number_format((float)$lastGame->savePctg, 3, '.', '') : '' ?></td>
                    <td><?= $lastGame->shotsAgainst ?? '' ?></td>
                    <td><?= $lastGame->goalsAgainst ?? '' ?></td>
                    <td><?= $lastGame->toi ?? '' ?></td>
                </tr>
            <?php } ?>
            </tbody>
        <?php } ?>
    </table>
    <?php
    return ob_get_clean();
}
