<?php
include_once '../path.php';
include_once '../includes/functions.php';

// Get player ID from the request
$playerID = $_POST['player'] ?? null;

if (!$playerID) {
    echo json_encode(['error' => 'No player ID provided']);
    exit;
}

// Determine player type
$isSkater = isset($_POST['isSkater']) ? $_POST['isSkater'] === 'true' : false;

if ($isSkater) {
    // Calculate advanced stats only for skaters
    $playerStats = getPlayerSeasonStats($playerID, $season, 'skater');
    $advancedStats = calculateAdvancedStats($playerStats);
    
    echo json_encode([
        'success' => true,
        'advancedStats' => [
            'formattedSAT' => $advancedStats['formattedSAT'] ?? 'N/A',
            'formattedUSAT' => $advancedStats['formattedUSAT'] ?? 'N/A',
            'evenStrengthGoalDiff' => $advancedStats['evenStrengthGoalDiff'] ?? '0'
        ]
    ]);
} else {
    // For goalies, we need different advanced stats
    $playerStats = getPlayerSeasonStats($playerID, $season, 'goalie');
    $advanced = $playerStats['advanced'] ?? null;
    $savesByStrength = $playerStats['savesByStrength'] ?? null;
    
    echo json_encode([
        'success' => true,
        'advancedStats' => [
            'qualityStartsPct' => isset($advanced->qualityStartsPct) ? number_format((float)$advanced->qualityStartsPct * 100, 2, '.', '') : 'N/A',
            'shotsAgainstPer60' => isset($advanced->shotsAgainstPer60) ? number_format((float)$advanced->shotsAgainstPer60, 2, '.', '') : 'N/A',
            'completeGamePct' => isset($advanced->completeGamePct) ? number_format((float)$advanced->completeGamePct * 100, 2, '.', '') : 'N/A',
            'evSavePct' => isset($savesByStrength->evSavePct) ? number_format((float)$savesByStrength->evSavePct * 100, 2, '.', '') : 'N/A',
            'ppSavePct' => isset($savesByStrength->ppSavePct) ? number_format((float)$savesByStrength->ppSavePct * 100, 2, '.', '') : 'N/A',
            'shSavePct' => isset($savesByStrength->shSavePct) ? number_format((float)$savesByStrength->shSavePct * 100, 2, '.', '') : 'N/A'
        ]
    ]);
}
?>