<?php
function curlInit($ApiUrl){
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.snoop.com/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $ApiUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $results = curl_exec($ch);
    curl_close($ch);
    return $results;
}

function fetchData($apiUrl, $cacheFile, $cacheLifetime) {
    // Fix path to be absolute if it's relative
    if (!preg_match('/^[a-zA-Z]:\\\\|^\//', $cacheFile)) {
        $cacheFile = dirname(__DIR__, 2) . '/' . $cacheFile;
    }
    
    // Make sure cache directory exists
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    
    if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
        return json_decode(file_get_contents($cacheFile));
    } else {
        return fetchAndCacheData($apiUrl, $cacheFile);
    }
}

function fetchAndCacheData($apiUrl, $cacheFile) {
    try {
        $apiResponse = curlInit($apiUrl);
        if ($apiResponse === false || empty($apiResponse)) {
            // If API request fails, try to use the existing cache file if available
            if (file_exists($cacheFile)) {
                error_log("API request to $apiUrl failed, using cached data");
                return json_decode(file_get_contents($cacheFile));
            }
            throw new Exception("Error: Unable to fetch data from $apiUrl");
        }
        
        // Make sure cache directory exists
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        
        file_put_contents($cacheFile, $apiResponse);
        return json_decode($apiResponse);
    } catch (Exception $e) {
        // Log the error but try to use cached data if possible
        error_log($e->getMessage());
        if (file_exists($cacheFile)) {
            return json_decode(file_get_contents($cacheFile));
        }
        // Return an empty object rather than throwing an exception
        return json_decode('{"error":"Unable to fetch data"}');
    }
}

function getLatestSeasons() {
    $ApiUrl = 'https://api.nhle.com/stats/rest/en/season?sort=%5B%7B%22property%22:%22id%22,%22direction%22:%22DESC%22%7D%5D&limit=3';    $curl = curlInit($ApiUrl);
    $activeSeasons = json_decode($curl);
    return $activeSeasons;
}

/**
 * Fetch popular posts from any Reddit subreddit
 * @param string $subreddit The subreddit name without r/ prefix (default: 'hockey')
 * @param string $sort The sort method for posts (default: 'hot', options: 'hot', 'new', 'top', 'rising')
 * @param int $limit Number of posts to fetch (default: 10)
 * @param string $timeframe Time frame for 'top' sorting (default: 'day', options: 'hour', 'day', 'week', 'month', 'year', 'all')
 * @return object|false Reddit data object or false on error
 */
function fetchRedditPosts($subreddit = 'hockey', $sort = 'hot', $limit = 10, $timeframe = 'day') {
    // Reddit API credentials
    $clientId = 'Nl-aGFU_uePl7xyP1Md3kg';
    $clientSecret = 'p8HIJJRN3BngeaWHpaTb625DNHqvrw';
    $userAgent = 'nhlplay/1.0 by bobemil';
    
    // Get access token from Reddit
    $tokenUrl = 'https://www.reddit.com/api/v1/access_token';
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        error_log("Reddit API authentication failed: $response");
        return false;
    }
    
    $authData = json_decode($response);
    curl_close($ch);
    
    if (!isset($authData->access_token)) {
        error_log("Reddit API authentication failed: Access token not found");
        return false;
    }
    
    // Validate input parameters
    $validSorts = ['hot', 'new', 'top', 'rising'];
    $sort = in_array($sort, $validSorts) ? $sort : 'hot';
    
    $validTimeframes = ['hour', 'day', 'week', 'month', 'year', 'all'];
    $timeframe = in_array($timeframe, $validTimeframes) ? $timeframe : 'day';
    
    // Build API URL - only fetch post metadata, not images
    $apiUrl = "https://oauth.reddit.com/r/$subreddit/$sort?limit=$limit&raw_json=1&sr_detail=1";
    if ($sort === 'top') {
        $apiUrl .= "&t=$timeframe";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $authData->access_token]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $postsResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        error_log("Reddit API request failed for r/$subreddit: $postsResponse");
        return false;
    }
    return json_decode($postsResponse);
}

/**
 * Legacy function for backward compatibility
 * @param int $limit Number of posts to fetch
 * @return object|false Reddit data object or false on error
 */
function fetchRedditHockeyPosts($limit = 10) {
    return fetchRedditPosts('hockey', 'hot', $limit);
}

/**
 * Fetch comments from a specific Reddit post
 * @param string $postId Reddit post ID
 * @param string $subreddit Subreddit name
 * @param int $limit Number of comments to fetch
 * @param string $sort Comment sort order (top, new, best)
 * @return array|false Array of formatted comments or false on error
 */
function fetchRedditComments($postId, $subreddit = 'hockey', $limit = 20, $sort = 'new') {
    // Reddit API credentials
    $clientId = 'Nl-aGFU_uePl7xyP1Md3kg';
    $clientSecret = 'p8HIJJRN3BngeaWHpaTb625DNHqvrw';
    $userAgent = 'nhlplay/1.0 by bobemil';
    
    // Get access token from Reddit
    $tokenUrl = 'https://www.reddit.com/api/v1/access_token';
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        error_log("Reddit API authentication failed: $response");
        return false;
    }
    
    $authData = json_decode($response);
    curl_close($ch);
    
    if (!isset($authData->access_token)) {
        error_log("Reddit API authentication failed: Access token not found");
        return false;
    }
    
    // Build API URL for comments
    $apiUrl = "https://oauth.reddit.com/r/$subreddit/comments/$postId?limit=$limit&sort=$sort&raw_json=1&depth=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $authData->access_token]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $commentsResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Reddit API request failed for comments in r/$subreddit, post $postId: $commentsResponse");
        return false;
    }
    
    $commentsData = json_decode($commentsResponse);
    
    // Format the comments for display
    $formattedComments = [];
    
    if ($commentsData && isset($commentsData[1]) && isset($commentsData[1]->data) && isset($commentsData[1]->data->children)) {
        foreach ($commentsData[1]->data->children as $comment) {
            if (!isset($comment->data) || $comment->kind === 'more') continue;
            
            $commentData = $comment->data;
            
            // Skip AutoModerator comments if you want
            if ($commentData->author === 'AutoModerator') continue;
            
            // Format the comment
            $formattedComment = [
                'author' => $commentData->author,
                'body' => $commentData->body_html, // HTML formatted comment
                'body_text' => $commentData->body, // Plain text comment
                'score' => $commentData->score,
                'created_utc' => $commentData->created_utc
            ];
            
            $formattedComments[] = $formattedComment;
            
            if (count($formattedComments) >= $limit) break;
        }
    }
    
    return $formattedComments;
}

/**
 * Gets game data from NHL API for a specific game ID
 * @param string $gameId Game ID
 * @return object Game data object
 */
function getNHLGameData($gameId) {
    $ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/' . $gameId . '/landing';
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}