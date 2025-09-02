<?php
$cacheDir = __DIR__ . '/cache';

function rrmdir($dir) {
    if (!is_dir($dir)) return;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            rrmdir($path);       // recurse into subdir
            rmdir($path);        // remove empty subdir
        } else {
            unlink($path);       // remove file
        }
    }
}

rrmdir($cacheDir);