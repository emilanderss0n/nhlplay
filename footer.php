<div class="support-banner">
    <div class="content">
        <h3>Enjoying NHLPLAY?</h3>
        <p>Enjoying the ad-free experience? Consider supporting NHLPLAY.online with a small donation. This site is 100% non-profit and run with love for the game.</p>
        <div class="content-links">
            <a href="https://ko-fi.com/moxopixel" target="_blank" rel="noopener noreferrer" class="btn"><i class="bi bi-cup-hot-fill"></i> Ko-Fi</a>
            <a href="https://paypal.me/moxopixel" target="_blank" rel="noopener noreferrer" class="btn"><i class="bi bi-paypal"></i> PayPal</a>
        </div>
    </div>
</div>
<footer>
    <div class="wrapper">
        <div class="footer-info">Copyright © <?php echo date('Y'); ?> <span>/</span> <strong>NHLPLAY</strong> <span>/</span> <a class="social-btn-twitter" href="https://twitter.com/NHLPlayOnline" target="_blank"><i class="bi bi-twitter"></i> Follow</a></div>
        <div class="credit">Created by <a href="https://emils.graphics" target="_blank">emils.graphics</a></div>
    </div>
</footer>

<?php
// Require $app context; fall back to minimal defaults if missing
$app = $app ?? ($GLOBALS['app'] ?? null);
if (!$app) { $app = ['context' => [], 'detect' => null]; }
$detect = $app['detect'] ?? null;
if ($detect && !$detect->isMobile()) { ?>
<script src="<?= BASE_URL ?>/assets/js/datatables.min.js"></script>
<?php } ?>
<?php if (isset($teamBuilderActive) && $teamBuilderActive) { ?>
<script src="https://cdn.jsdelivr.net/npm/@shopify/draggable@1.0.0-beta.11/lib/draggable.bundle.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js" defer></script>
<script type="module" src="<?= BASE_URL ?>/assets/js/global.js"></script>

<script>
    ajaxPath = '<?= BASE_URL ?>/ajax/'; 
    let season = '<?= $app['context']['season'] ?? '' ?>';
</script>
</body>
</html>