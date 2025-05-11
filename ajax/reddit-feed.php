<?php
// filepath: f:\wamp64\www\nhl\ajax\reddit-feed.php
include_once '../path.php';
include_once '../includes/functions.php';

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    exit('Direct access not allowed');
}

// Get requested subreddit (default to 'hockey')
$subreddit = isset($_GET['subreddit']) ? $_GET['subreddit'] : 'hockey';

// Get requested post limit (default to 12)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;

// Sanitize inputs
$subreddit = preg_replace('/[^a-zA-Z0-9_]/', '', $subreddit);
$limit = min(max($limit, 1), 50); // Ensure limit is between 1 and 50

// Create cache file path
$cacheFile = '../cache/reddit-' . $subreddit . '-frontpage.json';
$cacheLifetime = 1800; // 30 minutes

// Use the fetchData function to get cached or fresh data
if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
    $redditData = json_decode(file_get_contents($cacheFile));
} else {
    // Use our dedicated function from api-functions.php
    $redditData = fetchRedditPosts($subreddit, 'hot', 20); // Fetch 20 posts to account for stickied posts
    if ($redditData !== false) {
        file_put_contents($cacheFile, json_encode($redditData));
    } else {
        // If API fails and cache exists but is expired, use old cache as fallback
        if (file_exists($cacheFile)) {
            $redditData = json_decode(file_get_contents($cacheFile));
        } else {
            // No data available
            echo json_encode(['error' => 'No posts available from r/' . $subreddit]);
            exit;
        }
    }
}

// Format and return the posts
$formattedPosts = [];
$count = 0;

if ($redditData && isset($redditData->data) && isset($redditData->data->children)) {
    foreach ($redditData->data->children as $post) {
        if ($count >= $limit) break;
        
        $postData = $post->data;
        if (isset($postData->stickied) && $postData->stickied) continue; // Skip stickied posts
        
        // Format data for display
        $formattedPost = [
            'title' => $postData->title,
            'author' => $postData->author,
            'permalink' => $postData->permalink,
            'score' => $postData->score,
            'comments' => $postData->num_comments,
            'created_utc' => $postData->created_utc
        ];
        
        $formattedPosts[] = $formattedPost;
        $count++;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($formattedPosts);
