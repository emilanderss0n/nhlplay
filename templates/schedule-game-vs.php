<?php
$gameState = $result->gameState;
$gameID = $result->id;
$awayID = $result->awayTeam->id;
$homeID = $result->homeTeam->id;
$awayTeamLogo = $result->awayTeam->darkLogo;
$homeTeamLogo = $result->homeTeam->darkLogo;
$awayName = $result->awayTeam->abbrev;
$homeName = $result->homeTeam->abbrev;
$gameType = $result->gameType;
$gameVideo = '';
if ($gameState == 'OFF' || $gameState == 'OVER' || $gameState == 'FINAL') { 
    if (!empty($result->threeMinRecap)) {
        $gameVideoSource = $result->threeMinRecap;
        $videoURLParts = explode("-", $gameVideoSource);
        $gameVideo = end($videoURLParts);
    }
}
$gameDate = date('Y-m-d', $gameDateG);
$time = $result->startTimeUTC;

?>
<div class="game <?php 
    switch ($gameState) {
        case 'LIVE':
        case 'CRIT':
            echo 'live';
            break;
        case 'FUT':
        case 'PRE':
            echo 'preview';
            break;
        case 'OFF':
        case 'OVER':
        case 'FINAL':
            echo 'final';
            break;
    }
    if (isset($result->specialEvent->parentId)) { echo ' disabled special-'.$result->specialEvent->parentId; }?>" id="game-<?= $gameID ?>" <?php 
    if ($gameState) { 
        echo 'data-post-link="'. $gameID .'"'; 
    } 
?>>
    <div class="teams" style="background-image: linear-gradient(120deg, 
            <?= teamToColor($awayID) ?> -50%,
            transparent 40%,
            transparent 60%,
            <?= teamToColor($homeID) ?> 150%);">
        <a id="team-linko" href="#">
            <img src="<?= $awayTeamLogo ?>" alt="<?= $awayName ?>" />
        </a>
        <p>
            <span class="scoring"><?php 
                if ($gameState == 'OFF' || $gameState == 'LIVE' || $gameState == 'CRIT' || $gameState == 'OVER' || $gameState == 'FINAL' ) { 
                    echo '<span class="away-score ajax-check" data-game-id="'. $gameID .'">'. $result->awayTeam->score .'</span> <span>-</span> <span class="home-score ajax-check" data-game-id="'. $gameID .'">'. $result->homeTeam->score.'</span>'; 
                } elseif ($gameState == 'FUT' || $gameState == 'PRE') { 
                    echo '<span class="inactive">VS</span>'; 
                } 
            ?></span>
            <span class="default">VS</span>
        </p>
        <a id="team-linko" href="#">
            <img src="<?= $homeTeamLogo ?>" alt="<?= $homeName ?>" />
        </a>
    </div>
    <div class="time ajax-check" data-game-id="<?= $gameID ?>">
    <?php 
    if ($gameState == 'OFF' || $gameState == 'OVER' || $gameState == 'FINAL') {
        echo '<i class="bi bi-check-circle"></i>Final Score <a class="recap" href="https://players.brightcove.net/6415718365001/EXtG1xJ7H_default/index.html?videoId='. $gameVideo .'" target="_blank"><i class="bi bi-camera-video"></i></a>'; 
    } elseif ($gameState == 'LIVE' || $gameState == 'CRIT') {
        echo '<div class="live-game-time-container">
                <div class="live-indicator"></div>
                <div class="live-data period"></div>
             </div>';
    } else {
        echo '<i class="bi bi-clock"></i> <div class="theTime">'. $time .'</div>'; 
    } 
    ?>
    </div>
</div>