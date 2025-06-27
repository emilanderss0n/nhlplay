<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
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