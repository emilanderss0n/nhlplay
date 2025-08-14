<?php
include_once '../includes/functions.php';
include_once '../path.php';

$active_team = $_POST['active_team'];
$teamAbbr = idToTeamAbbrev($active_team);

// Use the new NHL API utility instead of building URL manually
$ApiUrl = NHLApi::teamSchedule($teamAbbr, $season);
$curl = curlInit($ApiUrl);
$scores = json_decode($curl);
$utcTimezone = new DateTimeZone('UTC');
$currentMonth = '';

// Store and reverse games array
$gamesArray = array_filter($scores->games, function($game) {
    return $game->gameState === 'FINAL' || $game->gameState === 'OFF';
});
$gamesArray = array_reverse($gamesArray);
?>

<dialog id="gameLogModal">
    <div class="modal-header"><p>Post Game</p><a href="javascript:void(0);" id="closeGameLogModal"><i class="bi bi-x-lg"></i></a></div>
    <div class="content"></div>
</dialog>
<div id="gameLogOverlay"></div>

<div class="team-roster-header game-log">
    <div class="team-roster-header-cont">
        <div class="stats">
            <a href="javascript:void(0)" id="closeGameLog" class="btn sm"><i class="bi bi-arrow-left"></i> Team Roster</a>
        </div>
        <div class="btn-group filter-game-log right">
            <i class="bi bi-filter icon"></i>
            <a class="filter-btn btn sm active" data-type="all" href="javascript:void(0)">All</a>
            <a class="filter-btn btn sm" data-type="win" href="javascript:void(0)">Wins</a>
            <a class="filter-btn btn sm" data-type="loss" href="javascript:void(0)">Losses</a>
        </div>
    </div>
</div>

<div class="team-game-log grid grid-400 grid-gap-lg grid-gap-row-lg" grid-max-col-count="3">

<?php foreach($gamesArray as $result) { 
    $time = new DateTime($result->startTimeUTC, $utcTimezone);
    $gameDate = new DateTime($result->gameDate);
    $month = $gameDate->format('F');
    
    if ($month !== $currentMonth) {
        echo '<div class="break month">' . $month . '</div>';
        $currentMonth = $month;
    }
    
    $activeTeamWon = ($teamAbbr === $result->homeTeam->abbrev && $result->homeTeam->score > $result->awayTeam->score) || 
                     ($teamAbbr === $result->awayTeam->abbrev && $result->awayTeam->score > $result->homeTeam->score);
?>
    <div 
    data-post-link="<?= $result->id ?>" 
    class="item log-game <?php if($result->gameType === 1) { echo 'preseason'; } elseif ($result->gameType === 2) { echo 'regular'; } elseif ($result->gameType === 3) { echo 'playoff'; } if($activeTeamWon) { echo ' win'; } else { echo ' loss'; } ?>"
    style="background-image: linear-gradient(120deg, <?= teamToColor($result->awayTeam->id) ?> -50%, transparent 40%, transparent 60%, <?= teamToColor($result->homeTeam->id) ?> 150%);"
    >
        <div class="log-game-date"><strong><?= $result->gameDate ?></strong><span class="hide-mobile"> at <?= $result->venue->default ?></span></div>
        <div class="log-game-visual">
            <picture>
                <source srcset="<?= $result->awayTeam->darkLogo ?>" media="(prefers-color-scheme: dark)" />
                <img class="game-team-logo <?php if($result->awayTeam->score < $result->homeTeam->score) echo 'losing-team'; ?>" src="<?= $result->awayTeam->logo ?>" alt="" />
            </picture>
            <div class="log-game-score">
                <?= $result->awayTeam->score ?> <span>-</span> <?= $result->homeTeam->score ?>
            </div>
            <picture>
                <source srcset="<?= $result->homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)" />
                <img class="game-team-logo <?php if($result->homeTeam->score < $result->awayTeam->score) echo 'losing-team'; ?>" src="<?= $result->homeTeam->logo ?>" alt="" />
            </picture>
        </div>
    </div>
<?php } ?>

</div> <!-- team-game-log -->