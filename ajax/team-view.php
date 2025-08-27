<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/team.php';
// Process request parameters based on request type
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // AJAX request - prioritize POST parameter
    $activeTeam = $_POST['active_team'];
} else {
    // Direct access - check for team_abbr parameter first, then GET active_team
    if (!defined('IN_PAGE')) {
        include_once '../header.php';
    }
    
    if(isset($_GET['team_abbr'])) {
        // Convert abbreviation to team ID using your existing function
        $activeTeam = abbrevToTeamId($_GET['team_abbr']);
    } elseif(isset($_GET['active_team'])) {
        $activeTeam = $_GET['active_team'];
    } else {
        // Default fallback or error handling
        header("Location: " . rtrim(BASE_URL, '/') . "/404.php");
        exit;
    }
}


$utcTimezone = new DateTimeZone('UTC');
$data = team_prepare($activeTeam, $season);
extract($data);
// Defensive defaults to avoid "Trying to get property of non-object" warnings in views
$teamInfo = (isset($teamInfo) && is_object($teamInfo)) ? $teamInfo : new stdClass();
$teamNameDefault = isset($teamInfo->teamName->default) ? $teamInfo->teamName->default : 'Team';
$teamCommonDefault = isset($teamInfo->teamCommonName->default) ? $teamInfo->teamCommonName->default : $teamNameDefault;
$teamAbbrev = isset($teamAbbrev) ? $teamAbbrev : '';
$teamAbbrev2 = isset($teamAbbrev2) ? $teamAbbrev2 : '';
$teamRosterInfo = (isset($teamRosterInfo) && is_object($teamRosterInfo)) ? $teamRosterInfo : null;
$teamRosterStats = isset($teamRosterStats) ? $teamRosterStats : null;
$teamStatsAdv = isset($teamStatsAdv) ? $teamStatsAdv : null;
$injuryCount = isset($injuryCount) ? $injuryCount : 0;
$medianAge = isset($medianAge) ? $medianAge : '';
$injuredPlayerIds = isset($injuredPlayerIds) ? $injuredPlayerIds : [];
$schedules = (isset($schedules) && is_object($schedules)) ? $schedules : (object)[];
?>

