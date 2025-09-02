<?php
// filepath: f:\wamp64\www\nhl\ajax\youtube-videos.php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/ajax.php';

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    exit('Direct access not allowed');
}

// Get parameters with defaults
$maxResults = isset($_GET['maxResults']) ? intval($_GET['maxResults']) : 12;
$seasonBreak = isset($_GET['seasonBreak']) ? ($_GET['seasonBreak'] === 'true') : false;

try {
    // Define channel IDs based on season break status
    if (!$seasonBreak) {
        $channelIds = [
            'UCA3Lillszhzs_Mv0Da0EHlg',  // Jens95
            'UCqFMzb-4AUf6WAIbl132QKA',  // NHL
            'UCRsrhXtzXmzYX_fiyNHHxbw'   // NHL Network
        ];
    } else {
        $channelIds = [
            'UCqFMzb-4AUf6WAIbl132QKA',  // NHL
            'UCRsrhXtzXmzYX_fiyNHHxbw',  // NHL Network
            'UC_AFyA9FqrZ57bb9QRH77wg'   // The Hockey Guy
        ];
    }

    // Implement caching similar to renderMultiChannelVideos
    $cacheDir = dirname(__DIR__) . '/cache';
    $cacheKey = md5(implode('-', $channelIds) . $maxResults);
    $cacheFile = $cacheDir . "/youtube-multi-{$cacheKey}.json";
    $cacheLifetime = 3600; // 1 hour
    
    $videos = null;
    
    // Check if cache exists and is valid
    if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
        $videos = json_decode(file_get_contents($cacheFile), true);
    } else {
        // Fetch fresh data
        $videos = fetchMultiChannelVideos($channelIds, $maxResults);
        if ($videos !== false) {
            // Save to cache
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            file_put_contents($cacheFile, json_encode($videos));
        }
    }
    
    if ($videos !== false && !empty($videos)) {
        send_success(['videos' => $videos], 200);
    } else {
        send_error('No videos available at the moment', 404);
    }
    
} catch (Exception $e) {
    error_log("YouTube Videos AJAX Error: " . $e->getMessage());
    send_error('Error loading YouTube videos', 500);
}
?>
