<?php
include_once '../path.php';
include_once '../includes/functions.php';
$playerID = $_POST['player'];

$ApiUrl = 'https://api-web.nhle.com/v1/player/'. $playerID .'/landing';
$curl = curlInit($ApiUrl);
$playerResult = json_decode($curl);

$ApiUrl = 'https://api-web.nhle.com/v1/player/'. $playerID .'/game-log/'. $season .'/2';
$curl = curlInit($ApiUrl);
$playerGameLog = json_decode($curl);

$player = $playerResult;
$stat = $player->featuredStats->regularSeason->subSeason;
$statTotals = $player->featuredStats->regularSeason->career;

if ($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D') {

    // https://api.nhle.com/stats/rest/en/skater/summary?isAggregate=false&isGame=false&sort=%5B%7B%22property%22:%22points%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22goals%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22assists%22,%22direction%22:%22DESC%22%7D,%7B%22property%22:%22playerId%22,%22direction%22:%22ASC%22%7D%5D&start=0&limit=50&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20playerId=8480800%20and%20seasonId%3C=20232024%20and%20seasonId%3E=20232024
    $ApiUrl = 'https://api.nhle.com/stats/rest/en/skater/puckPossessions?limit=50&cayenneExp=gameTypeId=2%20and%20playerId='. $playerID .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
    $curl = curlInit($ApiUrl);
    $playerMoreStats = json_decode($curl);

    $ApiUrl = 'https://api.nhle.com/stats/rest/en/skater/goalsForAgainst?limit=50&cayenneExp=gameTypeId=2%20and%20playerId='. $playerID .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
    $curl = curlInit($ApiUrl);
    $playerGF = json_decode($curl);

    $originalSAT = $playerMoreStats->data[0]->satPct;
    $formattedSAT = $originalSAT * 100;
    $formattedSAT = number_format($formattedSAT, 1);
    $originalUSAT = $playerMoreStats->data[0]->usatPct;
    $formattedUSAT = $originalUSAT * 100;
    $formattedUSAT = number_format($formattedUSAT, 1);
}

$dob = $player->birthDate;
$playerAge = (date('Y') - date('Y',strtotime($dob)));


?>
<div id="close"><i class="bi bi-x-circle"></i></div>
<div class="player-header">
    <div class="left">
        <div class="headshot">
            <svg class="headshot_wrap" width="128" height="128">
                <mask id="circleMask:r2:">
                    <svg>
                        <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                    </svg>
                </mask>
                <image mask="url(#circleMask:r2:)" fill="#000000" id="canTop" height="128" href="<?= $player->headshot ?>"></image>
            </svg>
            <img class="team-img" src="<?= $player->teamLogo ?>" />
            <svg class="team-fill" width="128" height="128">
                <circle cx="64" cy="72" r="56" fill="<?= teamToColor($player->currentTeamId) ?>"></circle>
                <defs>
                    <linearGradient id="gradient:r2:" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                        <stop offset="65%" stop-opacity=".25" stop-color="#000000"></stop>
                    </linearGradient>
                </defs>
                <circle cx="64" cy="72" r="56" fill="url(#gradient:r2:)"></circle>
            </svg>
        </div><!-- END .headshot -->
    </div>
    <div class="right">
        <?php if (!empty($player->draftDetails->overallPick)) {
            echo '<div class="drafted">Drafted #' . $player->draftDetails->overallPick . ' overall by ' . $player->draftDetails->teamAbbrev . ', ' . $player->draftDetails->year . '</div>';
        } ?>
        <div class="name"><h2><?= $player->firstName->default ?> <?= $player->lastName->default ?></h2></div>
        <div class="player-header-info">
            <div class="info"><div class="label">Position</div><p><?= positionCodeToName($player->position) ?></p></div>
            <div class="info"><div class="label">Nationality</div><img class="flag" src="<?= BASE_URL ?>/assets/img/flags/<?= convertCountryAlphas3To2($player->birthCountry) ?>.svg" height="78" width="102" /></div>
            <div class="info"><div class="label">Age</div><p><?= $playerAge ?></p></div>
            <div class="info"><div class="label">Number</div><p>#<?= $player->sweaterNumber ?></p></div>
        </div>
    </div>
