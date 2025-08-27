<?php
$playerID = $_POST['player'];
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/player.php';
$careerData = player_fetch_career($playerID);
$careerTotals = $careerData->careerTotals->regularSeason;
$careerTotalsPlayoffs = $careerData->careerTotals->playoffs ?? (object)[
    'gamesPlayed' => 0,
    'wins' => 0,
    'shutouts' => 0,
    'savePctg' => 0,
    'goalsAgainstAvg' => 0,
    'goals' => 0,
    'assists' => 0,
    'points' => 0,
    'shootingPctg' => 0,
    'plusMinus' => 0,
    'pim' => 0,
];
$careerAll = $careerData->seasonTotals;
?>
<div class="stats-player-inner">
    <div class="career-stats">
        <?php if ($careerData->position == 'G') { ?>
        <table class="phone-hide">
            <thead>
                <td>Games</td>
                <td>Wins</td>
                <td>Shutouts</td>
                <td>SV%</td>
                <td>GAA</td>
            </thead>
            <tbody>
                <td><?= $careerTotals->gamesPlayed ?></td>
                <td><?= $careerTotals->wins ?></td>
                <td><?= $careerTotals->shutouts ?></td>
                <td><?= number_format((float)$careerTotals->savePctg, 3, '.', '') ?></td>
                <td><?= number_format((float)$careerTotals->goalsAgainstAvg, 2, '.', '') ?></td>
            </tbody>
        </table>
        <div class="phone-show">
            <div class="stat">
                <div class="label">Games</div>
                <div class="value"><?= $careerTotals->gamesPlayed ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">GAA</div>
                <div class="value"><?= isset($careerTotals->goalsAgainstAvg) ? number_format((float)$careerTotals->goalsAgainstAvg, 2, '.', '') : '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Save %</div>
                <div class="value"><?= isset($careerTotals->savePctg) ? number_format((float)$careerTotals->savePctg, 3, '.', '') : '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Wins</div>
                <div class="value"><?= $careerTotals->wins ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Shutouts</div>
                <div class="value"><?= $careerTotals->shutouts ?? '' ?></div>
            </div>
        </div> <!-- GOALIE REGULAR SEASONS .phone-show -->
        <div class="title stats">
            <h3 class="header-text" style="font-size: 1.3rem;">Playoffs</h3>
        </div>
        <table class="phone-hide">
            <thead>
                <td>Games</td>
                <td>Wins</td>
                <td>Shutouts</td>
                <td>SV%</td>
                <td>GAA</td>
            </thead>
            <tbody>
                <td><?= $careerTotalsPlayoffs->gamesPlayed ?></td>
                <td><?= $careerTotalsPlayoffs->wins ?></td>
                <td><?= $careerTotalsPlayoffs->shutouts ?></td>
                <td><?= number_format((float)$careerTotalsPlayoffs->savePctg, 3, '.', '') ?></td>
                <td><?= number_format((float)$careerTotalsPlayoffs->goalsAgainstAvg, 2, '.', '') ?></td>
            </tbody>
        </table>
        <div class="phone-show">
            <div class="stat">
                <div class="label">Games</div>
                <div class="value"><?= $careerTotalsPlayoffs->gamesPlayed ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">GAA</div>
                <div class="value"><?= isset($careerTotalsPlayoffs->goalsAgainstAvg) ? number_format((float)$careerTotalsPlayoffs->goalsAgainstAvg, 2, '.', '') : '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Save %</div>
                <div class="value"><?= isset($careerTotalsPlayoffs->savePctg) ? number_format((float)$careerTotalsPlayoffs->savePctg, 3, '.', '') : '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Wins</div>
                <div class="value"><?= $careerTotalsPlayoffs->wins ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Shutouts</div>
                <div class="value"><?= $careerTotalsPlayoffs->shutouts ?? '' ?></div>
            </div>
        </div> <!-- GOALIE PLAYOFFS .phone-show -->
        <?php } else { ?>
        <table class="phone-hide">
            <thead>
                <td>Games</td>
                <td>Goals</td>
                <td>Assists</td>
                <td>Points</td>
                <td>P/GP</td>
                <td>Shot %</td>
                <td>+/-</td>
                <td>PIM</td>
            </thead>
            <tbody>
                <td><?= $careerTotals->gamesPlayed ?></td>
                <td><?= $careerTotals->goals ?></td>
                <td><?= $careerTotals->assists ?></td>
                <td><?= $careerTotals->points ?></td>
                <td><?= number_format((float)$careerTotals->points / $careerTotals->gamesPlayed, 2, '.', '') ?></td>
                <td><?= number_format((float)$careerTotals->shootingPctg * 100, 1, '.', '') ?></td>
                <td><?= $careerTotals->plusMinus ?></td>
                <td><?= $careerTotals->pim ?></td>
            </tbody>
        </table>
        <div class="phone-show">
            <div class="stat">
                <div class="label">Games</div>
                <div class="value"><?= $careerTotals->gamesPlayed ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Goals</div>
                <div class="value"><?= $careerTotals->goals ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Assists</div>
                <div class="value"><?= $careerTotals->assists ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Points</div>
                <div class="value"><?= $careerTotals->points ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">PPG</div>
                <div class="value"><?= isset($careerTotals->points) && isset($careerTotals->gamesPlayed) ? number_format((float)$careerTotals->points / $careerTotals->gamesPlayed, 2, '.', '') : '' ?></div>
            </div>
            <div class="stat">
                <div class="label">+/-</div>
                <div class="value"><?= $careerTotals->plusMinus ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">PIM</div>
                <div class="value"><?= $careerTotals->pim ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">S%</div>
                <div class="value"><?= isset($careerTotals->shootingPctg) ? number_format((float)$careerTotals->shootingPctg * 100, 0, '.', '') : '' ?></div>
            </div>
        </div> <!-- FORWARD STATS .phone-show -->
        <div class="title stats">
            <h3 class="header-text" style="font-size: 1.3rem;">Playoffs</h3>
        </div>
        <table class="phone-hide">
            <thead>
                <td>Games</td>
                <td>Goals</td>
                <td>Assists</td>
                <td>Points</td>
                <td>P/GP</td>
                <td>Shot %</td>
                <td>+/-</td>
                <td>PIM</td>
            </thead>
            <tbody>
                <td><?= $careerTotalsPlayoffs->gamesPlayed ?></td>
                <td><?= $careerTotalsPlayoffs->goals ?></td>
                <td><?= $careerTotalsPlayoffs->assists ?></td>
                <td><?= $careerTotalsPlayoffs->points ?></td>
                <td><?= ($careerTotalsPlayoffs->gamesPlayed > 0) ? number_format((float)$careerTotalsPlayoffs->points / $careerTotalsPlayoffs->gamesPlayed, 2, '.', '') : '0.00' ?></td>
                <td><?= number_format((float)$careerTotalsPlayoffs->shootingPctg * 100, 1, '.', '') ?></td>
                <td><?= $careerTotalsPlayoffs->plusMinus ?></td>
                <td><?= $careerTotalsPlayoffs->pim ?></td>
            </tbody>
        </table>
        <div class="phone-show">
            <div class="stat">
                <div class="label">Games</div>
                <div class="value"><?= $careerTotalsPlayoffs->gamesPlayed ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Goals</div>
                <div class="value"><?= $careerTotalsPlayoffs->goals ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Assists</div>
                <div class="value"><?= $careerTotalsPlayoffs->assists ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">Points</div>
                <div class="value"><?= $careerTotalsPlayoffs->points ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">PPG</div>
                <div class="value"><?= isset($careerTotalsPlayoffs->points) && isset($careerTotalsPlayoffs->gamesPlayed) && $careerTotalsPlayoffs->gamesPlayed > 0 ? number_format((float)$careerTotalsPlayoffs->points / $careerTotalsPlayoffs->gamesPlayed, 2, '.', '') : '0.00' ?></div>
            </div>
            <div class="stat">
                <div class="label">+/-</div>
                <div class="value"><?= $careerTotalsPlayoffs->plusMinus ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">PIM</div>
                <div class="value"><?= $careerTotalsPlayoffs->pim ?? '' ?></div>
            </div>
            <div class="stat">
                <div class="label">S%</div>
                <div class="value"><?= isset($careerTotalsPlayoffs->shootingPctg) ? number_format((float)$careerTotalsPlayoffs->shootingPctg * 100, 0, '.', '') : '' ?></div>
            </div>
        </div> <!-- FORWARD PLAYOFFS STATS .phone-show -->
        <?php } ?>
    </div>

    <div class="awards">
        <?php 
        // Initialize an empty array to count trophies
        $trophyCounts = [];

        // Mapping array for trophy names to image file names
        $trophyImageMap = [
            "Art Ross Trophy" => "art_ross",
            "Bill Masterton Memorial Trophy" => "bill_masterton_memorial",
            "Calder Memorial Trophy" => "calder_memorial",
            "Conn Smythe Trophy" => "conn_smythe",
            "E.J. McGuire Award of Excellence" => "placeholder",
            "Frank J. Selke Trophy" => "frank_selke",
            "Hart Memorial Trophy" => "hart_memorial",
            "Jack Adams Award" => "jack_adams",
            "James Norris Memorial Trophy" => "james_norris_memorial",
            "King Clancy Memorial Trophy" => "king_clancy",
            "Lady Byng Memorial Trophy" => "lady_byng_memorial",
            "Mark Messier NHL Leadership Award" => "mark_messier",
            "Maurice “Rocket” Richard Trophy" => "maurice_richard",
            "Presidents' Trophy" => "presidents_trophy",
            "Ted Lindsay Award" => "ted_lindsay",
            "Vezina Trophy" => "vezina",
            "William M. Jennings Trophy" => "william_jennings",
            "Stanley Cup" => "stanley_cup",
        ];

        // Check if awards property exists and is an array
        if (isset($careerData->awards) && is_array($careerData->awards)) {
            // Loop through awards to count trophies
            foreach ($careerData->awards as $award) {
                $trophyName = $award->trophy->default;
                if (!isset($trophyCounts[$trophyName])) {
                    $trophyCounts[$trophyName] = 0;
                }
                $trophyCounts[$trophyName] += count($award->seasons);
            }
        }

        // Only display the trophies div if there are trophies to show
        if (!empty($trophyCounts)) {
            echo '<div class="trophies grid grid-150 grid-gap-lg grid-gap-row-xl">';
            // Loop through trophy counts to display each trophy
            foreach ($trophyCounts as $trophyName => $count) {
                $imageFileName = isset($trophyImageMap[$trophyName]) ? $trophyImageMap[$trophyName] : strtolower(str_replace(' ', '_', $trophyName));
                
                // Get the first two words of the trophy name
                $trophyNameWords = explode(' ', $trophyName);
                $shortTrophyName = implode(' ', array_slice($trophyNameWords, 0, 2));
                
                echo '<div class="trophy">';
                echo '<div class="trophy-wrapper">';
                echo '<img src="'. BASE_URL .'/assets/img/trophies/' . $imageFileName . '.png" alt="' . $trophyName . '">';
                echo '<div class="trophy-count-wrapper">';
                echo '<h2 class="trophy-count">' . $count . '<span>x</span></h2>';
                echo '<p class="trophy-name">' . $shortTrophyName . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>

    <div class="career-stats-two">
    <?php
    // Group career stats by season and team for NHL only
    $seasonTeamMap = [];
    foreach ($careerAll as $career) {
        if ($career->leagueAbbrev == 'NHL') {
            $key = $career->season . '_' . $career->teamName->default;
            if (!isset($seasonTeamMap[$key])) {
                $seasonTeamMap[$key] = [];
            }
            $seasonTeamMap[$key][$career->gameTypeId] = $career; // 2 = regular, 3 = playoffs
        }
    }

    $playoffBlocks = [];
    foreach ($seasonTeamMap as $key => $types) {
        // Get season and team from key
        list($season, $teamName) = explode('_', $key, 2);
        $season1 = substr($season, 0, -4);
        $season2 = substr($season, 4, 4);
        $teamColorConverted = teamNameToIdConvert($teamName);
        ?>
        <div class="season-career">
            <div class="header-season-career">
                <h3 class="header-text">
                    <?= idToTeamAbbrev($teamColorConverted) ?><span class="season"> <?= '- ' . $season1 . ' / ' . $season2 ?></span>
                </h3>
                <img class="team-logo" src="assets/img/teams/<?= $teamColorConverted ?>.svg" alt="<?= $teamName ?>">
            </div>
            <?php
            // REGULAR SEASON - render inline
            if (isset($types[2])) {
                $career = $types[2];
                ?>
                <div class="section">
                    <?php if ($careerData->position == 'G') { ?>
                        <div class="career-stats-group">
                            <div class="stat">
                                <div class="label">GP</div>
                                <div class="value"><?= $career->gamesPlayed ?? '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">GAA</div>
                                <div class="value"><?= isset($career->goalsAgainstAvg) ? number_format((float)$career->goalsAgainstAvg, 2, '.', '') : '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">SV %</div>
                                <div class="value"><?= isset($career->savePctg) ? number_format((float)$career->savePctg, 3, '.', '') : '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">W</div>
                                <div class="value"><?= $career->wins ?? '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">SO</div>
                                <div class="value"><?= $career->shutouts ?? '' ?></div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="career-stats-group">
                            <div class="stat">
                                <div class="label">GP</div>
                                <div class="value"><?= $career->gamesPlayed ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">G</div>
                                <div class="value"><?= $career->goals ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">A</div>
                                <div class="value"><?= $career->assists ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">P</div>
                                <div class="value"><?= $career->points ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">PM</div>
                                <div class="value"><?= $career->plusMinus ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">PIM</div>
                                <div class="value"><?= $career->pim ?></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <a href="javascript:void(0);" data-player="<?= $playerID ?>" data-skater-goalie="<?= $careerData->position ?>" data-season-selection="<?= $season ?>" data-season-type="2" class="btn sm outline player-game-log">Full Game Log</a>
            <?php } ?>

            <?php
            // PLAYOFFS - capture for later so playoffs are grouped at bottom
            if (isset($types[3])) {
                $career = $types[3];
                ob_start();
                ?>
                <div class="season-career">
                    <div class="header-season-career">
                        <h3 class="header-text">
                            <?= idToTeamAbbrev($teamColorConverted) ?><span class="season"> <?= '- ' . $season1 . ' / ' . $season2 ?></span>
                        </h3>
                        <img class="team-logo" src="assets/img/teams/<?= $teamColorConverted ?>.svg" alt="<?= $teamName ?>">
                    </div>
                    <div class="section">
                        <?php if ($careerData->position == 'G') { ?>
                        <div class="career-stats-group">
                            <div class="stat">
                                <div class="label">GP</div>
                                <div class="value"><?= $career->gamesPlayed ?? '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">GAA</div>
                                <div class="value"><?= isset($career->goalsAgainstAvg) ? number_format((float)$career->goalsAgainstAvg, 2, '.', '') : '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">SV %</div>
                                <div class="value"><?= isset($career->savePctg) ? number_format((float)$career->savePctg, 3, '.', '') : '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">W</div>
                                <div class="value"><?= $career->wins ?? '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">SO</div>
                                <div class="value"><?= $career->shutouts ?? '' ?></div>
                            </div>
                        </div>
                        <?php } else { ?>
                        <div class="career-stats-group">
                            <div class="stat">
                                <div class="label">GP</div>
                                <div class="value"><?= $career->gamesPlayed ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">G</div>
                                <div class="value"><?= $career->goals ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">A</div>
                                <div class="value"><?= $career->assists ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">P</div>
                                <div class="value"><?= $career->points ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">PM</div>
                                <div class="value"><?= $career->plusMinus ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">PIM</div>
                                <div class="value"><?= $career->pim ?></div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <a href="javascript:void(0);" data-player="<?= $playerID ?>" data-skater-goalie="<?= $careerData->position ?>" data-season-selection="<?= $season ?>" data-season-type="3" class="btn sm outline player-game-log">Full Game Log</a>
                </div>
                <?php
                $playoffBlocks[] = ob_get_clean();
            }
            ?>
        </div>
    <?php } ?>
        <div class="title stats">
            <h3 class="header-text" style="font-size:1.3rem;">Regular Season</h3>
        </div>
    </div>

    <?php
    // Render playoffs grouped at the bottom
    if (!empty($playoffBlocks)) {
        echo '<div class="career-playoffs career-stats-two">';
        foreach ($playoffBlocks as $block) {
            echo $block;
        }
        echo '<div class="title stats"><h3 class="header-text" style="font-size:1.3rem;">Playoffs</h3></div>';
        echo '</div>';
    }
    ?>
</div>