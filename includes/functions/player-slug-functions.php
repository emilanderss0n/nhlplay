<?php
/**
 * Player utility functions for SEO-friendly URLs
 */

/**
 * Extract player ID from player slug
 * Player slugs are in format: firstname-lastname-playerid
 * @param string $slug Player slug like "connor-mcdavid-8478402"
 * @return string|null Player ID or null if invalid slug
 */
function extractPlayerIdFromSlug($slug) {
    if (empty($slug)) {
        return null;
    }
    
    // Player slug format: firstname-lastname-playerid
    // Extract the last segment after the last dash
    $parts = explode('-', $slug);
    if (count($parts) < 2) {
        return null;
    }
    
    $lastPart = end($parts);
    // Check if it's a valid player ID (numeric)
    if (is_numeric($lastPart)) {
        return $lastPart;
    }
    
    return null;
}

/**
 * Create a player slug from player data
 * @param object $player Player object with firstName, lastName, and playerId
 * @return string Player slug
 */
function createPlayerSlug($player) {
    if (!isset($player->firstName->default) || !isset($player->lastName->default) || !isset($player->playerId)) {
        return null;
    }
    
    $firstName = strtolower($player->firstName->default);
    $lastName = strtolower($player->lastName->default);
    $playerId = $player->playerId;
    
    // Clean names - remove special characters and convert spaces to dashes
    $firstName = preg_replace('/[^a-z0-9\s-]/', '', $firstName);
    $lastName = preg_replace('/[^a-z0-9\s-]/', '', $lastName);
    $firstName = preg_replace('/[\s-]+/', '-', trim($firstName));
    $lastName = preg_replace('/[\s-]+/', '-', trim($lastName));
    
    return $firstName . '-' . $lastName . '-' . $playerId;
}

/**
 * Validate if a player slug matches the actual player data
 * @param string $slug The provided slug
 * @param object $player Player object from API
 * @return bool True if slug is valid for this player
 */
function validatePlayerSlug($slug, $player) {
    $expectedSlug = createPlayerSlug($player);
    return $slug === $expectedSlug;
}

/**
 * Get canonical player URL
 * @param object $player Player object with playerSlug or player data
 * @return string Canonical URL for the player
 */
function getPlayerCanonicalUrl($player) {
    $slug = isset($player->playerSlug) ? $player->playerSlug : createPlayerSlug($player);
    return BASE_URL . '/player/' . $slug;
}
?>
