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
    // Use the new NHL API utility
    $ApiUrl = NHLApi::seasonsStats(3, 'DESC');
    $curl = curlInit($ApiUrl);
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
    // Use the new NHL API utility
    $ApiUrl = NHLApi::gameCenterLanding($gameId);
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

/**
 * Fetch YouTube videos from a channel
 * @param string $channelId YouTube channel ID
 * @param int $maxResults Maximum number of videos to fetch (default: 10)
 * @param string $apiKey YouTube API key (optional, uses default if not provided)
 * @return array|false Array of video data or false on error
 */
function fetchYouTubeVideos($channelId, $maxResults = 10, $apiKey = null) {
    // Use default API key if none provided
    if ($apiKey === null) {
        $apiKey = "AIzaSyDUMIbtdWIDoZxM7Z-vAaYeELW-i3Ayhxc";
    }
    
    try {
        // Use uploads playlist but filter out Shorts by getting video details
        // Get Uploads playlist ID
        $playlistUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=$channelId&key=$apiKey";
        $playlistResponse = curlInit($playlistUrl);
        $playlistData = json_decode($playlistResponse, true);
        
        if (!$playlistData || !isset($playlistData['items'][0]['contentDetails']['relatedPlaylists']['uploads'])) {
            error_log("YouTube API Error: Unable to fetch playlist data for channel $channelId. Response: " . $playlistResponse);
            return false;
        }
        
        $uploadsPlaylist = $playlistData['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
        
        // Fetch more videos than needed to account for filtering
        $fetchCount = $maxResults * 2; // Fetch double to account for filtered Shorts
        $videosUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=$uploadsPlaylist&maxResults=$fetchCount&key=$apiKey";
        $videosResponse = curlInit($videosUrl);
        $videosData = json_decode($videosResponse, true);
        
        if (!$videosData || !isset($videosData['items'])) {
            error_log("YouTube API Error: Unable to fetch videos for playlist $uploadsPlaylist. Response: " . $videosResponse);
            return false;
        }

        // example response url: https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=UUzRl6BRpz1cQfqK36wXsYhA&maxResults=20&key=AIzaSyDUMIbtdWIDoZxM7Z-vAaYeELW-i3Ayhxc
        
        // Get video IDs to check durations
        $videoIds = [];
        foreach ($videosData['items'] as $item) {
            if (isset($item['snippet']['resourceId']['videoId'])) {
                $videoIds[] = $item['snippet']['resourceId']['videoId'];
            }
        }
        
        if (empty($videoIds)) {
            return [];
        }
        
        // Get video details including duration
        $idsString = implode(',', $videoIds);
        $detailsUrl = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=$idsString&key=$apiKey";
        $detailsResponse = curlInit($detailsUrl);
        $detailsData = json_decode($detailsResponse, true);
        
        // Create map of video ID to duration in seconds
        $videoDurations = [];
        if ($detailsData && isset($detailsData['items'])) {
            foreach ($detailsData['items'] as $video) {
                $duration = $video['contentDetails']['duration'] ?? '';
                $seconds = parseDuration($duration);
                $videoDurations[$video['id']] = $seconds;
            }
        }
        
        // Filter out Shorts (videos <= 60 seconds) and limit to requested count
        $filteredItems = [];
        foreach ($videosData['items'] as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'] ?? '';
            $duration = $videoDurations[$videoId] ?? 0;
            
            // Skip Shorts (60 seconds or less)
            if ($duration > 60) {
                $filteredItems[] = $item;
                // Stop when we have enough videos
                if (count($filteredItems) >= $maxResults) {
                    break;
                }
            }
        }
        
        return $filteredItems;
        
    } catch (Exception $e) {
        error_log("YouTube API Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Fallback function to fetch from uploads playlist (may include Shorts)
 * @param string $channelId YouTube channel ID
 * @param int $maxResults Maximum number of videos to fetch
 * @param string $apiKey YouTube API key
 * @return array|false Array of video data or false on error
 */
function fetchYouTubeVideosFromUploads($channelId, $maxResults, $apiKey) {
    try {
        // Get Uploads playlist ID
        $playlistUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=$channelId&key=$apiKey";
        $playlistResponse = curlInit($playlistUrl);
        $playlistData = json_decode($playlistResponse, true);
        
        if (!$playlistData || !isset($playlistData['items'][0]['contentDetails']['relatedPlaylists']['uploads'])) {
            error_log("YouTube API Error: Unable to fetch playlist data for channel $channelId. Response: " . $playlistResponse);
            return false;
        }
        
        $uploadsPlaylist = $playlistData['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
        
        // Fetch videos
        $videosUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=$uploadsPlaylist&maxResults=$maxResults&key=$apiKey";
        $videosResponse = curlInit($videosUrl);
        $videosData = json_decode($videosResponse, true);
        
        if (!$videosData || !isset($videosData['items'])) {
            error_log("YouTube API Error: Unable to fetch videos for playlist $uploadsPlaylist. Response: " . $videosResponse);
            return false;
        }
        
        return $videosData['items'];
        
    } catch (Exception $e) {
        error_log("YouTube Uploads API Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Parse ISO 8601 duration (PT1M30S) to seconds
 * @param string $duration ISO 8601 duration string
 * @return int Duration in seconds
 */
function parseDuration($duration) {
    if (empty($duration)) {
        return 0;
    }
    
    // Simple regex parsing for common YouTube durations
    $seconds = 0;
    
    // Match hours, minutes, seconds
    if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches)) {
        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $secs = isset($matches[3]) ? (int)$matches[3] : 0;
        
        $seconds = ($hours * 3600) + ($minutes * 60) + $secs;
    }
    
    return $seconds;
}

/**
 * Render YouTube videos HTML with optional caching
 * @param string $channelId YouTube channel ID
 * @param int $maxResults Maximum number of videos to fetch (default: 10)
 * @param string $containerId HTML container ID (default: 'videos')
 * @param bool $useCache Whether to use caching (default: true)
 * @param int $cacheLifetime Cache lifetime in seconds (default: 3600 = 1 hour)
 * @return void Outputs HTML directly
 */
function renderYouTubeVideos($channelId, $maxResults = 10, $containerId = 'videos', $useCache = true, $cacheLifetime = 3600) {
    $videosData = null;
    
    if ($useCache) {
        // Create cache file path
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cacheFile = $cacheDir . "/youtube-{$channelId}-{$maxResults}.json";
        
        // Check if cache exists and is valid
        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
            $videosData = json_decode(file_get_contents($cacheFile), true);
        } else {
            // Fetch fresh data
            $videos = fetchYouTubeVideos($channelId, $maxResults);
            if ($videos !== false) {
                $videosData = $videos;
                // Save to cache
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                file_put_contents($cacheFile, json_encode($videos));
            }
        }
    } else {
        // Fetch without caching
        $videosData = fetchYouTubeVideos($channelId, $maxResults);
    }
    
    // Output the container and JavaScript
    // Mark container as swiper-enabled (JS will enhance to a Swiper carousel)
    echo '<div id="' . htmlspecialchars($containerId) . '" class="youtube-videos grid grid-400 grid-gap-lg grid-gap-row-lg" data-swiper-enabled="1">';
    
    if ($videosData !== false && !empty($videosData)) {
        // Embed video data as JavaScript variable
        echo '<script>window.youtubeVideosData = ' . json_encode(['items' => $videosData]) . ';</script>';
    } else {
        echo '<div class="alert info">No videos available at the moment</div>';
    }
    
    echo '</div>';
    
    // Include the JavaScript module
    echo '<script type="module">';
    echo 'import { initYouTubeVideos } from "./assets/js/modules/youtube-videos.js";';
    echo 'document.addEventListener("DOMContentLoaded", initYouTubeVideos);';
    echo '</script>';
}

/**
 * Fetch YouTube videos from multiple specific channels
 * @param array $channelIds Array of YouTube channel IDs
 * @param int $maxResults Maximum number of videos to fetch total (default: 12)
 * @param string $apiKey YouTube API key (optional, uses default if not provided)
 * @return array|false Array of video data or false on error
 * @note Gets recent videos from specified channels, sorted by publish date
 */
function fetchMultiChannelVideos($channelIds, $maxResults = 12, $apiKey = null) {
    // Use default API key if none provided
    if ($apiKey === null) {
        $apiKey = "AIzaSyDUMIbtdWIDoZxM7Z-vAaYeELW-i3Ayhxc";
    }
    
    try {
        $allVideos = [];
        $videosPerChannel = ceil($maxResults / count($channelIds)); // Distribute evenly
        
        // Calculate date 7 days ago in RFC 3339 format
        $sevenDaysAgo = date('c', strtotime('-7 days'));
        
        foreach ($channelIds as $channelId) {
            // Get uploads playlist for this channel
            $playlistUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=$channelId&key=$apiKey";
            $playlistResponse = curlInit($playlistUrl);
            $playlistData = json_decode($playlistResponse, true);
            
            if (!$playlistData || !isset($playlistData['items'][0]['contentDetails']['relatedPlaylists']['uploads'])) {
                error_log("YouTube API Error: Unable to fetch playlist data for channel $channelId");
                continue;
            }
            
            $uploadsPlaylist = $playlistData['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
            
            // Fetch recent videos from this channel
            $videosUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=$uploadsPlaylist&maxResults=$videosPerChannel&key=$apiKey";
            $videosResponse = curlInit($videosUrl);
            $videosData = json_decode($videosResponse, true);
            
            if ($videosData && isset($videosData['items'])) {
                foreach ($videosData['items'] as $item) {
                    // Add channel info to each video for sorting
                    $item['channelId'] = $channelId;
                    $allVideos[] = $item;
                }
            }
        }
        
        if (empty($allVideos)) {
            return [];
        }
        
        // Get video IDs to check durations
        $videoIds = [];
        foreach ($allVideos as $item) {
            if (isset($item['snippet']['resourceId']['videoId'])) {
                $videoIds[] = $item['snippet']['resourceId']['videoId'];
            }
        }
        
        // Get video details including duration
        $idsString = implode(',', $videoIds);
        $detailsUrl = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=$idsString&key=$apiKey";
        $detailsResponse = curlInit($detailsUrl);
        $detailsData = json_decode($detailsResponse, true);
        
        // Create map of video ID to duration
        $videoDurations = [];
        if ($detailsData && isset($detailsData['items'])) {
            foreach ($detailsData['items'] as $video) {
                $duration = $video['contentDetails']['duration'] ?? '';
                $seconds = parseDuration($duration);
                $videoDurations[$video['id']] = $seconds;
            }
        }
        
        // Filter out Shorts (videos <= 60 seconds) and sort by publish date
        $filteredVideos = [];
        foreach ($allVideos as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'] ?? '';
            $duration = $videoDurations[$videoId] ?? 0;
            
            // Skip Shorts (60 seconds or less)
            if ($duration > 60) {
                $filteredVideos[] = $item;
            }
        }
        
        // Sort by publish date (newest first)
        usort($filteredVideos, function($a, $b) {
            $dateA = strtotime($a['snippet']['publishedAt']);
            $dateB = strtotime($b['snippet']['publishedAt']);
            return $dateB - $dateA; // Descending order (newest first)
        });
        
        // Limit to requested number of videos
        return array_slice($filteredVideos, 0, $maxResults);
        
    } catch (Exception $e) {
        error_log("Multi-Channel YouTube API Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Render YouTube videos from multiple specific channels with optional caching
 * @param array $channelIds Array of YouTube channel IDs
 * @param int $maxResults Maximum number of videos to fetch total (default: 12)
 * @param string $containerId HTML container ID (default: 'videos')
 * @param bool $useCache Whether to use caching (default: true)
 * @param int $cacheLifetime Cache lifetime in seconds (default: 3600 = 1 hour)
 * @param bool $lazyLoad Whether to set up for lazy loading instead of immediate rendering (default: false)
 * @return void Outputs HTML directly
 */
function renderMultiChannelVideos($channelIds, $maxResults = 12, $containerId = 'videos', $useCache = true, $cacheLifetime = 3600, $lazyLoad = false) {
    // If lazy loading is enabled, just output the container structure
    if ($lazyLoad) {
        echo '<div id="' . htmlspecialchars($containerId) . '" class="youtube-videos grid grid-400 grid-gap-lg grid-gap-row-lg" data-swiper-enabled="1" data-max-results="' . $maxResults . '">';
        echo '<div class="load"><div class="loading-spinner"></div></div>';
        echo '</div>';
        
        // Include the JavaScript module
        echo '<script type="module">';
        echo 'import { initYouTubeVideos } from "./assets/js/modules/youtube-videos.js";';
        echo 'document.addEventListener("DOMContentLoaded", initYouTubeVideos);';
        echo '</script>';
        return;
    }
    
    $videosData = null;
    
    if ($useCache) {
        // Create cache file path
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cacheKey = md5(implode('-', $channelIds) . $maxResults);
        $cacheFile = $cacheDir . "/youtube-multi-{$cacheKey}.json";
        
        // Check if cache exists and is valid
        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
            $videosData = json_decode(file_get_contents($cacheFile), true);
        } else {
            // Fetch fresh data
            $videos = fetchMultiChannelVideos($channelIds, $maxResults);
            if ($videos !== false) {
                $videosData = $videos;
                // Save to cache
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                file_put_contents($cacheFile, json_encode($videos));
            }
        }
    } else {
        // Fetch without caching
        $videosData = fetchMultiChannelVideos($channelIds, $maxResults);
    }
    
    // Output the container and JavaScript
    // Mark container as swiper-enabled (JS will enhance to a Swiper carousel)
    echo '<div id="' . htmlspecialchars($containerId) . '" class="youtube-videos grid grid-400 grid-gap-lg grid-gap-row-lg" data-swiper-enabled="1">';
    
    if ($videosData !== false && !empty($videosData)) {
        // Embed video data as JavaScript variable
        echo '<script>window.youtubeVideosData = ' . json_encode(['items' => $videosData]) . ';</script>';
    } else {
        echo '<div class="alert info">No videos available at the moment</div>';
    }
    
    echo '</div>';
    
    // Include the JavaScript module
    echo '<script type="module">';
    echo 'import { initYouTubeVideos } from "./assets/js/modules/youtube-videos.js";';
    echo 'document.addEventListener("DOMContentLoaded", initYouTubeVideos);';
    echo '</script>';
}

/**
 * Search YouTube for NHL videos broadly (not channel-specific)
 * @param string $query Search query (default: 'NHL hockey')
 * @param int $maxResults Maximum number of videos to fetch (default: 10)
 * @param string $videoDuration Duration filter: 'short', 'medium', 'long' (default: 'medium')
 * @param string $apiKey YouTube API key (optional, uses default if not provided)
 * @return array|false Array of video data or false on error
 * @note Tries to get videos from last 7 days first, falls back to 30 days if needed
 * @note Excludes gaming category and gaming-related content
 */
function searchYouTubeVideos($query = 'NHL hockey', $maxResults = 10, $videoDuration = 'medium', $apiKey = null) {
    // Use default API key if none provided
    if ($apiKey === null) {
        $apiKey = "AIzaSyDUMIbtdWIDoZxM7Z-vAaYeELW-i3Ayhxc";
    }
    
    try {
        // Fetch more videos than needed to account for filtering
        $fetchCount = $maxResults * 3; // Increased multiplier for better results
        
        // Calculate date 7 days ago in RFC 3339 format
        $sevenDaysAgo = date('c', strtotime('-7 days'));
        
        // Build search URL with filters - excluded gaming content
        $searchUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($query . ' -gaming -game -FIFA -NHL24 -NHL25 -EA') . 
                    "&type=video&videoDuration=" . $videoDuration . 
                    "&order=date&publishedAfter=" . urlencode($sevenDaysAgo) . 
                    "&maxResults=$fetchCount&key=$apiKey";
        
        $searchResponse = curlInit($searchUrl);
        $searchData = json_decode($searchResponse, true);
        
        // If no results with 7-day filter, try with 30 days as fallback
        if (!$searchData || !isset($searchData['items']) || empty($searchData['items'])) {
            $thirtyDaysAgo = date('c', strtotime('-30 days'));
            $searchUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($query . ' -gaming -game -FIFA -NHL24 -NHL25 -EA') . 
                        "&type=video&videoDuration=" . $videoDuration . 
                        "&order=relevance&publishedAfter=" . urlencode($thirtyDaysAgo) . 
                        "&maxResults=$fetchCount&key=$apiKey";
            
            $searchResponse = curlInit($searchUrl);
            $searchData = json_decode($searchResponse, true);
        }
        
        if (!$searchData || !isset($searchData['items'])) {
            error_log("YouTube Search API Error: Unable to search for '$query'. Response: " . $searchResponse);
            return false;
        }
        
        // Get video IDs to check durations and get additional details
        $videoIds = [];
        foreach ($searchData['items'] as $item) {
            if (isset($item['id']['videoId'])) {
                $videoIds[] = $item['id']['videoId'];
            }
        }
        
        if (empty($videoIds)) {
            return [];
        }
        
        // Get video details including duration and view count
        $idsString = implode(',', $videoIds);
        $detailsUrl = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails,statistics,snippet&id=$idsString&key=$apiKey";
        $detailsResponse = curlInit($detailsUrl);
        $detailsData = json_decode($detailsResponse, true);
        
        // Create map of video ID to duration, view count, and category
        $videoDurations = [];
        $videoStats = [];
        $videoCategories = [];
        if ($detailsData && isset($detailsData['items'])) {
            foreach ($detailsData['items'] as $video) {
                $duration = $video['contentDetails']['duration'] ?? '';
                $seconds = parseDuration($duration);
                $videoDurations[$video['id']] = $seconds;
                $videoStats[$video['id']] = $video['statistics'] ?? [];
                $videoCategories[$video['id']] = $video['snippet']['categoryId'] ?? '';
            }
        }
        
        // Filter videos based on duration and limit to requested count
        $filteredItems = [];
        foreach ($searchData['items'] as $item) {
            $videoId = $item['id']['videoId'] ?? '';
            $duration = $videoDurations[$videoId] ?? 0;
            $categoryId = $videoCategories[$videoId] ?? '';
            $title = $item['snippet']['title'] ?? '';
            $description = $item['snippet']['description'] ?? '';
            
            // Skip gaming category (20) and gaming-related content
            if ($categoryId === '20') {
                continue;
            }
            
            // Additional text-based filtering for gaming content
            $gamingKeywords = ['gaming', 'gameplay', 'FIFA', 'NHL 24', 'NHL 25', 'EA Sports', 'Xbox', 'PlayStation', 'PC gaming'];
            $isGamingContent = false;
            foreach ($gamingKeywords as $keyword) {
                if (stripos($title, $keyword) !== false || stripos($description, $keyword) !== false) {
                    $isGamingContent = true;
                    break;
                }
            }
            
            if ($isGamingContent) {
                continue;
            }
            
            // For medium/long duration, be more flexible with duration requirements
            // Medium: 2+ minutes, Long: 5+ minutes (reduced from previous values)
            $minDuration = ($videoDuration === 'long') ? 300 : 120; // 5 min for long, 2 min for medium
            
            if ($duration >= $minDuration) {
                // Convert search result format to match playlist format for compatibility
                $convertedItem = [
                    'snippet' => [
                        'resourceId' => ['videoId' => $videoId],
                        'title' => $item['snippet']['title'],
                        'description' => $item['snippet']['description'],
                        'thumbnails' => $item['snippet']['thumbnails'],
                        'channelTitle' => $item['snippet']['channelTitle'],
                        'publishedAt' => $item['snippet']['publishedAt']
                    ]
                ];
                
                $filteredItems[] = $convertedItem;
                
                // Stop when we have enough videos
                if (count($filteredItems) >= $maxResults) {
                    break;
                }
            }
        }
        
        return $filteredItems;
        
    } catch (Exception $e) {
        error_log("YouTube Search API Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Render YouTube search results with optional caching
 * @param string $query Search query (default: 'NHL hockey')
 * @param int $maxResults Maximum number of videos to fetch (default: 10)
 * @param string $containerId HTML container ID (default: 'videos')
 * @param string $videoDuration Duration filter: 'short', 'medium', 'long' (default: 'medium')
 * @param bool $useCache Whether to use caching (default: true)
 * @param int $cacheLifetime Cache lifetime in seconds (default: 3600 = 1 hour)
 * @return void Outputs HTML directly
 * @note Tries to get videos from last 7 days first, falls back to 30 days if needed
 * @note Excludes gaming category and gaming-related content
 */
function renderYouTubeSearchVideos($query = 'NHL hockey', $maxResults = 10, $containerId = 'videos', $videoDuration = 'medium', $useCache = true, $cacheLifetime = 3600) {
    $videosData = null;
    
    if ($useCache) {
        // Create cache file path
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cacheKey = md5($query . $videoDuration . $maxResults);
        $cacheFile = $cacheDir . "/youtube-search-{$cacheKey}.json";
        
        // Check if cache exists and is valid
        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
            $videosData = json_decode(file_get_contents($cacheFile), true);
        } else {
            // Fetch fresh data
            $videos = searchYouTubeVideos($query, $maxResults, $videoDuration);
            if ($videos !== false) {
                $videosData = $videos;
                // Save to cache
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                file_put_contents($cacheFile, json_encode($videos));
            }
        }
    } else {
        // Fetch without caching
        $videosData = searchYouTubeVideos($query, $maxResults, $videoDuration);
    }
    
    // Output the container and JavaScript
    // Mark container as swiper-enabled (JS will enhance to a Swiper carousel)
    echo '<div id="' . htmlspecialchars($containerId) . '" class="youtube-videos grid grid-400 grid-gap-lg grid-gap-row-lg" data-swiper-enabled="1">';
    
    if ($videosData !== false && !empty($videosData)) {
        // Embed video data as JavaScript variable
        echo '<script>window.youtubeVideosData = ' . json_encode(['items' => $videosData]) . ';</script>';
    } else {
        echo '<div class="alert info">No videos available at the moment</div>';
    }
    
    echo '</div>';
    
    // Include the JavaScript module
    echo '<script type="module">';
    echo 'import { initYouTubeVideos } from "./assets/js/modules/youtube-videos.js";';
    echo 'document.addEventListener("DOMContentLoaded", initYouTubeVideos);';
    echo '</script>';
}

/**
 * Simple function to just get YouTube videos data without rendering
 * @param string $channelId YouTube channel ID
 * @param int $maxResults Maximum number of videos to fetch (default: 10)
 * @param bool $useCache Whether to use caching (default: true)
 * @param int $cacheLifetime Cache lifetime in seconds (default: 3600 = 1 hour)
 * @return array|false Array of video data or false on error
 */
function getYouTubeVideos($channelId, $maxResults = 10, $useCache = true, $cacheLifetime = 3600) {
    if ($useCache) {
        // Create cache file path
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cacheFile = $cacheDir . "/youtube-{$channelId}-{$maxResults}.json";
        
        // Check if cache exists and is valid
        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
            return json_decode(file_get_contents($cacheFile), true);
        } else {
            // Fetch fresh data
            $videos = fetchYouTubeVideos($channelId, $maxResults);
            if ($videos !== false) {
                // Save to cache
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                file_put_contents($cacheFile, json_encode($videos));
                return $videos;
            }
            return false;
        }
    } else {
        // Fetch without caching
        return fetchYouTubeVideos($channelId, $maxResults);
    }
}