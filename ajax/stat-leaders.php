<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

?>
<main>
    <div class="wrap">
        <h2 class="header-text-gradient">Three Stars of the week</h2>
        <div class="three-stars">
        <?= getThreeStars($season); ?>
        </div>
        <div class="section-stats">
            <div class="stats-leaders skaters">
                <h3>Forwards</h3>
                <div class="stat-select">
                    <a href="#" data-type="points" data-list="skaters" class="skaters option active">Points</a>
                    <a href="#" data-type="goals" data-list="skaters" class="skaters option" data-load="true">Goals</a>
                    <a href="#" data-type="assists" data-list="skaters" class="skaters option" data-load="true">Assists</a>
                </div>
                <div class="activity-content skaters"><span class="loader"></span></div>
                <div class="stat-points stat-holder skaters">
                    <?= renderStatHolder('skaters', 'points', $season); ?>
                </div>
                <div class="stat-goals stat-holder skaters">
                </div>
                <div class="stat-assists stat-holder skaters">
                </div>
            </div>
            <div class="stats-leaders defense">
                <h3>Defensemen</h3>
                <div class="stat-select">
                    <a href="#" data-type="points" data-list="defense" class="defense option active">Points</a>
                    <a href="#" data-type="goals" data-list="defense" class="defense option" data-load="true">Goals</a>
                    <a href="#" data-type="assists" data-list="defense" class="defense option" data-load="true">Assists</a>
                </div>
                <div class="activity-content defense"><span class="loader"></span></div>
                <div class="stat-points stat-holder defense">
                    <?= renderStatHolder('defense', 'points', $season); ?>
                </div>
                <div class="stat-goals stat-holder defense">
                </div>
                <div class="stat-assists stat-holder defense">
                </div>
            </div>
            <div class="stats-leaders goalies">
                <h3>Goalies</h3>
                <div class="stat-select">
                    <a href="#" data-type="svp" data-list="goalies" class="goalies option active">Save %</a>
                    <a href="#" data-type="gaa" data-list="goalies" class="goalies option" data-load="true">GAA</a>
                </div>
                <div class="activity-content goalies"><span class="loader"></span></div>
                <div class="stat-svp stat-holder goalies">
                    <?= renderStatHolder('goalies', 'savePctg', $season); ?>
                </div>
                <div class="stat-gaa stat-holder goalies">
                </div>
            </div>
            <div class="stats-leaders rookie">
                <h3>Rookies</h3>
                <div class="stat-select">
                    <a href="#" data-type="points" data-list="rookies" class="rookies option active">Points</a>
                    <a href="#" data-type="goals" data-list="rookies" class="rookies option" data-load="true">Goals</a>
                    <a href="#" data-type="assists" data-list="rookies" class="rookies option" data-load="true">Assists</a>
                </div>
                <div class="activity-content rookies"><span class="loader"></span></div>
                <div class="stat-points stat-holder rookies">
                    <?= renderStatHolder('rookies', 'points', $season); ?>
                </div>
                <div class="stat-goals stat-holder rookies">
                </div>
                <div class="stat-assists stat-holder rookies">
                </div>
            </div>
        </div><!-- END .section-stats -->
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>