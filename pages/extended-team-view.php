<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once '../header-extended.php';

$activeTeam = $_GET['active_team'];

$utcTimezone = new DateTimeZone('UTC');
// Use the new NHL API utility
$expands = ['team.stats', 'team.roster', 'roster.person'];
$ApiUrl = NHLApi::legacyTeam($activeTeam, $expands);
$curl = curlInit($ApiUrl);
$team = json_decode($curl);
$teamInfo = $team->teams[0];

$playerAges = array();
$injuredPlayers = 0;
foreach ($teamInfo->roster->roster as $player) {
    $birthDate = $player->person->birthDate;
    $age = date_diff(date_create($birthDate), date_create('today'))->y;
    array_push($playerAges, $age);
    if ($player->person->rosterStatus == "I") {
        $injuredPlayers++;
    }
}
sort($playerAges);
$count = count($playerAges);
$medianAge = ($count % 2 == 0) ? ($playerAges[$count/2-1] + $playerAges[$count/2])/2 : $playerAges[floor($count/2)];

?>
<main>
    <div class="wrap extended">
    <div class="team-header">
        <div class="selected-team">
            <img src="<?= BASE_URL ?>/assets/img/teams/<?= $teamInfo->id ?>.svg" alt="<?= $teamInfo->name ?> logo" />
            <div>
                <h2><?= $teamInfo->name ?></h2>
            </div>
        </div>
        <div class="record-wrap">
            <div class="record" title="Season Record">
                <div class="stat wins">
                    <div>W</div>
                    <?= $teamInfo->teamStats[0]->splits[0]->stat->wins ?>
                </div>
                -
                <div class="stat losses">
                    <div>L</div>
                    <?= $teamInfo->teamStats[0]->splits[0]->stat->losses ?>
                </div>
                -
                <div class="stat ot">
                    <div>OT</div>
                    <?= $teamInfo->teamStats[0]->splits[0]->stat->ot ?>
                </div>
            </div>
            <div class="place" title="League Standing">
                <div>Rank</div>
                <?= $teamInfo->teamStats[0]->splits[1]->stat->pts ?>
            </div>
        </div>
    </div>
    <div class="team-adv-header">
        <div class="team-info">
            <div class="info"><div class="label">Division</div><p><?= $teamInfo->division->name ?></p></div>
            <div class="info"><div class="label">Conference</div><p><?= $teamInfo->conference->name ?></p></div>
            <div class="info"><div class="label">Played Since</div><p><?= $teamInfo->firstYearOfPlay ?></p></div>
            <div class="info"><div class="label">Venue</div><p><?= $teamInfo->venue->name ?></p></div>
            <div class="info"><div class="label">Median Age</div><p><?= $medianAge ?></p></div>
            <div class="info"><div class="label">Injuries</div><p><?= $injuredPlayers ?></p></div>
        </div>
        <div class="team-stats">
            <table class="mainTable" data-order='[[ 0, "asc" ]]'>
                <thead>
                    <td>GP</td>
                    <td>W</td>
                    <td>L</td>
                    <td>OT</td>
                    <td>P</td>
                    <td>P%</td>
                    <td>GF/GP</td>
                    <td>GA/GP</td>
                    <td>PP%</td>
                    <td>PK%</td>
                    <td>S/G</td>
                    <td>SA/G</td>
                    <td>WSF</td>
                    <td>FO%</td>
                    <td>S%</td>
                </thead>
                <tbody>
                    <tr>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->gamesPlayed ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->wins ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->losses ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->ot ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->pts ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->ptPctg ?></td>
                        <td><?= number_format((float)$teamInfo->teamStats[0]->splits[0]->stat->goalsPerGame, 2, '.', ''); ?></td>
                        <td><?= number_format((float)$teamInfo->teamStats[0]->splits[0]->stat->goalsAgainstPerGame, 2, '.', ''); ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->powerPlayPercentage ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->penaltyKillPercentage ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->shotsPerGame ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->shotsAllowed ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->winScoreFirst ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->faceOffWinPercentage ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[0]->stat->shootingPctg ?></td>
                    </tr>
                    <tr>
                        <td>Rank <i class="bi bi-arrow-right"></i></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->wins ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->losses ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->ot ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->pts ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->ptPctg ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->pts ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->ptPctg ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->powerPlayPercentage ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->penaltyKillPercentage ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->shotsPerGame ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->shotsAllowed ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->winScoreFirst ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->faceOffWinPercentage ?></td>
                        <td><?= $teamInfo->teamStats[0]->splits[1]->stat->shootingPctRank ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="team-adv-content">
        <div class="left">
            <h3 class="adv-title">Point Leaders</h3>
            <div class="scoring">
            <?php
            // Use the new NHL API utility
            $params = [
                'leaderCategories' => 'points',
                'limit' => 5,
                'season' => $season,
                'expand' => 'leaders.person'
            ];
            $ApiUrl = NHLApi::legacyTeamLeaders($activeTeam, $params);
            $curl = curlInit($ApiUrl);
            $player = json_decode($curl); ?>
                <div class="player-cont">
                    <?php foreach($player->teamLeaders[0]->leaders as $leader) { ?>
                        <div class="player" data-player-cont="<?= $leader->person->id ?>">
                            <a id="player-link" data-link="<?= $leader->person->id ?>" href="#">
                                <div class="info">
                                    <div class="headshot">
                                            <img class="player-img" src="https://cms.nhl.bamgrid.com/images/headshots/current/168x168/<?= $leader->person->id ?>@2x.png" onError="this.onerror=null;this.src='<?= BASE_URL ?>/assets/img/no-image.png';" />
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
                <div class="points-cont">
                    <?php foreach($player->teamLeaders[0]->leaders as $leader) { ?>
                    <div class="player-text" data-player-text="<?= $leader->person->id ?>">
                        <div class="position">#<?= $leader->person->primaryNumber ?> - <?= $leader->person->primaryPosition->name ?></div>
                        <div class="name"><h3 class="adv-title"><?= $leader->person->fullName ?></h3><?php if ($leader->person->rosterStatus == "I") {echo '<i title="Injured" class="bi bi-bandaid"></i>';} ?></div>
                    </div>
                    <?php } ?>
                    <?php foreach($player->teamLeaders[0]->leaders as $leader) { ?>
                    <div class="points" data-player-id="<?= $leader->person->id ?>">
                        <div class="points-line" data-value="<?= $leader->value ?>"></div>
                        <div class="points-value"><?= $leader->value ?></div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="right">
            <h3>Last Games</h3>
            <div class="last-games">
                <?php 
                $now = date("Y-m-d");
                $then = date("Y-m-d", strtotime("-3 week"));
                // Use the new NHL API utility
                $params = [
                    'teamId' => $activeTeam,
                    'startDate' => $then,
                    'endDate' => $now,
                    'expand' => 'schedule.scoringplays'
                ];
                $ApiUrl = NHLApi::legacySchedule($params);
                $curl = curlInit($ApiUrl);
                $history = json_decode($curl);
                krsort($history->dates);
                foreach(array_slice($history->dates, 0, 4) as $result) {
                $time = new DateTime( $result->games[0]->gameDate, $utcTimezone );
                $gameID = $result->games[0]->gamePk;
                $awayID = $result->games[0]->teams->away->team->id;
                $awayName = $result->games[0]->teams->away->team->name;
                $awayScore = $result->games[0]->teams->away->score;
                $homeID = $result->games[0]->teams->home->team->id;
                $homeName = $result->games[0]->teams->home->team->name;
                $homeScore = $result->games[0]->teams->home->score;
                $arena = $result->games[0]->venue->name;
                $gameState = $result->games[0]->status->abstractGameState;
                if ($gameState == 'Final') {
                ?>
                <div class="game" data-date="<?= $time->format( 'Y-m-d' ) ?>">
                    <div class="time">
                        <i class="bi bi-clock"></i><?= $time->format( 'Y-m-d' ) ?> | <?= $arena ?>
                    </div>
                    <div class="teams">
                        <img src="<?= BASE_URL ?>/assets/img/teams/<?= $awayID ?>.svg" alt="<?= $awayName ?>" />
                        <p>
                            <span><?= $awayScore .' - '. $homeScore ?></span>
                        </p>
                        <img src="<?= BASE_URL ?>/assets/img/teams/<?= $homeID ?>.svg" alt="<?= $homeName ?>" />
                    </div>
                    <div class="scoring-plays">
                    <?php foreach($result->games[0]->scoringPlays as $scp) {
                    if ($scp->team->id == $activeTeam) { ?>
                        <div class="scp-action">
                            <a id="player-link" data-link="<?= $scp->players[0]->player->id ?>" href="#" target="_blank"><?= $scp->players[0]->player->fullName ?></a> - <?= $scp->about->periodTime ?>, <?= $scp->about->ordinalNum ?>
                        </div>
                    <?php }} ?>
                    </div>
                </div>
                <?php }} ?>
            </div>
        </div>
    </div>
    </div>
</main>
<script>
$(document).ready(function() {
    $('.scoring').each(function(index) {
        var $scoring = $(this)

        $scoring.find('.player').eq(0).addClass('active')
        $scoring.find('.player-text').eq(0).addClass('active')
        $scoring.find('.points').eq(0).addClass('active')

        var maxVal = 0
        $scoring.find('.points-cont .points-line').each(function() {
            var value = $(this).data('value')
            if (value > maxVal) {
                maxVal = value
            }
        })

        $scoring.find('.points-cont .points-line').each(function() {
            var value = $(this).data('value')
            var widthPercentage = (value / maxVal) * 100
            $(this).css('width', widthPercentage + '%')
        })

        $scoring.find('.points').hover(
            function() { // mouseenter
                var playerId = $(this).data('player-id')
                $scoring.find('.player').removeClass('active')
                $scoring.find('.points').removeClass('active')
                $scoring.find('.player-text').removeClass('active')
                $scoring.find('.player[data-player-cont="' + playerId + '"]').addClass('active')
                $scoring.find('.player-text[data-player-text="' + playerId + '"]').addClass('active')
                $(this).addClass('active')
            },
            function() { // mouseleave
                var playerId = $(this).data('player-id')
                $scoring.find('.points').removeClass('active')
                $scoring.find('.points[data-player-id="' + playerId + '"]').removeClass('active')
                $scoring.find('.points[data-player-id="' + playerId + '"]').addClass('active')
            }
        )
    })
    $(document).on('mousedown', '.last-games .game', function() {
        $('.scoring-plays').slideToggle()
    })
})
</script>
</body>
</html>