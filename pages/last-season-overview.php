<?php
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
include_once '../path.php';
include_once '../includes/functions.php';

function fetchPlayoffData($url) {
    $curl = curlInit($url);
    if ($curl === false) {
        // Handle error
        die('Error fetching playoff data');
    }
    return json_decode($curl);
}
?>
<main>
    <div class="wrap">
        <div id="last-season-overview">
            <div class="lso-header">
                <h1 class="lso-title main-title"><?= substr($lastSeason, 0, 4) . ' / ' . substr($lastSeason, 4) ?> Season Overview</h1>
                <div class="lso-award-winners">
                    <?php
                    $awardsApiUrl = 'https://records.nhl.com/site/api/award-details?cayenneExp=seasonId=' . $lastSeason . '&include=seasonId&include=trophy.name&include=trophy.imageUrl&include=player.firstName&include=player.lastName&include=player.position&include=player.id&include=value&include=team.id&include=team.franchiseId&include=team.fullName&include=team.placeName&include=team.commonName&include=team.triCode&include=team.league.abbreviation&include=status&include=imageUrl&include=playerImageUrl&sort=seasonId&dir=DESC';
                    $cacheFile = '../cache/award-winners-' . $lastSeason . '.json';
                    $cacheLifetime = 900000;

                    $records = fetchData($awardsApiUrl, $cacheFile, $cacheLifetime);
                    $awards = $records->data;
                    $displayedTrophies = [];
                    
                    $trophyImageMap = [
                        "Art Ross Trophy" => "art_ross",
                        "Bill Masterton Memorial Trophy" => "bill_masterton_memorial",
                        "Calder Memorial Trophy" => "calder_memorial",
                        "Clarence S. Campbell Bowl" => "clarence_s_campbell",
                        "Conn Smythe Trophy" => "conn_smythe",
                        "Frank J. Selke Trophy" => "frank_selke",
                        "Hart Memorial Trophy" => "hart_memorial",
                        "Jack Adams Award" => "jack_adams",
                        "James Norris Memorial Trophy" => "james_norris_memorial",
                        "Jim Gregory General Manager of the Year Award" => "jim_gregory",
                        "King Clancy Memorial Trophy" => "king_clancy",
                        "Lady Byng Memorial Trophy" => "lady_byng_memorial",
                        "Mark Messier NHL Leadership Award" => "mark_messier",
                        "Maurice “Rocket” Richard Trophy" => "maurice_richard",
                        "Presidents’ Trophy" => "presidents_trophy",
                        "Prince of Wales Trophy" => "prince_of_wales",
                        "Ted Lindsay Award" => "ted_lindsay",
                        "Vezina Trophy" => "vezina",
                        "William M. Jennings Trophy" => "william_jennings",
                        "Stanley Cup" => "stanley_cup",
                    ];
                    
                    foreach ($awards as $award) {
                        if ($award->trophy->name === "Jim Gregory General Manager of the Year Award") {
                            continue;
                        }
                        if ($award->status === 'WINNER' && !in_array($award->trophy->name, $displayedTrophies)) {
                            // Check if the award has a valid player or team name
                            if (($award->player !== null && !empty($award->player->firstName) && !empty($award->player->lastName)) || ($award->team !== null && !empty($award->team->fullName))) {
                    
                                if ($award->trophy->name === 'Stanley Cup') {
                                    echo '<div class="award team stanleycup">';
                                    echo '<div class="winner-image" style="background-image: url('. $award->imageUrl .')"></div>';
                                    echo '<svg class="team-fill" width="100%" height="100%">';
                                    echo '<rect width="100%" height="100%" fill="'. teamToColor($award->team->id) .'"></rect>';
                                    echo '<defs>';
                                    echo '<linearGradient id="gradient:r2:" x1="0" y1="0" x2="0" y2="1">';
                                    echo '<stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>';
                                    echo '<stop offset="65%" stop-opacity="0.35" stop-color="#000000"></stop>';
                                    echo '</linearGradient>';
                                    echo '</defs>';
                                    echo '<rect width="100%" height="100%" fill="url(#gradient:r2:)"></rect>';
                                    echo '</svg>';
                                    $teamAward = true;
                                } elseif ($award->player === null) {
                                    echo '<div class="award team">';
                                    $teamAward = true;
                                } else {
                                    echo '<div class="award">';
                                    $teamAward = false;
                                }
                    
                                // Use the helper function for absolute URLs
                                $imageFileName = isset($trophyImageMap[$award->trophy->name]) ? $trophyImageMap[$award->trophy->name] : strtolower(str_replace(' ', '_', $award->trophy->name));
                                echo '<img class="award-image" width="300" src="assets/img/trophies/' . $imageFileName . '.png" alt="' . $award->trophy->name . '">';
                                
                                if ($teamAward) {
                                    echo '<div class="team-info">';
                                    echo '<div class="team-img">';
                                    echo '<img width="70" height="70" src="assets/img/teams/' . $award->team->id . '.svg" alt="' . $award->team->fullName . '">';
                                    echo '</div>';
                                    echo '<div>';
                                    echo '<p class="weak">' . $award->trophy->name . '</p>';
                                    echo '<p class="strong">' . $award->team->fullName . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="player-info">';
                                    echo '<a class="headshot" href="#" id="player-link" data-link="'. $award->player->id .'">';      
                                    echo '<svg class="headshot_wrap" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(0.805); z-index: 2;">';
                                    echo '<mask id="circleMask:r2:">';
                                    echo '<svg>';
                                    echo '<path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>';
                                    echo '</svg>';
                                    echo '</mask>';
                                    echo '<image mask="url(#circleMask:r2:)" fill="#000000" id="canTop" height="128" href="https://assets.nhle.com/mugs/nhl/'. $lastSeason .'/'. $award->team->triCode .'/'. $award->player->id .'.png"></image>';
                                    echo '</svg>';
                                    echo '<svg class="team-fill" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(0.8);">';
                                    echo '<circle cx="64" cy="72" r="56" fill="'. teamToColor($award->team->id) .'"></circle>';
                                    echo '<defs>';
                                    echo '<linearGradient id="gradient:r2:" x1="0" y1="0" x2="0" y2="1">';
                                    echo '<stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>';
                                    echo '<stop offset="65%" stop-opacity="0.35" stop-color="#000000"></stop>';
                                    echo '</linearGradient>';
                                    echo '</defs>';
                                    echo '<circle cx="64" cy="72" r="56" fill="url(#gradient:r2:)"></circle>';
                                    echo '</svg>';
                                    echo '</a><!-- END .headshot -->';
                                    echo '<div>';
                                    echo '<p class="weak">' . $award->trophy->name . '</p>';
                                    echo '<p class="strong">' . $award->player->firstName . ' ' . $award->player->lastName . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                
                                // Mark this trophy as displayed
                                $displayedTrophies[] = $award->trophy->name;
                            }
                        }
                    }
                    ?>
                </div>
                <div class="component-header">
                    <h3 class="title">Regular Season Leaders</h3>
                </div>
                <div class="home-leaders grid grid-300 grid-gap-lg grid-gap-row-xl">
                    <?php
                        $skaterApiUrl = 'https://api-web.nhle.com/v1/skater-stats-leaders/' . $lastSeason . '/2?categories=points%2Cgoals';
                        $goalieApiUrl = 'https://api-web.nhle.com/v1/goalie-stats-leaders/' . $lastSeason . '/2?categories=goalsAgainstAverage%2Cwins';

                        $apiUrls = [$skaterApiUrl, $goalieApiUrl];
                        $cacheDir = '../cache/';
                        $cacheLifetime = 90000;

                        foreach ($apiUrls as $apiUrl) {
                            $fileName = ($apiUrl === $skaterApiUrl) ? 'skater-leaders-mini-lastseason' : 'goalie-leaders-mini-lastseason';
                            $cacheFile = $cacheDir . $fileName . '.json';

                            $apiResponse = fetchData($apiUrl, $cacheFile, $cacheLifetime);

                            foreach (['points', 'goals', 'goalsAgainstAverage', 'wins'] as $category) {
                                if (!isset($apiResponse->$category)) {
                                    continue;
                                }
                                $leaders = $apiResponse->$category;
                                include '../templates/leaders-mini.php';
                            }
                        }
                    ?>
                </div>
            </div>
        </div>
        <div class="playoffs-table">
            <?= renderPlayoffsBracket('2024', 'Stanley Cup Playoffs', true, false) ?>
        </div>
        <dialog id="seriesModal" class="modal">
            <div class="modal-header">
                <h3 class="title">Playoff Series</h3>
                <span class="close close-btn bi bi-x-lg"></span>
            </div>
            <div class="modal-content">
                <div id="seriesContent"></div>
            </div>
            <div class="modal-footer">
            </div>
        </dialog>
        <script type="module">
            import { initPlayoffSeriesHandlers } from './assets/js/modules/standings-handlers.js';
            document.addEventListener('DOMContentLoaded', initPlayoffSeriesHandlers);
        </script>
    </div>
</main>
<?php
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include_once '../footer.php'; }
?>