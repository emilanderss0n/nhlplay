<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once '../includes/functions/stats-functions.php';
require_once "../includes/MobileDetect.php";
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

// Check for season parameter from POST request
$selectedSeason = $season; // Default to global setting
if(isset($_POST['season'])) {
    $selectedSeason = $_POST['season'];
}

$standing = getSkaterLeadersTable($selectedSeason);

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
            .'<td class="shots">'.htmlspecialchars($player->shots).'</td>'
            .'<td class="shot-percentage">'.number_format((float)$player->shootingPct * 100, 0, '.', '').'%</td>'
            .'<td class="fow-percentage">'.number_format((float)$player->faceoffWinPct * 100, 0, '.', '').'%</td>'
            .'</tr>';
    }
    return $rows;
}
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
            <td>S</td>
            <td>S%</td>
            <td>FOW%</td>
        </thead>
        <tbody>
            <?php echo renderSkaterLeadersRows($standing); ?>
        </tbody>
    </table>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!$detect->isMobile()) { ?>
            let dt = new jsdatatables.JSDataTable('#playerStatsTable', {
                paging: true,
                searchable: true,
            });
            <?php } else { ?>
            let dt = '';
            <?php } ?>
        });
    </script>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>