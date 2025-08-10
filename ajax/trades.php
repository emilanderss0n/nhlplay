<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";

// Handle AJAX request for individual trade
if (isset($_GET['expanded']) && isset($_GET['index'])) {
    $tradeIndex = intval($_GET['index']);
    $tradeTracker = fetchTradeData();
    
    if ($tradeTracker && is_array($tradeTracker) && isset($tradeTracker[$tradeIndex])) {
        $trade = $tradeTracker[$tradeIndex];
        echo renderTrade($trade, true, true, $tradeIndex, false); // false for expanded mode
    } else {
        echo '<div class="trade alt-layout expanded" style="background: var(--dark-bg-color);">';
        echo '<div class="date">Trade Not Found</div>';
        echo '<div class="teams"><div style="text-align: center; padding: 2rem; color: #999;">Trade details not available.</div></div>';
        echo '</div>';
    }
    exit;
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
} else {
    include '../header.php';
}

$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
?>
<main>
    <div class="wrap">
        <div class="component-header">
            <h3 class="title">Trade Tracker</h3>
            <p class="sm">Any new trade within 1 days, shows an indicator in the "Links" menu</p>
        </div>
        <div class="trades">
            <?php echo renderTradeContent(); ?>
        </div>
    </div>
</main>
<?php if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
} else {
    include_once '../footer.php';
} ?>