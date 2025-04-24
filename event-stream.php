<?php
require_once 'path.php';
require_once 'includes/functions.php';

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx

// Create and use a cache file to persist file modification times between requests
function checkForChanges() {
    $cacheFile = __DIR__ . '/cache/file_timestamps.json';
    
    // Load existing timestamps from cache if it exists
    if (file_exists($cacheFile)) {
        $lastModified = json_decode(file_get_contents($cacheFile), true);
    } else {
        $lastModified = [];
    }
    
    $watchDirs = [
        __DIR__ . '/assets/css'
    ];
    
    $changed = false;
    $changedFile = '';
    $filesChecked = false;
    
    foreach ($watchDirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = $file->getPathname();
                    $mtime = $file->getMTime();
                    $filesChecked = true;
                    
                    // Only report a change if we've seen this file before and it has been modified
                    if (isset($lastModified[$path]) && $lastModified[$path] < $mtime) {
                        $lastModified[$path] = $mtime;
                        $changed = true;
                        $changedFile = $path;
                        break 2;
                    }
                    
                    // Always update the timestamp
                    $lastModified[$path] = $mtime;
                }
            }
        } catch (Exception $e) {
            error_log("Error scanning directory $dir: " . $e->getMessage());
        }
    }
    
    // Only save the timestamps if we actually checked files
    if ($filesChecked) {
        // Make sure cache directory exists
        if (!is_dir(__DIR__ . '/cache')) {
            mkdir(__DIR__ . '/cache', 0755, true);
        }
        
        // Save updated timestamps
        file_put_contents($cacheFile, json_encode($lastModified));
    }
    
    return [$changed, $changedFile];
}

// Prevent time limit
set_time_limit(0);
ignore_user_abort(true);

// Initial delay to let the page load properly
sleep(1);

// Send initial message
echo "data: " . json_encode(['reload' => false, 'message' => 'Hot reload connected']) . "\n\n";
@ob_flush();
flush();

// Initialize timestamps on first load without triggering a reload
checkForChanges();

// Main event loop
while (true) {
    if (connection_aborted()) {
        break;
    }
    
    // Check for file changes
    list($changed, $file) = checkForChanges();
    
    if ($changed) {
        echo "data: " . json_encode(['reload' => true, 'file' => $file]) . "\n\n";
    } else {
        echo "data: " . json_encode(['reload' => false]) . "\n\n";
    }
    
    // Flush the output buffer
    @ob_flush();
    flush();
    
    // Wait before checking again
    sleep(2);
}