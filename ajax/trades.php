<?php
include_once '../path.php';
include_once '../includes/functions.php';
$app = $app ?? ($GLOBALS['app'] ?? null);

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

// Handle AJAX request for individual signing
if (isset($_GET['signing-expanded']) && isset($_GET['index'])) {
    $signingIndex = intval($_GET['index']);
    $signingTracker = fetchSigningData();
    
    if ($signingTracker && is_array($signingTracker) && isset($signingTracker[$signingIndex])) {
        $signing = $signingTracker[$signingIndex];
        echo renderSigning($signing);
    } else {
        echo '<div class="signing" style="background: var(--dark-bg-color);">';
        echo '<div class="date">Signing Not Found</div>';
        echo '<div class="signing-content"><div style="text-align: center; padding: 2rem; color: #999;">Signing details not available.</div></div>';
        echo '</div>';
    }
    exit;
}

// Handle AJAX request for view toggle
if (isset($_GET['view'])) {
    $view = $_GET['view'];
    if ($view === 'signings') {
        echo renderSigningContent();
    } else {
        echo renderTradeContent();
    }
    exit;
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
} else {
    include '../header.php';
}

$detect = $app['detect'] ?? null;
$deviceType = ($detect ? ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer') : 'computer');
?>
<main>
    <div class="wrap">
        <div class="component-header">
            <h3 class="title">Trade Tracker</h3>
            <div class="multi">
                <div class="btn-group">
                    <i class="icon bi bi-filter"></i>
                    <a href="javascript:void(0);" id="trades-toggle" class="btn sm active" data-view="trades">Trades</a>
                    <a href="javascript:void(0);" id="signings-toggle" class="btn sm" data-view="signings">Signings</a>
                </div>
            </div>
        </div>
        <div class="trades" id="content-container">
            <?php echo renderTradeContent(); ?>
        </div>
    </div>
</main>
<?php if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
} else {
    include_once '../footer.php';
} ?>