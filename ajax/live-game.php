<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { $gameId = $_POST['gameId']; } else { include_once '../header.php'; $gameId = $_GET['gameId']; }

require_once "../includes/MobileDetect.php";
$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

include_once '../includes/data/init-live-game.php';
?>
<main>
    <div class="wrap">
        <div class="post-game-cont live">
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
                            <span id="score"><?= $awayTeam->score ?> - <?= $homeTeam->score ?></span>
                            <div class="game-date" id="game-date">
                                <?php if($periodPaused) { ?>
                                    Period: <?= $periodNow ?><br>Intermission
                                <?php } else { ?>
                                    Period: <?= $periodNow ?><br><?= $periodRemaining ?> remaining
                                <?php } ?>
                            </div>
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
                                <td id="away-sog"><?= $awayGameStats['sog'] ?></td>
                                <td id="away-pp">
                                    <?= $awayGameStats['powerPlay'] ?>
                                </td>
                                <td id="away-pim">
                                    <?= $awayGameStats['pim'] ?>
                                </td>
                                <td id="away-fo"><?= round($awayGameStats['faceoffWinningPctg'] * 100) . '%' ?></td>
                                <td id="away-hits">
                                    <?= $awayGameStats['hits'] ?>
                                </td>
                                <td id="away-blks"><?= $awayGameStats['blockedShots'] ?></td>
                            </tr>
                            <tr class="home">
                                <td>
                                    <?= $homeTeam->commonName->default ?>
                                </td>
                                <td id="home-sog"><?= $homeGameStats['sog'] ?></td>
                                <td id="home-pp">
                                    <?= $homeGameStats['powerPlay'] ?>
                                </td>
                                <td id="home-pim">
                                    <?= $homeGameStats['pim'] ?>
                                </td>
                                <td id="home-fo"><?= round($homeGameStats['faceoffWinningPctg'] * 100) . '%' ?></td>
                                <td id="home-hits">
                                    <?= $homeGameStats['hits'] ?>
                                </td>
                                <td id="home-blks"><?= $homeGameStats['blockedShots'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <!--
                    <div class="watch-streams grid-template-4">
                        <div class="stream">
                            <a href="https://1stream.vip/nhl-streams/" target="_blank" class="btn subtle">1Stream <i class="bi right bi-arrow-right-short"></i></a>
                        </div>
                        <div class="stream">
                            <a href="https://back.methstreamer.com/nhl-live-streams" target="_blank" class="btn subtle">MethStreams <i class="bi right bi-arrow-right-short"></i></a>
                        </div>
                        <div class="stream">
                            <a href="https://reddit.nhlbite.com/" target="_blank" class="btn subtle">NHL Bite <i class="bi right bi-arrow-right-short"></i></a>
                        </div>
                        <div class="stream">
                            <a href="https://nhlstreamlinks.live/" target="_blank" class="btn subtle">NHL Stream Links <i class="bi right bi-arrow-right-short"></i></a>
                        </div>
                    </div>
                                -->
                </div>
            </div>
            <div class="post-game-boxscore grid grid-300 grid-gap-lg grid-gap-row-lg" id="game-scoring-plays" grid-max-col-count="3">
                <?= gameScoringPlays($gameContent) ?>
            </div>
            <div class="boxscore-roster">
                <div class="away box" id="away-roster-stats">
                    <?= gameRosterStats($id = $awayTeamId, $name = $awayTeamName, $teamSide = 'awayTeam', $game) ?>
                </div><!-- END .away -->
                <div class="home box" id="home-roster-stats">
                    <?= gameRosterStats($id = $homeTeamId, $name = $homeTeamName, $teamSide = 'homeTeam', $game) ?>
                </div><!-- END .home -->
            </div><!-- END .boxscore-roster -->
            <h2 class="header-dashed">Penalties</h2>
            <div class="penalties team-boxes grid grid-300 grid-gap grid-gap-row" id="game-penalties" grid-max-col-count="3">
                <?= gamePenalties($gameContent) ?>
            </div>
            <h2 class="header-dashed">Game Information</h2>
            <div class="game-misc">
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
                <div class="coaches item">
                    <div class="away-coach coach">
                        <picture>
                            <source srcset="<?= $awayTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                            <img src="<?= $awayTeam->logo ?>" alt="<?= $awayTeam->commonName->default ?>" />
                        </picture>
                        <span data-tooltip="Coach"><?= $railContent->gameInfo->awayTeam->headCoach->default ?></span>
                    </div>
                    <div class="home-coach coach">
                        <picture>
                            <source srcset="<?= $homeTeam->darkLogo ?>" media="(prefers-color-scheme: dark)">
                            <img src="<?= $homeTeam->logo ?>" alt="<?= $awayTeam->commonName->default ?>" />
                        </picture>
                        <span data-tooltip="Coach"><?= $railContent->gameInfo->homeTeam->headCoach->default ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    $('#activity').fadeOut('slow');
    function imageError(e){
        e.setAttribute("src","./assets/img/no-image.png");
        e.removeAttribute("onError");
        e.removeAttribute("onclick");
    }
    <?php if (!$detect->isMobile()) { ?>
    $(document).ready(function() {
        $('.boxscore-table').DataTable( {
            paging: false,
            searching: false,
            "order": [ 0, 'asc' ],
            "columnDefs": [ {
                "targets"  : 'no-sort',
                "orderable": false,
            }]
        });
    });
    <?php } ?>

    function updateStats() {
        $.ajax({
            url: 'live-game.php',
            type: 'POST',
            data: { gameId: <?= $gameId ?> },
            success: function(data) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');
                $('#score').html(doc.querySelector('#score').innerHTML);
                $('#game-date').html(doc.querySelector('#game-date').innerHTML);
                $('#away-sog').html(doc.querySelector('#away-sog').innerHTML);
                $('#away-pp').html(doc.querySelector('#away-pp').innerHTML);
                $('#away-pim').html(doc.querySelector('#away-pim').innerHTML);
                $('#away-fo').html(doc.querySelector('#away-fo').innerHTML);
                $('#away-hits').html(doc.querySelector('#away-hits').innerHTML);
                $('#away-blks').html(doc.querySelector('#away-blks').innerHTML);
                $('#home-sog').html(doc.querySelector('#home-sog').innerHTML);
                $('#home-pp').html(doc.querySelector('#home-pp').innerHTML);
                $('#home-pim').html(doc.querySelector('#home-pim').innerHTML);
                $('#home-fo').html(doc.querySelector('#home-fo').innerHTML);
                $('#home-hits').html(doc.querySelector('#home-hits').innerHTML);
                $('#home-blks').html(doc.querySelector('#home-blks').innerHTML);
                $('#game-scoring-plays').html(doc.querySelector('#game-scoring-plays').innerHTML);
                $('#game-penalties').html(doc.querySelector('#game-penalties').innerHTML);
                $('#away-roster-stats').html(doc.querySelector('#away-roster-stats').innerHTML);
                $('#home-roster-stats').html(doc.querySelector('#home-roster-stats').innerHTML);
            }
        });
    }

    setInterval(updateStats, 10000); // Update every 10 seconds
</script>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>