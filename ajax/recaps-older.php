<?php
include_once '../path.php';
include_once '../includes/functions.php';
?>
<div class="alert">No older games found</div>
<?php 
$now = date("Y-m-d", strtotime("-3 DAY"));
$then = date("Y-m-d", strtotime("-2 DAY"));
$ApiUrl = 'https://statsapi.web.nhl.com/api/v1/schedule?startDate='. $now .'&endDate='. $then;
$curl = curlInit($ApiUrl);
$schedules = json_decode($curl);

if(isset($schedules->dates)) {
foreach ($schedules->dates as $gameDates) {
krsort($gameDates->games);
foreach ($gameDates->games as $result) {
if ($result->status->abstractGameState == 'Final') {
    
$utcTimezone = new DateTimeZone('UTC');
$time = new DateTime( $result->gameDate, $utcTimezone );
$awayID = $result->teams->away->team->id;
$homeID = $result->teams->home->team->id;
$awayName = $result->teams->away->team->name;
$homeName = $result->teams->home->team->name;
$gameState = $result->status->abstractGameState;
$gameID = $result->gamePk;

// Second API
$ApiUrl = 'https://statsapi.web.nhl.com/api/v1/game/'. $gameID .'/content';
$curl = curlInit($ApiUrl);
$gameContent = json_decode($curl);
if(isset($gameContent->media->epg[3]->items[0]->playbacks[3]->url)) { 
$recapVideo = $gameContent->media->epg[3]->items[0]->playbacks[3]->url;
?>
<div class="game recap">
    <div class="watch-recap">
        <a href="<?= $recapVideo ?>" target="_blank"><i class="bi bi-tv"></i> Watch Recap</a>
    </div>
    <div class="teams">
        <div id="team-linko" href="#" data-link="<?= $awayID ?>">
            <img src="<?= BASE_URL ?>/assets/img/teams/<?= $awayID ?>.svg" alt="<?= $awayName ?>" />
        </div>
        <p>
            <span class="default">VS</span>
        </p>
        <div id="team-linko" href="#" data-link="<?= $homeID ?>">
            <img src="<?= BASE_URL ?>/assets/img/teams/<?= $homeID ?>.svg" alt="<?= $homeName ?>" />
        </div>
    </div>
    <div class="time">Game Ended: <?= $time->format( 'Y-m-d' ) ?></div>
</div>
<?php }}}}} else { echo 'No games :('; } ?>
</div>