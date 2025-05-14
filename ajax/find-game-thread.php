<?php
// filepath: f:\wamp64\www\nhl\ajax\find-game-thread.php
include_once '../path.php';
include_once '../includes/functions.php';

// Get game ID or team names from request
$gameId = isset($_GET['gameId']) ? trim($_GET['gameId']) : '';
$homeTeam = isset($_GET['homeTeam']) ? trim($_GET['homeTeam']) : '';
$awayTeam = isset($_GET['awayTeam']) ? trim($_GET['awayTeam']) : '';

// If we have a gameId but no team names, get team names from the API
if (!empty($gameId) && (empty($homeTeam) || empty($awayTeam))) {
    // Get game details from NHL API
    $gameData = getNHLGameData($gameId);
    
    if ($gameData) {
        $homeTeam = $gameData->homeTeam->name->default ?? $gameData->homeTeam->commonName->default;
        $awayTeam = $gameData->awayTeam->name->default ?? $gameData->awayTeam->commonName->default;
    }
}

if (empty($homeTeam) || empty($awayTeam)) {
    echo json_encode(['error' => 'Team names could not be determined']);
    exit;
}

// Cache file path
$cacheKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $homeTeam . $awayTeam));
$cacheFile = "../cache/reddit-gamethread-{$cacheKey}.json";
$cacheLifetime = 300; // 5 minutes

// Use cached data if available and fresh
if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
    $result = json_decode(file_get_contents($cacheFile), true);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Function to find game thread posts
function findGameThreadPosts($homeTeam, $awayTeam) {
    // First, fetch recent posts from r/hockey
    $posts = fetchRedditPosts('hockey', 'new', 50); // Get more posts to increase chances of finding the thread
    
    if (!$posts || empty($posts->data->children)) {
        return ['error' => 'Could not fetch posts from r/hockey'];
    }

    // Search terms - both team names and common thread indicators
    $searchTerms = [
        strtolower($homeTeam),
        strtolower($awayTeam),
        'game thread',
        'gdt',
    ];
    
    // Array to store matching posts
    $matchingPosts = [];
    $exactMatches = [];
    
    // Process each post
    foreach ($posts->data->children as $post) {
        $postData = $post->data;
        $title = strtolower($postData->title);
        
        // Check if title contains both team names and thread indicators
        $containsHomeTeam = strpos($title, strtolower($homeTeam)) !== false;
        $containsAwayTeam = strpos($title, strtolower($awayTeam)) !== false;
        $containsThreadIndicator = strpos($title, 'game thread') !== false || 
                                 strpos($title, 'gdt') !== false;
        
        // Score the match based on how many terms are matched
        $score = 0;
        foreach ($searchTerms as $term) {
            if (strpos($title, $term) !== false) {
                $score++;
            }
        }
        
        // Add post to matching posts if it contains both team names
        if ($containsHomeTeam && $containsAwayTeam) {
            // If it also has thread indicators, consider it an exact match
            if ($containsThreadIndicator) {
                $exactMatches[] = [
                    'id' => $postData->id,
                    'title' => $postData->title,
                    'url' => $postData->permalink,
                    'score' => $score,
                    'created_utc' => $postData->created_utc,
                    'num_comments' => $postData->num_comments
                ];
            } else {
                $matchingPosts[] = [
                    'id' => $postData->id,
                    'title' => $postData->title,
                    'url' => $postData->permalink,
                    'score' => $score,
                    'created_utc' => $postData->created_utc,
                    'num_comments' => $postData->num_comments
                ];
            }
        }
    }
    
    // Sort exact matches by recency (might be multiple GDTs - pre-game, post-game)
    if (!empty($exactMatches)) {
        usort($exactMatches, function($a, $b) {
            return $b['created_utc'] - $a['created_utc']; // Most recent first
        });
        return $exactMatches[0]; // Return the most recent exact match
    }
    
    // If no exact matches, try the matching posts
    if (!empty($matchingPosts)) {
        usort($matchingPosts, function($a, $b) {
            return $b['score'] - $a['score']; // Highest score first
        });
        return $matchingPosts[0]; // Return the highest-scoring match
    }
    
    return ['error' => 'No game thread found for ' . $awayTeam . ' at ' . $homeTeam];
}

// Try to find a matching game thread
$result = findGameThreadPosts($homeTeam, $awayTeam);

// Add a flag to indicate if the search was successful
if (!isset($result['error'])) {
    $result['found'] = true;
} else {
    $result['found'] = false;
}

// Cache the result
file_put_contents($cacheFile, json_encode($result));

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
