<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

// Check if user is switching between regular season and playoffs
$selectedPlayoffs = $playoffs; // Default to global setting
if(isset($_GET['playoffs'])) {
    $selectedPlayoffs = $_GET['playoffs'] === 'true';
}

// Check if user is selecting a different season
$selectedSeason = $season; // Default to global setting
if(isset($_GET['season'])) {
    $selectedSeason = $_GET['season'];
}

// Keep legacy global $season in sync so includes (seasonSelection.php) render correctly
$season = $selectedSeason;

?>
<main>
    <div class="wrap">
        <div class="component-header stat-leaders">
            <h3 class="title">Stat Leaders</h3>
            <div class="multi">
                <select id="seasonStatLeadersSelect" class="form-select">
                    <div class="select-options">
                        <?php include_once '../includes/seasonSelection.php'; ?>
                    </div>
                </select>
                <div class="season-select btn-group">
                    <i class="icon bi bi-filter"></i>
                    <a href="javascript:void(0);" class="btn sm <?= !$selectedPlayoffs ? 'active' : '' ?>" data-season="<?= $selectedSeason ?>" data-playoffs="false">Regular Season</a>
                    <a href="javascript:void(0);" class="btn sm <?= $selectedPlayoffs ? 'active' : '' ?>" data-season="<?= $selectedSeason ?>" data-playoffs="true">Playoffs</a>
                </div>
                <a href="javascript:void(0);" id="stat-leaders-toggle-table" class="btn sm" data-season="<?= $selectedSeason ?>" data-playoffs="<?= $selectedPlayoffs ? 'true' : 'false' ?>">Table</a>
            </div>
        </div>
        <div class="section-stats">
            <div class="stats-leaders skaters">
                <h3>Forwards</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="points" data-list="skaters" class="skaters option active">Points</a>
                    <a href="javascript:void(0);" data-type="goals" data-list="skaters" class="skaters option" data-load="true">Goals</a>
                    <a href="javascript:void(0);" data-type="assists" data-list="skaters" class="skaters option" data-load="true">Assists</a>
                </div>
                <div class="activity-content skaters"><span class="loader"></span></div>
                <div class="stat-points stat-holder skaters">
                    <?= renderStatHolder('skaters', 'points', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-goals stat-holder skaters">
                </div>
                <div class="stat-assists stat-holder skaters">
                </div>
            </div>
            <div class="stats-leaders defense">
                <h3>Defensemen</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="points" data-list="defense" class="defense option active">Points</a>
                    <a href="javascript:void(0);" data-type="goals" data-list="defense" class="defense option" data-load="true">Goals</a>
                    <a href="javascript:void(0);" data-type="assists" data-list="defense" class="defense option" data-load="true">Assists</a>
                </div>
                <div class="activity-content defense"><span class="loader"></span></div>
                <div class="stat-points stat-holder defense">
                    <?= renderStatHolder('defense', 'points', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-goals stat-holder defense">
                </div>
                <div class="stat-assists stat-holder defense">
                </div>
            </div>
            <div class="stats-leaders goalies">
                <h3>Goalies</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="svp" data-list="goalies" class="goalies option active">Save %</a>
                    <a href="javascript:void(0);" data-type="gaa" data-list="goalies" class="goalies option" data-load="true">GAA</a>
                </div>
                <div class="activity-content goalies"><span class="loader"></span></div>
                <div class="stat-svp stat-holder goalies">
                    <?= renderStatHolder('goalies', 'savePctg', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-gaa stat-holder goalies">
                </div>
            </div>
            <div class="stats-leaders rookie">
                <h3>Rookies</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="points" data-list="rookies" class="rookies option active">Points</a>
                    <a href="javascript:void(0);" data-type="goals" data-list="rookies" class="rookies option" data-load="true">Goals</a>
                    <a href="javascript:void(0);" data-type="assists" data-list="rookies" class="rookies option" data-load="true">Assists</a>
                </div>
                <div class="activity-content rookies"><span class="loader"></span></div>
                <div class="stat-points stat-holder rookies">
                    <?= renderStatHolder('rookies', 'points', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-goals stat-holder rookies">
                </div>
                <div class="stat-assists stat-holder rookies">
                </div>
            </div>
            <?php if (!$selectedPlayoffs) { ?>
            <div class="stats-leaders">
                <h3>Three Stars of the Week</h3>
                <div class="three-stars">
                <?= getThreeStars($selectedSeason); ?>
                </div>
            </div>
            <?php } ?>
        </div><!-- END .section-stats -->
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>