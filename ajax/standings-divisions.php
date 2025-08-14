<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

// Use the new NHL API utility
$ApiUrl = NHLApi::standingsNow();
$cacheFile = '../cache/standings-divisions.json';
$cacheTime = 30 * 30;
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
    $standing = json_decode(file_get_contents($cacheFile));
} else {
    $curl = curlInit($ApiUrl);
    $standing = json_decode($curl);
    file_put_contents($cacheFile, json_encode($standing));
}

renderAtlanticDivision($standing, $detect);
renderCentralDivision($standing, $detect);
renderMetropolitanDivision($standing, $detect);
renderPacificDivision($standing, $detect);
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!$detect->isMobile()) { ?>
        let dt = new jsdatatables.JSDataTable('.divisionTable', {
            paging: false,
            searchable: true,
        });
        <?php } ?>

        <?php if ($detect->isMobile()) { ?>
        let dt = '';
        <?php } ?>
    });
</script>