<?php
include_once '../path.php';
include_once '../includes/functions.php';
$playerID = $_POST['player'];

$ApiUrl = 'https://api-web.nhle.com/v1/player/'. $playerID .'/landing';
$curl = curlInit($ApiUrl);
$playerResult = json_decode($curl);
$player = $playerResult;

$playerSeasonStats = $player->featuredStats->regularSeason->subSeason ?? null;
$playerPlayoffsStats = $player->featuredStats->playoffs->subSeason ?? null;
$statTotals = $player->featuredStats->regularSeason->career ?? null;

// Determine player type and initialize flags
$isSkater = ($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D');
$isForward = ($player->position == 'C' || $player->position == 'L' || $player->position == 'R');
$needsAdvancedStats = false;

// Get advanced stats for regular season and playoffs
if ($isSkater) {
    $regularSeasonAdvancedStats = getPlayerAdvancedStats($playerID, $season, 2);
    $playoffAdvancedStats = $playerPlayoffsStats ? getPlayerAdvancedStats($playerID, $season, 3) : null;
} else {
    $summary = $playerSeasonStats ?? null;
    
    if ($summary && isset($summary->savePct)) {
        $savePct = $summary->savePct;
        $evSavePct = $savePct;
        $ppSavePct = $savePct;
        $shSavePct = $savePct;
    } else {
        $savePct = $evSavePct = $ppSavePct = $shSavePct = 0;
    }
    
    $needsAdvancedStats = true;
}

$lastGames = $player->last5Games ?? [];

$dob = $player->birthDate ?? null;
$playerAge = $dob ? (date('Y') - date('Y',strtotime($dob))) : null;
$playerBirthplace = convertCountryAlphas3To2($player->birthCountry) ?? null;
$playerBirthplaceLong = \Locale::getDisplayRegion('-' . $playerBirthplace, 'en');
?>
<div class="wrapper <?php if (isset($player->draftDetails->year) && $player->draftDetails->year == date("Y")) { echo 'rookie'; } ?>">
    <div id="close"><i class="bi bi-x-lg"></i></div>
        <div class="player-header">
            <div class="left">
                <div class="headshot">
                    <svg class="headshot_wrap" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(2.009);">
                        <mask id="circleMask:r0:">
                            <svg>
                                <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                            </svg>
                        </mask>
                        <image mask="url(#circleMask:r0:)" fill="#000000" id="canTop" height="128" href="<?= $player->headshot ?>"></image>
                    </svg>
                    <img class="team-img" src="<?= $player->teamLogo ?>" />
                    <svg class="team-fill" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(2);">
                        <circle cx="64" cy="72" r="56" fill="<?= teamToColor($player->currentTeamId) ?>"></circle>
                        <defs>
                            <linearGradient id="gradient:r0:" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                                <stop offset="65%" stop-opacity=".25" stop-color="#000000"></stop>
                            </linearGradient>
                        </defs>
                        <circle cx="64" cy="72" r="56" fill="url(#gradient:r0:)"></circle>
                    </svg>
                </div><!-- END .headshot -->
            </div>
            <div class="right">
                <div class="name"><h2 class="player-name <?php if (($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D') && $playerSeasonStats && $playerSeasonStats->points / $playerSeasonStats->gamesPlayed > 1) { echo 'hot'; } ?>">
                    <?= $player->firstName->default ?> <?= $player->lastName->default ?>
                </h2></div>
                <?php if (!empty($player->draftDetails->overallPick)) {
                echo '<div class="drafted"><i class="bi bi-check2-circle"></i> Drafted #' . $player->draftDetails->overallPick . ' overall by ' . $player->draftDetails->teamAbbrev . ', ' . $player->draftDetails->year . '</div>';
                } ?>
                <div class="player-header-info">
                    <div class="info"><div class="label">Position</div><p><?= positionCodeToName($player->position) ?></p></div>
                    <div class="info"><div class="label">Nationality</div><img class="flag" title="<?= $playerBirthplaceLong ?>" src="<?= BASE_URL ?>/assets/img/flags/<?= $playerBirthplace ?>.svg" height="78" width="102" /></div>
                    <div class="info"><div class="label">Age</div><p><?= $playerAge ?></p></div>
                    <div class="info"><div class="label">Number</div><p>#<?= $player->sweaterNumber ?></p></div>
                </div>
            </div>
        </div>
        <?php if ($playerSeasonStats && $playerSeasonStats->gamesPlayed > 4) { ?>
            <div class="player-graph">
                <canvas id="playerStatsChart"></canvas>
            </div>
        <?php } ?>
        <div class="title stats">
            <h3 id="season-career" class="header-text">Season Stats</h3>
            <div class="btn-group player-filters">
                <i class="bi bi-filter icon"></i>
                <a href="#" class="btn sm" id="graph-toggle" data-player="<?= $playerID ?>" data-needs-stats="<?= $needsAdvancedStats ? 'true' : 'false' ?>" data-player-data='<?= htmlspecialchars(json_encode($player), ENT_QUOTES, 'UTF-8') ?>'>Radar</a>
                <a href="#" class="btn sm" id="career-link" data-link="<?= $playerID ?>">Career</a>
            </div>
        </div>
        <div class="stats-player">
            <div class="phone-show">
                <?php if ($isSkater) { ?>
                    <?= renderPhoneStatsDisplay($playerSeasonStats, $regularSeasonAdvancedStats['formattedSAT'], $regularSeasonAdvancedStats['formattedUSAT'], $regularSeasonAdvancedStats['evenStrengthGoalDiff'], true) ?>
                <?php } else { ?>
                    <?= renderPhoneStatsDisplay($playerSeasonStats, null, null, null, false) ?>
                <?php } ?>
            </div>
            <table class="phone-hide">
                <?php if ($isSkater) { ?>
                    <thead>
                        <tr>
                            <td>Games</td>
                            <td>Goals</td>
                            <td>Assists</td>
                            <td>Points</td>
                            <td>PPG</td>
                            <td>+/-</td>
                            <td>PIM</td>
                            <td>Shots</td>
                            <td>S%</td>
                            <td data-tooltip="Shot Attempts For Percentage">SAT%</td>
                            <td data-tooltip="Unblocked Shot Attempts For Percentage">USAT%</td>
                            <td data-tooltip="Even Strength Goal Differential">EV GD</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?= renderPlayerStatsRow($playerSeasonStats, $regularSeasonAdvancedStats['formattedSAT'], $regularSeasonAdvancedStats['formattedUSAT'], $regularSeasonAdvancedStats['evenStrengthGoalDiff']) ?>
                    </tbody>
                <?php } else { ?>
                    <thead>
                        <tr>
                            <td>GP</td>
                            <td>SV%</td>
                            <td>GAA</td>
                            <td>W</td>
                            <td>L (OT)</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?= renderGoalieStatsRow($playerSeasonStats) ?>
                    </tbody>
                <?php } ?>
            </table>

            <?php if ($playerPlayoffsStats) { ?>
                <div class="title stats">
                    <h3 class="header-text">Playoffs</h3>
                </div>
                <div class="phone-show">
                    <?php if ($isSkater) { ?>
                        <?= renderPhoneStatsDisplay($playerPlayoffsStats, $playoffAdvancedStats['formattedSAT'], $playoffAdvancedStats['formattedUSAT'], $playoffAdvancedStats['evenStrengthGoalDiff'], true) ?>
                    <?php } else { ?>
                        <?= renderPhoneStatsDisplay($playerPlayoffsStats, null, null, null, false) ?>
                    <?php } ?>
                </div>
                <table class="phone-hide">
                    <?php if ($isSkater) { ?>
                        <thead>
                            <tr>
                                <td>Games</td>
                                <td>Goals</td>
                                <td>Assists</td>
                                <td>Points</td>
                                <td>PPG</td>
                                <td>+/-</td>
                                <td>PIM</td>
                                <td>Shots</td>
                                <td>S%</td>
                                <td data-tooltip="Shot Attempts For Percentage - The percentage of shot attempts (on goal, missed, or blocked) taken by the player's team while they are on the ice">SAT%</td>
                                <td data-tooltip="Unblocked Shot Attempts For Percentage - The percentage of unblocked shot attempts (on goal or missed) taken by the player's team while they are on the ice">USAT%</td>
                                <td data-tooltip="Even Strength Goal Differential - The difference between goals for and against at even strength while the player is on the ice">EV GD</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderPlayerStatsRow($playerPlayoffsStats, $playoffAdvancedStats['formattedSAT'], $playoffAdvancedStats['formattedUSAT'], $playoffAdvancedStats['evenStrengthGoalDiff']) ?>
                        </tbody>
                    <?php } else { ?>
                        <thead>
                            <tr>
                                <td>GP</td>
                                <td>SV%</td>
                                <td>GAA</td>
                                <td>W</td>
                                <td>L (OT)</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderGoalieStatsRow($playerPlayoffsStats) ?>
                        </tbody>
                    <?php } ?>
                </table>
            <?php } ?>
            
            <?= renderLastGames($lastGames, $isSkater) ?>
        </div>
</div>