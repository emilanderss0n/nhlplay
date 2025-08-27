<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/game.php';

if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { 
    $gameId = $_POST['gameId']; 
} else { 
    if (!defined('IN_PAGE')) include_once '../header.php';
    $gameId = $_GET['gameId']; 
}

// Prepare data for the pre-game view and extract returned variables
$preData = game_prepare_pre($gameId ?? ($GLOBALS['gameId_temp'] ?? null));
if (is_array($preData)) extract($preData);
?>
<main>
    <div class="wrap">
        <div class="pre-game-cont pre">
            <div class="pre-game-box">
                <div class="pre-game-header">
                    <div class="container" style="
                        background-image: linear-gradient(120deg, 
                        <?= teamToColor($awayTeamId) ?> -50%,
                        transparent 40%,
                        transparent 60%,
                        <?= teamToColor($homeTeamId) ?> 150%);">
                        <div class="away-team">
                            <picture>
                                <source srcset="<?= $awayTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                <img src="<?= $awayTeam->logo ?>" alt="<?= $awayTeam->commonName->default ?>" />
                            </picture>
                        </div>
                        <div class="score">
                            <span>VS</span>
                            <div class="game-date"><?php echo $gameTime->format( 'Y / m / d - H:i' ) .' <span>UTC</span>'; ?></div>
                            <div id="countdown" class="tag" data-game-time="<?= $gameTime->format('Y-m-d\TH:i:s\Z') ?>"></div>
                        </div>
                        <div class="home-team">
                            <picture>
                                <source srcset="<?= $homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                <img src="<?= $homeTeam->logo ?>" alt="<?= $homeTeam->commonName->default ?>" />
                            </picture>
                        </div>
                    </div>
                </div>

                <div class="pre-game-side">

                <?php
                $advantagePoints = preGameAdvantage(
                    $homeTeamId,
                    $awayTeamId,
                    $seasonStats->homeTeam,
                    $seasonStats->awayTeam,
                    $game->homeTeam->record,
                    $game->awayTeam->record,
                    isset($railGame->last10Record->homeTeam->record) ? $railGame->last10Record->homeTeam->record : null,
                    isset($railGame->last10Record->awayTeam->record) ? $railGame->last10Record->awayTeam->record : null,
                    $seasonSeries,
                    $season
                );

                $awayPercentage = round($advantagePoints[0]);
                $homePercentage = round($advantagePoints[1]);

                $awayColor = teamToColor($awayTeamId);
                $homeColor = teamToColor($homeTeamId);

                $awayHSL = hexToHSL($awayColor);
                $homeHSL = hexToHSL($homeColor);
                ?>
                <div class="advantage-bar-container" data-tooltip="Team Advantage Bar">
                    <div class="advantage-labels">
                        <span class="away-advantage"><?= $awayPercentage ?>% Advantage</span>
                        <span class="home-advantage"><?= $homePercentage ?>% Advantage</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                            style="width: <?= $awayPercentage ?>%; background-color: <?= $awayColor ?>; 
                            --team-color: <?= $awayColor ?>; 
                            --team-hue: <?= $awayHSL[0] ?>; 
                            --team-sat: <?= $awayHSL[1] ?>%; 
                            --team-light: <?= $awayHSL[2] ?>%;" 
                            aria-valuenow="<?= $awayPercentage ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                        <div class="progress-bar" role="progressbar" 
                            style="width: <?= $homePercentage ?>%; background-color: <?= $homeColor ?>; 
                            --team-color: <?= $homeColor ?>; 
                            --team-hue: <?= $homeHSL[0] ?>; 
                            --team-sat: <?= $homeHSL[1] ?>%; 
                            --team-light: <?= $homeHSL[2] ?>%;" 
                            aria-valuenow="<?= $homePercentage ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <div class="pre-game-series schedule grid grid-300 grid-gap grid-gap-row" grid-max-col-count="4">
                <?php renderSeasonSeries($seasonSeries); ?>
                </div>
                
                <div class="pre-game-stats">
                    <div class="head-to-head">
                        <div class="away stats">
                            <div class="stat"><?= $game->awayTeam->record ?></div>
                            <div class="stat"><?= isset($railGame->last10Record->awayTeam->record) ? $railGame->last10Record->awayTeam->record : '-' ?></div>
                            <div class="stat"><?= number_format((float)$seasonStats->awayTeam->ppPctg * 100, 0, '.', '') ?>%</div>
                            <div class="stat"><?= number_format((float)$seasonStats->awayTeam->pkPctg * 100, 0, '.', '') ?>%</div>
                            <div class="stat"><?= number_format((float)$seasonStats->awayTeam->faceoffWinningPctg * 100, 0, '.', '') ?>%</div>
                            <div class="stat"><?= number_format((float)$seasonStats->awayTeam->goalsForPerGamePlayed, 2, '.', '') ?></div>
                            <div class="stat"><?= number_format((float)$seasonStats->awayTeam->goalsAgainstPerGamePlayed, 2, '.', '') ?></div>
                            <div class="stat"><?= $seasonStats->awayTeam->goalsForPerGamePlayedRank ?></div>
                            <div class="stat"><?= $seasonStats->awayTeam->goalsAgainstPerGamePlayedRank ?></div>
                        </div>
                        <div class="stats stat-desc">
                            <div class="stat desc"><p>Record</p><span>REC</span></div>
                            <div class="stat desc"><p>Last 10</p><span>L10</span></div>
                            <div class="stat desc"><p>Powerplay %</p><span>PP%</span></div>
                            <div class="stat desc"><p>Penalty Kill %</p><span>PK%</span></div>
                            <div class="stat desc"><p>Face-off %</p><span>FO%</span></div>
                            <div class="stat desc"><p>Goals For /GP</p><span>GF/P/GP</span></div>
                            <div class="stat desc"><p>Goals Against /GP</p><span>GA/P/GP</span></div>
                            <div class="stat desc"><p>Goals For /GP Rank</p><span>GF/P/GP/R</span></div>
                            <div class="stat desc"><p>Goals Against /GP Rank</p><span>GA/P/GP/R</span></div>
                        </div>
                        <div class="home stats">
                            <div class="stat"><?= $game->homeTeam->record ?></div>
                            <div class="stat"><?= isset($railGame->last10Record->homeTeam->record) ? $railGame->last10Record->homeTeam->record : '-' ?></div>
                            <div class="stat"><?= number_format((float)$seasonStats->homeTeam->ppPctg * 100, 0, '.', '') ?>%</div>
                            <div class="stat"><?= number_format((float)$seasonStats->homeTeam->pkPctg * 100, 0, '.', '') ?>%</div>
                            <div class="stat"><?= number_format((float)$seasonStats->homeTeam->faceoffWinningPctg * 100, 0, '.', '') ?>%</div>
                            <div class="stat"><?= number_format((float)$seasonStats->homeTeam->goalsForPerGamePlayed, 2, '.', '') ?></div>
                            <div class="stat"><?= number_format((float)$seasonStats->homeTeam->goalsAgainstPerGamePlayed, 2, '.', '') ?></div>
                            <div class="stat"><?= $seasonStats->homeTeam->goalsForPerGamePlayedRank ?></div>
                            <div class="stat"><?= $seasonStats->homeTeam->goalsAgainstPerGamePlayedRank ?></div>
                        </div>
                    </div>
                </div><!-- end .pre-game-stats -->
                </div><!-- end .pre-game-side -->
            </div>
            <div class="watch-streams grid grid-300 grid-gap-lg grid-gap-row-lg" grid-max-col-count="4">
                <div class="stream">
                    <a href="https://1stream.vip/nhl-streams/" target="_blank">1Stream <i class="bi right bi-arrow-right-short"></i></a>
                </div>
                <div class="stream">
                    <a href="https://nhlbite.com/" target="_blank">NHL Bite <i class="bi right bi-arrow-right-short"></i></a>
                </div>
                <div class="stream">
                    <a href="https://nhlstreamlinks.live/" target="_blank">NHL Stream Links <i class="bi right bi-arrow-right-short"></i></a>
                </div>
            </div>
            <h3 class="players-to-watch-title" >Players to Watch</h3>
            <div class="players-to-watch grid grid-500 grid-gap-lg grid-gap-row-lg" grid-max-col-count="2">
                <?php if (isset($game->matchup->skaterComparison->leaders) && is_array($game->matchup->skaterComparison->leaders)) {
                    foreach ($game->matchup->skaterComparison->leaders as $categoryData) {
                        if ($categoryData->category == 'assists') {
                            continue;
                        }
                ?>
                    <div class="<?= $categoryData->category ?> row">
                        <a href="#" id="player-link" data-link="<?= $categoryData->awayLeader->playerId ?>" class="player">
                            <div class="headshot head-1">
                                <img class="head" width="180" height="180" src="<?= $categoryData->awayLeader->headshot ?>"></img>
                                <picture>
                                    <source srcset="<?= $awayTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                    <img class="team-img" src="<?= $awayTeam->logo ?>" />
                                </picture>
                                <div class="team-color" style="background: linear-gradient(142deg, <?= teamToColor($awayTeamId) ?> 0%, rgba(255,255,255,0) 58%); left:0;"></div>
                            </div><!-- END .headshot -->
                            <div class="player-desc">
                                <div class="name"><?= $categoryData->awayLeader->name->default ?></div>
                                <div class="role">#<?= $categoryData->awayLeader->sweaterNumber ?> - <?= positionCodeToName($categoryData->awayLeader->positionCode) ?></div>
                            </div>
                            <div class="stat-m">
                                <?= $categoryData->awayLeader->value ?>
                                <?php 
                                    if ($categoryData->category == 'points') { echo '<span> P</span>'; }
                                    elseif ($categoryData->category == 'goals') { echo '<span> G</span>'; }
                                ?>
                            </div>
                        </a>
                        <div class="stat"><?= $categoryData->awayLeader->value ?></div>
                        <div class="info">
                            <?php 
                                if ($categoryData->category == 'points') { echo '<span>P</span>'; }
                                elseif ($categoryData->category == 'goals') { echo '<span>G</span>'; }
                            ?>
                        </div>
                        <div class="stat"><?= $categoryData->homeLeader->value ?></div>
                        <a href="#" id="player-link" data-link="<?= $categoryData->homeLeader->playerId ?>" class="player">
                            <div class="headshot head-2">
                                <img class="head" width="180" height="180" src="<?= $categoryData->homeLeader->headshot ?>"></img>
                                <picture>
                                    <source srcset="<?= $homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                    <img class="team-img" src="<?= $homeTeam->logo ?>" />
                                </picture>
                                <div class="team-color" style="background: linear-gradient(-142deg, <?= teamToColor($homeTeamId) ?> 0%, rgba(255,255,255,0) 58%); right: 0;"></div>
                            </div><!-- END .headshot -->
                            <div class="player-desc">
                                <div class="name"><?= $categoryData->homeLeader->name->default ?></div>
                                <div class="role">#<?= $categoryData->homeLeader->sweaterNumber ?> - <?= positionCodeToName($categoryData->homeLeader->positionCode) ?></div>
                            </div>
                            <div class="stat-m">
                                <?= $categoryData->homeLeader->value ?>
                                <?php 
                                    if ($categoryData->category == 'points') { echo '<span> P</span>'; }
                                    elseif ($categoryData->category == 'goals') { echo '<span> G</span>'; }
                                ?>
                            </div>
                        </a>
                    </div>
                <?php }} else { echo '<div class="alert info">Not enough games played in current season or preseason is active</div>'; } ?>
            </div>
            
            <h3 class="players-to-watch-title">Goalies to Watch</h3>
            <div class="players-to-watch grid grid-500 grid-gap-lg grid-gap-row-lg" grid-max-col-count="2">
                <?php if (isset($game->matchup->goalieComparison) && 
                          isset($game->matchup->goalieComparison->awayTeam->leaders) && 
                          isset($game->matchup->goalieComparison->homeTeam->leaders) &&
                          !empty($game->matchup->goalieComparison->awayTeam->leaders) &&
                          !empty($game->matchup->goalieComparison->homeTeam->leaders)) {
                    
                    // Find the best goalie from each team based on games played and save percentage
                    $awayGoalie = null;
                    $awayMinGames = 3; // Minimum games threshold
                    $awayBestSavePct = 0;
                    
                    foreach ($game->matchup->goalieComparison->awayTeam->leaders as $goalie) {
                        if (isset($goalie->gamesPlayed) && isset($goalie->savePctg)) {
                            // Prioritize goalies with at least minimum games
                            if ($goalie->gamesPlayed >= $awayMinGames && $goalie->savePctg > $awayBestSavePct) {
                                $awayGoalie = $goalie;
                                $awayBestSavePct = $goalie->savePctg;
                            }
                        }
                    }
                    
                    // If no goalie meets the criteria, take the one with most games
                    if ($awayGoalie === null) {
                        $mostGames = 0;
                        foreach ($game->matchup->goalieComparison->awayTeam->leaders as $goalie) {
                            if (isset($goalie->gamesPlayed) && $goalie->gamesPlayed > $mostGames) {
                                $awayGoalie = $goalie;
                                $mostGames = $goalie->gamesPlayed;
                            }
                        }
                        // If still null, just take the first one
                        if ($awayGoalie === null && !empty($game->matchup->goalieComparison->awayTeam->leaders)) {
                            $awayGoalie = $game->matchup->goalieComparison->awayTeam->leaders[0];
                        }
                    }
                    
                    // Do the same for home team
                    $homeGoalie = null;
                    $homeMinGames = 3;
                    $homeBestSavePct = 0;
                    
                    foreach ($game->matchup->goalieComparison->homeTeam->leaders as $goalie) {
                        if (isset($goalie->gamesPlayed) && isset($goalie->savePctg)) {
                            if ($goalie->gamesPlayed >= $homeMinGames && $goalie->savePctg > $homeBestSavePct) {
                                $homeGoalie = $goalie;
                                $homeBestSavePct = $goalie->savePctg;
                            }
                        }
                    }
                    
                    if ($homeGoalie === null) {
                        $mostGames = 0;
                        foreach ($game->matchup->goalieComparison->homeTeam->leaders as $goalie) {
                            if (isset($goalie->gamesPlayed) && $goalie->gamesPlayed > $mostGames) {
                                $homeGoalie = $goalie;
                                $mostGames = $goalie->gamesPlayed;
                            }
                        }
                        if ($homeGoalie === null && !empty($game->matchup->goalieComparison->homeTeam->leaders)) {
                            $homeGoalie = $game->matchup->goalieComparison->homeTeam->leaders[0];
                        }
                    }
                    
                    // Only proceed if we have both goalies
                    if ($awayGoalie && $homeGoalie) {
                ?>
                <div class="goalie row">
                    <a href="#" id="player-link" data-link="<?= $awayGoalie->playerId ?>" class="player">
                        <div class="headshot head-1">
                            <img class="head" width="180" height="180" src="<?= $awayGoalie->headshot ?>" onerror="imageError(this)"></img>
                            <picture>
                                <source srcset="<?= $awayTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                <img class="team-img" src="<?= $awayTeam->logo ?>" />
                            </picture>
                            <div class="team-color" style="background: linear-gradient(142deg, <?= teamToColor($awayTeamId) ?> 0%, rgba(255,255,255,0) 58%); left:0;"></div>
                        </div><!-- END .headshot -->
                        <div class="player-desc">
                            <div class="name"><?= $awayGoalie->name->default ?></div>
                            <div class="role">#<?= $awayGoalie->sweaterNumber ?> - <?= positionCodeToName($awayGoalie->positionCode) ?></div>
                        </div>
                        <div class="stat-m">
                            <?= isset($awayGoalie->savePctg) ? number_format((float)$awayGoalie->savePctg, 3, '.', '') : '-' ?>
                            <span> SV%</span>
                        </div>
                    </a>
                    <div class="stat"><?= isset($awayGoalie->savePctg) ? number_format((float)$awayGoalie->savePctg, 3, '.', '') : '-' ?></div>
                    <div class="info"><span>SV%</span></div>
                    <div class="stat"><?= isset($homeGoalie->savePctg) ? number_format((float)$homeGoalie->savePctg, 3, '.', '') : '-' ?></div>
                    <a href="#" id="player-link" data-link="<?= $homeGoalie->playerId ?>" class="player">
                        <div class="headshot head-2">
                            <img class="head" width="180" height="180" src="<?= $homeGoalie->headshot ?>" onerror="imageError(this)"></img>
                            <picture>
                                <source srcset="<?= $homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                <img class="team-img" src="<?= $homeTeam->logo ?>" />
                            </picture>
                            <div class="team-color" style="background: linear-gradient(-142deg, <?= teamToColor($homeTeamId) ?> 0%, rgba(255,255,255,0) 58%); right: 0;"></div>
                        </div><!-- END .headshot -->
                        <div class="player-desc">
                            <div class="name"><?= $homeGoalie->name->default ?></div>
                            <div class="role">#<?= $homeGoalie->sweaterNumber ?> - <?= positionCodeToName($homeGoalie->positionCode) ?></div>
                        </div>
                        <div class="stat-m">
                            <?= isset($homeGoalie->savePctg) ? number_format((float)$homeGoalie->savePctg, 3, '.', '') : '-' ?>
                            <span> SV%</span>
                        </div>
                    </a>
                </div>
                <?php } } else { echo '<div class="alert info">Goalie data not available</div>'; } ?>
            </div>
            
            <div class="leaders-wrap">
                <h3 class="players-to-watch-title" >Team Point Leaders</h3>
                <div class="home-leaders pre-game grid grid-300 grid-gap-lg grid-gap-row-lg" max-col-count="2">
                <?php
                if ($playoffs) {
                    $type = 3;
                } else {
                    $type = 2;
                }
                // Use the new NHL API utility
                $ApiUrl = NHLApi::teamStats($awayTeamAbbrev, $season, $type);
                $curl = curlInit($ApiUrl);
                $players = json_decode($curl)->skaters;

                usort($players, function ($a, $b) {
                    return $b->points - $a->points;
                });

                $topPlayers = array_slice($players, 0, 5);
                ?>
                <div class="leaders-box">
                    <div class="player-cont">
                        <?php foreach ($topPlayers as $leader) { ?>
                            <div class="player" data-player-cont="<?= $leader->playerId ?>">
                                <a id="player-link" data-link="<?= $leader->playerId ?>" href="#">
                                    <div class="info">
                                        <div class="headshot">
                                            <span class="image" style="background-image: url(<?= $leader->headshot ?>);"></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="player-text" data-player-text="<?= $leader->playerId ?>">
                                <div class="category">Points</div>
                                <div class="name adv-title"><?= $leader->firstName->default ?> <?= $leader->lastName->default ?></div>
                                <div class="more"><div class="tag"><?= $awayTeamAbbrev ?></div> - <?= positionCodeToName($leader->positionCode) ?></div>
                            </div>
                            <div class="value-top" data-player-text="<?= $leader->playerId ?>"><?= $leader->points ?><div>POINTS</div></div>
                        <?php } ?>
                    </div>
                    <div class="points-cont">
                        <?php foreach ($topPlayers as $leader) { ?>
                            <div class="points" data-player-id="<?= $leader->playerId ?>">
                                <div class="points-line" data-value="<?= $leader->points ?>"></div>
                                <div class="points-name"><?= $leader->firstName->default ?> <?= $leader->lastName->default ?></div>
                                <div class="points-value"><?= $leader->points ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div><!-- end leaders-box -->
                <?php
                if ($playoffs) {
                    $type = 3;
                } else {
                    $type = 2;
                }
                // Use the new NHL API utility
                $ApiUrl = NHLApi::teamStats($homeTeamAbbrev, $season, $type);
                $curl = curlInit($ApiUrl);
                $players = json_decode($curl)->skaters;

                usort($players, function ($a, $b) {
                    return $b->points - $a->points;
                });

                $topPlayers = array_slice($players, 0, 5);
                ?>
                <div class="leaders-box">
                    <div class="player-cont">
                        <?php foreach ($topPlayers as $leader) { ?>
                            <div class="player" data-player-cont="<?= $leader->playerId ?>">
                                <a id="player-link" data-link="<?= $leader->playerId ?>" href="#">
                                    <div class="info">
                                        <div class="headshot">
                                            <span class="image" style="background-image: url(<?= $leader->headshot ?>);"></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="player-text" data-player-text="<?= $leader->playerId ?>">
                                <div class="category">Points</div>
                                <div class="name adv-title"><?= $leader->firstName->default ?> <?= $leader->lastName->default ?></div>
                                <div class="more"><div class="tag"><?= $homeTeamAbbrev ?></div> - <?= positionCodeToName($leader->positionCode) ?></div>
                            </div>
                            <div class="value-top" data-player-text="<?= $leader->playerId ?>"><?= $leader->points ?><div>POINTS</div></div>
                        <?php } ?>
                    </div>
                    <div class="points-cont">
                        <?php foreach ($topPlayers as $leader) { ?>
                            <div class="points" data-player-id="<?= $leader->playerId ?>">
                                <div class="points-line" data-value="<?= $leader->points ?>"></div>
                                <div class="points-name"><?= $leader->firstName->default ?> <?= $leader->lastName->default ?></div>
                                <div class="points-value"><?= $leader->points ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div><!-- end .leaders-box -->
            </div><!-- end .home-leaders -->
        </div><!-- end .leaders-wrap -->
    </div><!-- end .pre-game-cont -->
</main>
<script>
    function imageError(e){
        e.setAttribute("src","./assets/img/no-image.png")
        e.removeAttribute("onError")
        e.removeAttribute("onclick")
    }
</script>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>