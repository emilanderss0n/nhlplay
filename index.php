<?php 
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    include_once 'path.php';
    include_once 'includes/functions.php';  
    require_once "includes/MobileDetect.php";
    $detect = new \Detection\MobileDetect;
    $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
} else { 
    include_once 'header.php'; 
}

$page = 'home';


// Create cache directory if it doesn't exist
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
?>
<main>
    <div class="wrap">
        <div class="hero-bar" style="margin-bottom: 3rem">
            <?php 
            ob_start(); // Start output buffering
            getInjuriesLeague(); // Capture the output of the function in a buffer
            $injuriesOutput = ob_get_clean(); // Get the buffered output and stop output buffering
            if (!empty($injuriesOutput)) {
                echo '<div class="alert danger"><i class="bi bi-heart-pulse"></i> <span>There are recent player injuries reported, <a id="injuriesLink" href="#injuriesAnchor">click here</a> to examine them</span></div>'; 
            }
            ?>
        </div>
        <div class="injuries-home transition-zoom-in" id="injuriesAnchor">
            <div class="injuries team-boxes grid grid-400 grid-gap grid-gap-row" grid-max-col-count="2">
            <?php getInjuriesLeague() ?>
            </div>
        </div>
        <div class="component-header">
            <h3 class="title">Games Today</h3>
            <div class="see-score-check">
                <label class="switch">
                    <p>Show Score</p>
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <div class="schedule no-team-selected grid grid-300 grid-gap-lg grid-gap-row-sm" grid-max-col-count="4">
            <?php 
            $todaysGames = strtotime(date('Y-m-d'));
            $startDate = strtotime(date('Y-m-d', strtotime('-1 day')));
            $endDate = strtotime(date('Y-m-d', strtotime('+1 day')));

            $ApiUrl = 'https://api-web.nhle.com/v1/schedule/now';
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
            <a href="<?= BASE_URL ?>/stat-leaders" rel="page" class="btn sm">See More</a>
        </div>
        <div class="home-leaders grid grid-300 grid-gap-lg grid-gap-row-lg">
            <?php
            const SKATER_API_URL = 'https://api-web.nhle.com/v1/skater-stats-leaders/current/?categories=points,goals';
            const GOALIE_API_URL = 'https://api-web.nhle.com/v1/goalie-stats-leaders/current/?categories=goalsAgainstAverage,wins';

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
        <?php if ($playoffs) { ?>
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
                $ApiUrl = 'https://api-web.nhle.com/v1/standings/now';
                $cacheFile = 'cache/standings-league.json';
                $cacheTime = 30 * 30;
                if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
                    $standing = json_decode(file_get_contents($cacheFile));
                } else {
                    $curl = curlInit($ApiUrl);
                    $standing = json_decode($curl);
                    file_put_contents($cacheFile, json_encode($standing));
                }
                renderLeagueTable($standing, $detect);
                ?>
            </div>
        </div>
    </div>
</main>
<?php if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once 'footer.php'; } ?>