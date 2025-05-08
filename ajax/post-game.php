<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { $gameId = $_POST['gameId']; } else { include_once '../header.php'; $gameId = $_GET['gameId']; }
require_once "../includes/MobileDetect.php";
$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

include_once '../includes/data/init-post-game.php';
?>
<style>
    .team-banner-<?= $winnerTeam ?>::before {
        background-image: linear-gradient(
    to bottom, transparent, var(--main-bg-color)
  ),url('assets/img/team-banners/min/<?= $winnerTeam ?>-min.webp');
    }
  .post-recap-vid .inner-bg {
        background-image: linear-gradient(
    to bottom, transparent, var(--main-bg-color)
  ),url('assets/img/team-banners/min<?= $winnerTeam ?>-min.webp');
    }
</style>
<main>
    <div class="wrap">
        <div class="post-game-cont team-banner-<?= $winnerTeam ?>">
            <div class="post-game-box">
                <div class="post-game-header">
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
                            <span><?= $awayTeam->score ?> - <?= $homeTeam->score ?></span>
                            <div class="game-date"><?= $gameTime->format( 'Y-m-d' ) .' <span>UTC</span>'; ?> <?= $endPeriod ?></div>
                            <?php
                            if(isset($gameVideo->condensedGame)) { 
                                echo '<a href="https://players.brightcove.net/6415718365001/EXtG1xJ7H_default/index.html?videoId='. $gameVideo->condensedGame .'" target="_blank" class="tag post-recap-vid-header"><i class="bi bi-camera-video"></i>
                                Recap
                                </a>
                                ';
                            } ?>
                        </div>
                        <div class="home-team">
                            <picture>
                                <source srcset="<?= $homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                                <img src="<?= $homeTeam->logo ?>" alt="<?= $homeTeam->commonName->default ?>" />
                            </picture>
                        </div>
                    </div>
                </div>
                <div class="post-game-stats">
                    <table class="border-only">
                        <thead>
                            <tr>
                                <th class="first-col"></th>
                                <th>SOG</th>
                                <th>PP</th>
                                <th>PIM</th>
                                <th>FO%</th>
                                <th>HITS</th>
                                <th>BLKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="away">
                                <td>
                                    <?= $awayTeam->commonName->default ?>
                                </td>
                                <td><?= $awayGameStats['sog'] ?></td>
                                <td>
                                    <?= $awayGameStats['powerPlay'] ?>
                                </td>
                                <td>
                                    <?= $awayGameStats['pim'] ?>
                                </td>
                                <td><?= round($awayGameStats['faceoffWinningPctg'] * 100) . '%' ?></td>
                                <td>
                                    <?= $awayGameStats['hits'] ?>
                                </td>
                                <td><?= $awayGameStats['blockedShots'] ?></td>
                            </tr>
                            <tr class="home">
                                <td>
                                    <?= $homeTeam->commonName->default ?>
                                </td>
                                <td><?= $homeGameStats['sog'] ?></td>
                                <td>
                                    <?= $homeGameStats['powerPlay'] ?>
                                </td>
                                <td>
                                    <?= $homeGameStats['pim'] ?>
                                </td>
                                <td><?= round($homeGameStats['faceoffWinningPctg'] * 100) . '%' ?></td>
                                <td>
                                    <?= $homeGameStats['hits'] ?>
                                </td>
                                <td><?= $homeGameStats['blockedShots'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="post-game-summary">
                <div class="post-game-stars grid grid-300 grid-gap grid-gap-row" grid-max-col-count="3">
                    <?php 
                    foreach ($gameContent->summary->threeStars as $starPlayer) {
                    ?>
                    <a class="player star-<?= $starPlayer->star ?>" href="#" id="player-link" data-link="<?= $starPlayer->playerId ?>">
                        <div class="player-wrap">
                            <img class="head" width="280" height="280" src="<?= $starPlayer->headshot ?>"></img>
                            <img class="team-img" src="assets/img/teams/<?= abbrevToTeamId($starPlayer->teamAbbrev) ?>.svg" width="200" height="200" />
                            <div class="team-color" style="background: linear-gradient(142deg, <?php $starTeam = abbrevToTeamId($starPlayer->teamAbbrev); echo teamToColor($starTeam) ?> 0%, rgba(255,255,255,0) 58%); right: 0;"></div>
                            <div class="player-desc">
                                <div class="name"><?= $starPlayer->name->default ?></div>
                                <div class="role">#<?= $starPlayer->sweaterNo ?> - <?= positionCodeToName2($starPlayer->position) ?></div>
                                <?php if($starPlayer->position == "G") { ?>
                                <div class="stats">
                                    <div class="stat">
                                        <span><?= $starPlayer->goalsAgainstAverage ?></span>
                                        <span>GAA</span>
                                    </div>
                                    <div class="stat">
                                        <span><?= number_format((float)$starPlayer->savePctg * 100) ?></span>
                                        <span>SV%</span>
                                    </div>
                                </div>
                                <?php } else { ?>
                                <div class="stats">
                                    <div class="stat">
                                        <span><?= $starPlayer->goals ?></span>
                                        <span>G</span>
                                    </div>
                                    <div class="stat">
                                        <span><?= $starPlayer->assists ?></span>
                                        <span>A</span>
                                    </div>
                                    <div class="stat">
                                        <span><?= $starPlayer->points ?></span>
                                        <span>P</span>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </a>
                    <?php } ?>
                </div><!-- END .post-game-stars -->
                <div class="post-game-boxscore grid grid-300 grid-gap-lg grid-gap-row-lg" grid-max-col-count="3">
                    <?= gameScoringPlays($gameContent) ?>
                </div>
            </div>
            <div class="boxscore-roster">
                <div class="away box">
                    <?= gameRosterStats($id = $awayTeamId, $name = $awayTeamName, $teamSide = 'awayTeam', $game) ?>
                </div><!-- END .away -->
                <div class="home box">
                    <?= gameRosterStats($id = $homeTeamId, $name = $homeTeamName, $teamSide = 'homeTeam', $game) ?>
                </div><!-- END .home -->
            </div><!-- END .boxscore-roster -->
            <h2 class="header-dashed">Penalties</h2>
            <div class="penalties team-boxes grid grid-300 grid-gap grid-gap-row" id="game-penalties" grid-max-col-count="3">
                <?= gamePenalties($gameContent) ?>
            </div>
            <div class="game-misc">
                <?php
                if(isset($gameVideo->condensedGame)) { 
                    echo '<div class="post-recap-vid item"><i class="bi bi-camera-video"></i>
                    <a href="https://players.brightcove.net/6415718365001/EXtG1xJ7H_default/index.html?videoId='. $gameVideo->condensedGame .'" target="_blank">
                    Condensed Game
                    </a>
                    </div>
                    ';
                } ?>
                <div class="coaches item">
                    <div class="away-coach coach">
                        <picture>
                            <source srcset="<?= $awayTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                            <img src="<?= $awayTeam->logo ?>" alt="<?= $awayTeam->commonName->default ?>" />
                        </picture>
                        <span data-tooltip="Coach"><?= $railContent->gameInfo->awayTeam->headCoach->default ?></span>
                    </div>
                </div>
                <div class="coaches item">
                    <div class="home-coach coach">
                        <picture>
                            <source srcset="<?= $homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                            <img src="<?= $homeTeam->logo ?>" alt="<?= $awayTeam->commonName->default ?>" />
                        </picture>
                        <span data-tooltip="Coach"><?= $railContent->gameInfo->homeTeam->headCoach->default ?></span>
                    </div>
                </div>
                <div class="arena item"><i class="bi bi-pin-map-fill"></i><span data-tooltip="Arena"><?= $game->venue->default ?>, <?= $game->venueLocation->default ?></span></div>
                <div class="referee item"><i class="bi bi-person-gear"></i>
                    <?php 
                    $refCount = count($railContent->gameInfo->referees);
                    foreach ($railContent->gameInfo->referees as $i => $referee) { 
                        echo '<span data-tooltip="Referee">' . $referee->default . '</span>';
                        if ($i < $refCount - 1) echo ', ';
                    }
                    ?>
                </div>
                <div class="linesman item"><i class="bi bi-person"></i>
                    <?php 
                    $lineCount = count($railContent->gameInfo->linesmen);
                    foreach ($railContent->gameInfo->linesmen as $i => $linesman) { 
                        echo '<span data-tooltip="Linesman">' . $linesman->default . '</span>';
                        if ($i < $lineCount - 1) echo ', ';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    function imageError(e){
        e.setAttribute("src","./assets/img/no-image.png");
        e.removeAttribute("onError");
        e.removeAttribute("onclick");
    }
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (!$detect->isMobile()) { ?>
            let dt = new jsdatatables.JSDataTable('.boxscore-table', {
            paging: false,
            searchable: true,
        });
        <?php } ?>
    });
</script>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>