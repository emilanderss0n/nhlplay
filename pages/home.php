<?php
// Home page content previously inside index.php main block.
// This file is intended to be included by Router::dispatch() inside a <main> element.
?>
<div class="wrap">
        <div class="hero-bar" style="margin-bottom: 3rem">
            <?php $app = $app ?? null; $seasonBreak = $app['seasonBreak'] ?? false; if (!$seasonBreak) {
                ob_start();
                getInjuriesLeague();
                $injuriesOutput = ob_get_clean();
                if (!empty($injuriesOutput)) {
                    echo '<div class="alert danger">';
                    echo '<div class="animated-health">';
                    echo '<svg width="32px" height="24px">';
                    echo '<polyline points="0.0785 11.977, 7 11.977, 10.9215 24, 21.5 0, 25 12, 32 12" id="back"></polyline>';
                    echo '<polyline points="0.0785 11.977, 7 11.977, 10.9215 24, 21.5 0, 25 12, 32 12" id="front"></polyline>';
                    echo '</svg>';
                    echo '</div>';
                    echo '<span>There are recent player injuries reported, <a id="injuriesLink" href="#injuriesAnchor">click here</a> to examine them</span>';
                    echo '</div>';
                }
            } else {
                echo '<div class="trades trades-frontpage grid grid-300 grid-gap-lg grid-gap-row-lg" grid-max-col-count="2">';
                echo renderTradeContent(true, 6, true);
                echo '</div>';

                echo '<a href="'.BASE_URL.'/pages/last-season-overview" rel="page" tabindex="0" role="button">';
                echo '<div class="season-break-message" style="background-image: url('.BASE_URL.'/assets/img/stanley_cup_fla_2025.webp);">';
                echo '<div class="inner">';
                echo '<img class="season-break-logo" src="'.BASE_URL.'/assets/img/teams/13.svg" alt="Florida Panthers" />';
                echo '<h3>Congratulations to the Florida Panthers for winning the Stanley Cup in 2025!</h3>';
                echo '<p>Click here to see the last season overview</p>';
                echo '</div>';
                echo '</div>';
                echo '</a>';
            } ?>
        </div>
            <?php $seasonBreak = $app['seasonBreak'] ?? false; if (!$seasonBreak) { ?>
            <div class="injuries-home transition-zoom-in" id="injuriesAnchor">
                <div class="injuries team-boxes grid grid-400 grid-gap grid-gap-row" grid-max-col-count="2">
                    <?php getInjuriesLeague() ?>
                </div>
            </div>
            <div class="component-header">
                <h3 class="title">Games Today</h3>
                <div class="see-score-check flex-default">
                    <p>See Score</p>
                    <label class="switch">
                        <input type="checkbox">
                        <div class="slider">
                            <div class="switch-circle">
                                <svg class="cross" xml:space="preserve" viewBox="0 0 365.696 365.696" y="0" x="0" height="6"
                                    width="6" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <path data-original="#000000" fill="currentColor"
                                            d="M243.188 182.86 356.32 69.726c12.5-12.5 12.5-32.766 0-45.247L341.238 9.398c-12.504-12.503-32.77-12.503-45.25 0L182.86 122.528 69.727 9.374c-12.5-12.5-32.766-12.5-45.247 0L9.375 24.457c-12.5 12.504-12.5 32.77 0 45.25l113.152 113.152L9.398 295.99c-12.503 12.503-12.503 32.769 0 45.25L24.48 356.32c12.5 12.5 32.766 12.5 45.247 0l113.132-113.132L295.99 356.32c12.503 12.5 32.769 12.5 45.25 0l15.081-15.082c12.5-12.504 12.5-32.77 0-45.25zm0 0">
                                        </path>
                                    </g>
                                </svg>
                                <svg class="checkmark" xml:space="preserve" viewBox="0 0 24 24" y="0" x="0" height="10"
                                    width="10" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <path class="" data-original="#000000" fill="currentColor"
                                            d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z">
                                        </path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <div class="schedule no-team-selected grid grid-300 grid-gap-lg grid-gap-row" grid-max-col-count="4">
                <?php

                $todaysGames = strtotime(date('Y-m-d'));
                $startDate = strtotime(date('Y-m-d', strtotime('-1 day')));
                $endDate = strtotime(date('Y-m-d', strtotime('+1 day')));

                // Use the new NHL API utility
                $ApiUrl = NHLApi::scheduleNow();
                $curl = curlInit($ApiUrl);
                $schedules = json_decode($curl);

                if (isset($schedules->gameWeek)) {
                    foreach ($schedules->gameWeek as $gameWeek) {
                        $gameDateG = strtotime($gameWeek->date);
                        if ($gameDateG >= $startDate && $gameDateG <= $todaysGames) {
                            foreach ($gameWeek->games as $result) {
                                include 'templates/schedule-game-vs.php';
                            }
                        }
                    }
                } else {
                    echo '<div class="alert info">No games today</div>';
                } ?>
            </div>
            <div class="component-header" style="margin-top: 5rem">
                <h3 class="title">Stat Leaders</h3>
                <a href="<?= BASE_URL ?>/stat-leaders" rel="page" class="btn sm">More Stats</a>
            </div>
            <div class="home-leaders grid grid-300 grid-gap-lg grid-gap-row-lg">
                <?php
                // Use the new NHL API utility
                define('SKATER_API_URL', NHLApi::skaterStatsLeaders('current', '2', ['points', 'goals']));
                define('GOALIE_API_URL', NHLApi::goalieStatsLeaders('current', '2', ['goalsAgainstAverage', 'wins']));

                $apiUrls = [SKATER_API_URL, GOALIE_API_URL];
                $cacheDir = 'cache/';
                $cacheLifetime = 2000;

                foreach ($apiUrls as $apiUrl) {
                    $fileName = ($apiUrl === SKATER_API_URL) ? 'skater-leaders-mini' : 'goalie-leaders-mini';
                    $cacheFile = $cacheDir . $fileName . '.json';

                    $apiResponse = fetchData($apiUrl, $cacheFile, $cacheLifetime);

                    foreach (['points', 'goals', 'goalsAgainstAverage', 'wins'] as $category) {
                        if (!isset($apiResponse->$category)) {
                            continue;
                        }
                        $leaders = $apiResponse->$category;
                        include 'templates/leaders-mini.php';
                    }
                }
                ?>
            </div>
            <?php $playoffs = $app['playoffs'] ?? false; if ($playoffs) { ?>
                <div class="playoffs-table">
                    <?= renderPlayoffsBracket('2025', 'Stanley Cup Playoffs', true) ?>
                </div>
                <dialog id="seriesModal" class="modal">
                    <div class="modal-header">
                        <h3 class="title">Playoff Series</h3>
                        <span class="close close-btn bi bi-x-lg"></span>
                    </div>
                    <div class="modal-content">
                        <div id="seriesContent"></div>
                    </div>
                </dialog>
                <script type="module">
                    import { initPlayoffSeriesHandlers } from './assets/js/modules/standings-handlers.js';
                    document.addEventListener('DOMContentLoaded', initPlayoffSeriesHandlers);
                </script>
            <?php } ?>
            <div class="standings">
                <div class="component-header">
                    <h3 class="title">Current Standings</h3>
                    <div class="btn-group standings-filter">
                        <i class="bi bi-filter icon"></i>
                        <a class="btn sm active" id="standings-league" href="#">League</a>
                        <a class="btn sm" id="standings-conference" href="#">Conference</a>
                        <a class="btn sm" id="standings-divisions" href="#">Divisions</a>
                    </div>
                </div>
                <div id="standings-home">
                    <?php
                    // Use the new NHL API utility
                    $ApiUrl = NHLApi::standingsNow();
                    $cacheFile = 'cache/standings-league.json';
                    $cacheTime = 30 * 30;
                    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
                        $standing = json_decode(file_get_contents($cacheFile));
                    } else {
                        $curl = curlInit($ApiUrl);
                        $standing = json_decode($curl);
                        file_put_contents($cacheFile, json_encode($standing));
                    }
                    $detect = $app['detect'] ?? null;
                    renderLeagueTable($standing, $detect);
                    ?>
                </div>
            </div>
        <?php } ?>
        <div class="component-header" style="margin-top: 5rem">
            <h3 class="title">Latest NHL Videos</h3>
            <a href="https://www.youtube.com/@NHL" target="_blank" rel="noopener noreferrer" class="btn sm">NHL on YouTube</a>
        </div>
        <?php
        $channelId = "UCqFMzb-4AUf6WAIbl132QKA"; // NHL channel
        $maxResults = 9;
        renderYouTubeVideos($channelId, $maxResults);
        ?>
        <div class="component-header" style="margin-top: 5rem">
            <h3 class="title">Popular at r/hockey</h3>
            <a href="https://www.reddit.com/r/hockey/" target="_blank" rel="noopener noreferrer" class="btn sm">Visit r/hockey</a>
        </div>
        <div class="reddit-feed" id="reddit-feed-section" data-subreddit="hockey" data-limit="12">
            <?php include 'templates/reddit-frontpage.php'; ?>
        </div>
        <script type="module">
            import { initRedditPosts } from './assets/js/modules/reddit-handlers.js';
            document.addEventListener('DOMContentLoaded', initRedditPosts);
        </script>
    </div>