</div>
<div class="comp-cont">
    <div class="title stats">
        <h3 class="header-text">Current Season</h3>
    </div>
    <div class="stats-player">
        <?php if ($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D') { ?>
            <div class="stat">

                <div class="value"><?= $stat->gamesPlayed ?></div>          
            </div>
            <div class="stat">

                <div class="value"><?= $stat->goals ?></div>   
            </div>
            <div class="stat">
                
                <div class="value"><?= $stat->assists ?></div>         
            </div>
            <div class="stat">
                
                <div class="value"><?= $stat->points ?></div>        
            </div>
            <div class="stat">
                
                <div class="value"><?= number_format((float)$stat->points / $stat->gamesPlayed, 2, '.', '') ?></div>        
            </div>
            <div class="stat">
                
                <div class="value"><?= $stat->plusMinus ?></div>     
            </div>
            <div class="stat">
                
                <div class="value"><?= $stat->pim ?></div>
            </div>
            <div class="stat">
                
                <div class="value"><?= $stat->shots ?></div>      
            </div>
            <div class="stat">
                
                <div class="value"><?= number_format((float)$stat->shootingPctg * 100, 0, '.', '') ?></div>
            </div>
            <div class="stat">
                
                <div class="value"><?= $formattedSAT ?></div>
            </div>
            <div class="stat">
                
                <div class="value"><?= $formattedUSAT ?></div>
            </div>
            <div class="stat">
                
                <div class="value"><?= $playerGF->data[0]->evenStrengthGoalDifference ?></div>
            </div>      
        <?php } else { ?>
            <!-- Goalie Stats -->
                <td>Games</td>
                <td>GAA</td>
                <td>Save %</td>
                <td>Wins</td>
                <td>Shutouts</td>

                    <td class="value"><?= $stat->gamesPlayed ?></td>
                    <td class="value"><?= number_format((float)$stat->goalsAgainstAvg, 2, '.', '') ?></td>
                    <td class="value"><?= number_format((float)$stat->savePctg, 3, '.', '') ?></td>
                    <td class="value"><?= $stat->wins ?></td>
                    <td class="value"><?= $stat->shutouts ?></td>

        <?php } ?>
    </div>
</div>

<style>
#compare-players .suggestion-input {
    display: flex;
    gap: 2rem;
    align-items: center;
    margin-bottom: 2rem;
}

#compare-players .suggestion-input>input {
    width: 100%;
}

#compare-players .suggestion-input .suggestion-box {
    width: 49%;
    padding: 10px;
}

.compare-search-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 7.5rem;
}

.compare-container {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
}

.player-compare-container {
    border: 2px dashed var(--content-box-bg);
    border-radius: 10px;
    overflow: hidden;
    min-height: 600px;
    position: relative;
    overflow: hidden;
    width: 100%;
}

.compare-divider {
    max-width: 120px;
    width: 100%;
    text-align: center;
    min-height: 600px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 13.1rem 0 1.8rem;
}

.compare-divider .label {
    padding: 0.6rem 0;
    color: var(--medium-contrast-color);
    opacity: 0;
}

.compare-divider.active .label {
    animation-name: elementIn;
    animation-duration: 0.65s;
    animation-fill-mode: forwards;
    animation-iteration-count: 1;
}

.player-compare-container:has(.player-header) {
    background-color: var(--content-box-bg);
    border: none;
}

.player-compare-container .comp-cont {
    padding: 0 2rem 2rem;
}

#player-compare-1 .comp-cont {
    text-align: right;
}

.player-compare-container .comp-cont .header-text {
    font-size: 1.4rem;
}

.player-compare-container #close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--main-link-color);
}

.player-compare-container .player-header {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 1rem 0;
    max-height: 170px;
}

.player-compare-container .player-header .right {
    transform: translateX(-3rem);
    width: 100%;
}

.player-compare-container .player-header .right .drafted {
    font-size: 0.9rem;
    color: var(--medium-contrast-color);
    font-weight: bold;
}

.player-compare-container .player-header .right .name h2 {
    font-size: 1.8rem;
}

.player-compare-container .player-header .headshot .team-img {
    transform: translateX(-36px) translateY(5px);
    position: absolute;
    z-index: 1;
    clip-path: circle(32.333% at center);
}

.player-compare-container .player-header .player-header-info {
    display: flex;
    margin-top: 0.3rem;
    gap: 1.2rem;
}

.player-compare-container .player-header .player-header-info .info .flag {
    width: 30px;
    height: auto;
    border-radius: 3px;
    box-shadow: var(--shadow-button);
}

.player-compare-container .player-header .player-header-info .label {
    font-weight: bold;
    margin-bottom: 0.3em;
    color: var(--medium-contrast-color);
}

.player-compare-container .stats-player {
    gap: 1rem;
    display: grid;
    margin-top: 0.5rem;
}

.player-compare-container .stats-player .stat .value {
    padding: 0.3rem 0.6rem;
    background-color: var(--main-darker-bg);
    border-radius: 4px;
}

.player-compare-container .stats-player .stat .value.higher {
    background-color: #8cc6a8;
    color: #0b3414;
}

.player-compare-container .stats-player .stat .value.lower {
    background-color: #edc1d2;
    color: #44090f;
}

#activity-player-compare {
    width: 100px;
    height: 100px;
    left: 50%;
    top: 50%;
    margin-top: -50px;
    margin-left: -50px;
    position: absolute;
    display: none;
}

#activity-player-compare .loader {
    width: 100%;
    height: 100%;
    border: 8px solid var(--low-contrast-color);
    border-bottom-color: var(--secondary-link-color);
    border-radius: 50%;
    display: inline-block;
    box-sizing: border-box;
    animation: loader 1s linear infinite;
}
</style>