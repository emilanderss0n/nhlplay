<?php
/**
 * Share link utility functions
 */

/**
 * Generate a shareable URL for a player
 * @param mixed $player Player object or player ID
 * @return string Shareable URL
 */
function generatePlayerShareUrl($player) {
    if (is_numeric($player)) {
        // If just an ID, fetch the player data to get the slug
        try {
            include_once __DIR__ . '/controllers/player.php';
            $playerData = player_fetch_landing($player);
            if ($playerData && isset($playerData->playerSlug)) {
                return BASE_URL . '/player/' . $playerData->playerSlug;
            }
        } catch (Exception $e) {
            // Fallback to ID-based URL
            return BASE_URL . '/player/' . $player;
        }
    } elseif (is_object($player)) {
        // Player object
        $slug = isset($player->playerSlug) ? $player->playerSlug : createPlayerSlug($player);
        return BASE_URL . '/player/' . $slug;
    }
    
    return BASE_URL . '/';
}

/**
 * Add share functionality to player data for JavaScript
 * @param object $player Player object
 * @return array Player data with share information
 */
function addPlayerShareData($player) {
    $shareUrl = generatePlayerShareUrl($player);
    $playerName = (isset($player->firstName) && isset($player->lastName)) 
        ? $player->firstName->default . ' ' . $player->lastName->default 
        : 'NHL Player';
    
    return [
        'url' => $shareUrl,
        'title' => $playerName . ' - NHL Stats',
        'text' => 'Check out ' . $playerName . "'s NHL stats and career highlights!"
    ];
}
?>
