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
    $ApiUrl = 'https://api.nhle.com/stats/rest/en/season?sort=%5B%7B%22property%22:%22id%22,%22direction%22:%22DESC%22%7D%5D&limit=3';
    $curl = curlInit($ApiUrl);
    $activeSeasons = json_decode($curl);
    return $activeSeasons;
}