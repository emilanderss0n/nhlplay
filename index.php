<?php
// Keep AJAX behavior for API-style calls
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    include_once 'path.php';
    include_once 'includes/functions.php';
    require_once "includes/MobileDetect.php";
    $detect = new \Detection\MobileDetect;
    $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
} else {
    // Use the new Router for full-page requests
    require_once 'includes/router.php';
    require_once 'header.php';
    // Let the router dispatch the main content area
    ?>
    <main>
        <?php Router::dispatch(); ?>
    </main>
    <?php
    include_once 'footer.php';
}

// ensure cache dir exists (used by various pages)
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

?>