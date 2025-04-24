<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once '../includes/functions/stats-functions.php';
require_once "../includes/MobileDetect.php";
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

$season = isset($_POST['season']) ? $_POST['season'] : (isset($_GET['season']) ? $_GET['season'] : (isset($season) ? $season : '20242025'));
$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : 'skater');
$gameType = isset($_POST['gameType']) ? intval($_POST['gameType']) : (isset($_GET['gameType']) ? intval($_GET['gameType']) : 2);

$standing = getSkaterLeadersTable($season, $type, $gameType);

function renderSkaterLeadersRows($standing) {
    if (!$standing || !isset($standing->data)) return '<tr><td colspan="11">No data</td></tr>';
    $rows = '';
    foreach ($standing->data as $player) {
        $rows .= '<tr class="team">'
            .'<td class="name"><a id="player-link" href="#" data-link="'.htmlspecialchars($player->playerId).'">'.htmlspecialchars($player->skaterFullName).'</a></td>'
            .'<td class="position">'.htmlspecialchars($player->positionCode).'</td>'
            .'<td class="gp">'.htmlspecialchars($player->gamesPlayed).'</td>'
            .'<td class="goals">'.htmlspecialchars($player->goals).'</td>'
            .'<td class="assists">'.htmlspecialchars($player->assists).'</td>'
            .'<td class="points">'.htmlspecialchars($player->points).'</td>'
            .'<td class="points-per-game">'.number_format((float)$player->pointsPerGame, 2, '.', '').'</td>'
            .'<td class="ev-points">'.htmlspecialchars($player->evPoints).'</td>'
            .'<td class="plus-minus">'.htmlspecialchars($player->plusMinus).'</td>'
            .'<td class="pim">'.htmlspecialchars($player->penaltyMinutes).'</td>'
            .'<td class="fow-percentage">'.number_format((float)$player->faceoffWinPct * 100, 0, '.', '').'%</td>'
            .'</tr>';
    }
    return $rows;
}

if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // Return complete table for AJAX
    ?>
    <table id="playerStatsTable" class="hover sticky-header">
        <thead>
            <td>NAME</td>
            <td>POS</td>
            <td>GP</td>
            <td>G</td>
            <td>A</td>
            <td>P</td>
            <td>PPG</td>
            <td>EV P</td>
            <td>+/-</td>
            <td>PIM</td>
            <td>FOW%</td>
        </thead>
        <tbody>
            <?php echo renderSkaterLeadersRows($standing); ?>
        </tbody>
    </table>
    <?php
    exit;
}
?>
<main>
    <div class="wrap">
        <div class="component-header">
            <div class="menu-teams-stat-leaders custom-select">
                <input class="dropdown" type="checkbox" id="dropdown4" name="dropdown4"/>
                <label class="for-dropdown" for="dropdown4">Season <i class="bi bi-arrow-down-short"></i></label>
                <div class="section-dropdown" id="team-player-season-selection"> 
                    <div class="fader-top"></div>
                    <div class="container">
                    <?php include('../includes/seasonSelection.php'); ?>
                    </div>
                    <div class="fader-bottom"></div>
                </div>
            </div>
            <div class="menu-teams-stat-leaders custom-select">
                <input class="dropdown" type="checkbox" id="dropdown3" name="dropdown3"/>
                <label class="for-dropdown" for="dropdown3">Team <i class="bi bi-arrow-down-short"></i></label>
                <div class="section-dropdown" id="team-player-stats-selection"> 
                    <div class="fader-top"></div>
                    <div class="container">
                    <?php include('../includes/teamSelection.php'); ?>
                    </div>
                    <div class="fader-bottom"></div>
                </div>
            </div>
            <div class="btn-group">
                <i class="bi bi-filter icon"></i>
                <a href="#" class="btn sm active" data-type="2">Regular Season</a>
                <a href="#" class="btn sm" data-type="3">Playoffs</a>
            </div>
            <div class="btn-group">
                <i class="bi bi-filter icon"></i>
                <a href="#" class="btn sm active" data-type="forwards" data-api="skater">Forwards</a>
                <a href="#" class="btn sm" data-type="defense" data-api="skater">Defense</a>
                <a href="#" class="btn sm" data-type="goalies" data-api="goalie">Goalies</a>
            </div>
        </div>
        <table id="playerStatsTable" class="hover sticky-header">
            <thead>
                <td>NAME</td>
                <td>POS</td>
                <td>GP</td>
                <td>G</td>
                <td>A</td>
                <td>P</td>
                <td>PPG</td>
                <td>EV P</td>
                <td>+/-</td>
                <td>PIM</td>
                <td>FOW%</td>
            </thead>
            <tbody>
                <?php echo renderSkaterLeadersRows($standing); ?>
            </tbody>
        </table>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>