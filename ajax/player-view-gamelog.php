<?php
include_once '../path.php';
include_once '../includes/functions.php';
$playerID = $_POST['player'];
$seasonSelection = $_POST['season-selection'];
$seasonType = $_POST['season-type'];
$playerType = $_POST['player-type'];

// Use the new NHL API utility
$ApiUrl = NHLApi::playerGameLog($playerID, $seasonSelection, $seasonType);
$curl = curlInit($ApiUrl);
$playerGameLog = json_decode($curl);

if ($seasonType == 3) {
    $seasonType = 'Playoffs';
} else {
    $seasonType = 'Regular Season';
}

?>
<h3>Game Log - <?= substr($playerGameLog->seasonId, 0, 4) . '/' . substr($playerGameLog->seasonId, 4) ?> - <?= $seasonType ?></h3>
<div class="player-game-log-list">
<?php foreach ($playerGameLog->gameLog as $game) {
    $gameDate = date('F j, Y', strtotime($game->gameDate));
    $playerTeam = $game->teamAbbrev;
    $oppTeam = $game->opponentAbbrev;

    if ($playerType == 'G') {
        if (isset($game->decision)) {
            $decision = $game->decision;
        } else {
            $decision = null;
        }
        $savePctg = number_format((float)$game->savePctg, 3, '.', '');
        $shutouts = $game->shutouts;
        if ($shutouts == 1) {
            $shutouts = 'Yes';
        } else {
            $shutouts = 'No';
        }

        echo "<div class='game-log-entry'>";
        echo "<p><strong>{$gameDate}</strong></p>";
        echo "<p>Team: {$playerTeam}</p>";
        echo "<p>Against: {$oppTeam}</p>";
        if (isset($decision)) {
            echo "<p>Decision: {$decision}</p>";
        } else {
            echo "<p>Decision: N/A</p>";
        }
        echo "<p>Outcome: {$decision}</p>";
        echo "<p>Save Percentage: {$savePctg}</p>";
        echo "<p>Shutout: {$shutouts}</p>";
        echo "</div>";
    } else {
        $goals = $game->goals;
        $assists = $game->assists;
        $points = $game->points;
        $plusMinus = $game->plusMinus;
        $penaltyMinutes = $game->pim;
    
        echo "<div class='game-log-entry'>";
        echo "<p><strong>{$gameDate}</strong></p>";
        echo "<p>Team: {$playerTeam}</p>";
        echo "<p>Against: {$oppTeam}</p>";
        echo "<p>Goals: {$goals}</p>";
        echo "<p>Assists: {$assists}</p>";
        echo "<p>Points: {$points}</p>";
        echo "<p>Plus/Minus: {$plusMinus}</p>";
        echo "<p>Penalty Minutes: {$penaltyMinutes}</p>";
        echo "</div>";
    }
}
?>
</div>