<style>
.team-banner-<?= $activeTeam ?>::before { background-image: linear-gradient( to bottom, transparent, var(--main-bg-color)),url('assets/img/team-banners/min/<?= $activeTeam ?>-min.webp');}
</style>
<main>
    <div class="wrap team-view team-banner-<?= htmlspecialchars($activeTeam) ?>">
        <div class="team-view-main">
            <div class="team-header">
                <div class="selected-team">
                    <img src="assets/img/teams/<?= htmlspecialchars($activeTeam) ?>.svg" alt="<?= htmlspecialchars(getValue($teamNameDefault, 'Team')) ?> logo" />
                    <div class="team-name">
                        <h2><?= htmlspecialchars(getValue($teamNameDefault, '')) ?></h2>
                        <div class="team-quick-links">
                            <a href="javascript:void(0)" id="showGameLog" class="btn outline sm" data-value="<?= $activeTeam ?>">Game Log</a>
                        </div>
                    </div>
                </div>
                <div class="record-wrap">
                    <div class="record" title="Season Record">
                        <?php
                        $stats = [
                            'wins' => 'W',
                            'losses' => 'L',
                            'otLosses' => 'OT'
                        ];
                        foreach ($stats as $key => $label) {
                            $val = isset($teamInfo->{$key}) ? $teamInfo->{$key} : null;
                            echo '<div class="stat ' . htmlspecialchars($key) . '">
                                    <div>' . htmlspecialchars($label) . '</div>
                                    <p class="stat-display">' . getValue($val) . '</p>
                                </div>
                                <div class="divider-vertical"></div>';
                        }
                        ?>
                    </div>
                    <div class="stat place" title="League Standing">
                        <div>RANK</div>
                        <p><?= getValue($teamInfo->leagueSequence) ?></p>
                    </div>
                </div>
            </div>
            <div class="team-statistics">
                <div class="stats">
                    <?php
                    $teamStats = [
                        'homeWins' => 'Home Wins',
                        'homeLosses' => 'Home Loss',
                        'roadWins' => 'Road Wins',
                        'roadLosses' => 'Road Loss',
                        'goalFor' => 'Goals For',
                        'goalAgainst' => 'Goals Against',
                        'goalDifferential' => 'Goal Diff',
                        'streakCode' => 'Streak'
                    ];
                    foreach ($teamStats as $key => $label) {
                        echo '<div class="stat">
                                <label>' . htmlspecialchars($label) . '</label>
                                <p class="stat-display">' . ($key === 'streakCode' ? getStreak($teamInfo->$key, $teamInfo->streakCount) : getValue($teamInfo->$key)) . '</p>
                            </div>';
                    }
                    ?>
                    <div class="stat">
                        <label>PP%</label>
                        <?php if ($teamStatsAdv && isset($teamStatsAdv->data[0]->powerPlayPct)) {
                            echo '<p class="stat-display">' . getPercentage($teamStatsAdv->data[0]->powerPlayPct) . '</p>';
                        } else {
                            echo '<p class="stat-display">N/A</p>';
                        } ?>
                    </div>
                    <div class="stat">
                        <label>PK%</label>
                        <?php if ($teamStatsAdv && isset($teamStatsAdv->data[0]->penaltyKillPct)) {
                            echo '<p class="stat-display">' . getPercentage($teamStatsAdv->data[0]->penaltyKillPct) . '</p>';
                        } else {
                            echo '<p class="stat-display">N/A</p>';
                        } ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="teamMain" class="ani">
            <div class="schedule-games swiper">
                <div class="swiper-wrapper">
                    <?php
                    $now = date("Y-m-d");
                    $then = date("Y-m-d", strtotime("+2 week"));
                    // schedules was prepared in team_prepare and normalized by controller
                    if (isset($schedules->games) && is_array($schedules->games)) {
                        foreach ($schedules->games as $result) {
                            $g = $result; // keep naming consistent
                            if (!is_object($g) || !isset($g->gameDate)) continue;
                            if (!isset($g->gameState) || $g->gameState !== 'FUT') continue;
                    ?>
                        <div class="swiper-slide item schedule-game <?php if(isset($g->gameType) && $g->gameType === 1) { echo 'preseason'; } elseif (isset($g->gameType) && $g->gameType === 2) { echo 'regular'; } elseif (isset($g->gameType) && $g->gameType === 3) { echo 'playoff'; } ?>">
                            <div class="schedule-game-date"><strong><?= htmlspecialchars($g->gameDate) ?></strong> at <?= htmlspecialchars(isset($g->venue->default) ? $g->venue->default : '') ?></div>
                            <div class="schedule-game-visual">
                                <div class="schedule-game-away">
                                    <?php $awayId = isset($g->awayTeam->id) ? $g->awayTeam->id : ''; ?>
                                    <img class="game-team-logo" src="assets/img/teams/<?= htmlspecialchars($awayId) ?>.svg" alt="<?= htmlspecialchars(teamToName($awayId)) ?> logo" />
                                    <div class="game-team-fill" style="background: linear-gradient(142deg, <?= teamToColor($awayId) ?> 0%, rgba(255,255,255,0) 58%);"></div>
                                </div>
                                <div class="schedule-game-vs">
                                    <div class="vs">VS</div>
                                    <div class="time theTimeSimple"><?= htmlspecialchars(isset($g->startTimeUTC) ? $g->startTimeUTC : '') ?></div>
                                </div>
                                <div class="schedule-game-home">
                                    <?php $homeId = isset($g->homeTeam->id) ? $g->homeTeam->id : ''; ?>
                                    <img class="game-team-logo" src="assets/img/teams/<?= htmlspecialchars($homeId) ?>.svg" alt="<?= htmlspecialchars(teamToName($homeId)) ?> logo" />
                                    <div class="game-team-fill" style="background: linear-gradient(-142deg, <?= teamToColor($homeId) ?> 0%, rgba(255,255,255,0) 58%);"></div>
                                </div>
                            </div>
                        </div>
                    <?php }
                    }
                    ?>
                </div>
            </div>
            <div class="team-roster-header">
                <div class="team-roster-header-cont">
                    <div class="stats">
                        <div data-tooltip="Average Age"><i class="bi bi-person-gear" ></i> <span><?= $medianAge; ?></span></div>
                        <?php if ($injuryCount > 0) { ?>
                        <div data-tooltip="Injuries"><a href="javascript:void(0);" id="injury-list-toggle"><i class="bi bi-bandaid"></i> <span><?= $injuryCount; ?></span></a></div>
                        <?php } ?>
                    </div>
                    <div class="btn-group filter-team-roster right">
                        <i class="bi bi-filter icon"></i>
                        <a class="filter-btn btn sm active" data-type="forward" href="javascript:void(0)">Forwards</a>
                        <a class="filter-btn btn sm" data-type="defenseman" href="javascript:void(0)">Defensemen</a>
                        <a class="filter-btn btn sm" data-type="goalie" href="javascript:void(0)">Goalies</a>
                    </div>
                </div>
                <div class="hidden-box" id="injury-list">
                    <?php getInjuriesTeam($teamAbbrev2) ?>
                </div>
            </div>            
            <div class="team-roster grid grid-300 grid-gap-lg grid-gap-row-lg" grid-max-col-count="3">
            <?php if ($teamRosterInfo && $teamRosterStats) { renderTeamRoster($teamRosterInfo, $teamRosterStats, $activeTeam, $injuredPlayerIds); } else { echo '<p>No roster available.</p>'; } ?>
            </div><!-- END .team-roster -->
            <div class="team-prospects">
                <h3><?= htmlspecialchars(getValue($teamCommonDefault, 'Team')) ?> Prospects</h3>
                <div class="grid grid-300" grid-max-col-count="5">
                <?php if ($teamAbbrev) { teamProspects($teamAbbrev); } ?>
                </div>
            </div>

            <?php
            // Check if this team has a subreddit before including the team reddit section
            $teamSubreddit = $teamAbbrev ? getTeamRedditSub($teamAbbrev) : null;
            if ($teamSubreddit) {
                // Always use the relative path from the current file location
                include '../templates/team-reddit-feed.php';
            }
            ?>
            
        </div> <!-- END #teamMain -->
    </div> <!-- END .wrap -->
</main>

<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { if (!defined('IN_PAGE')) { include_once '../footer.php'; } } ?>