<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

$ApiUrl = 'https://api-web.nhle.com/v1/standings/now';
$cacheFile = '../cache/standings-conference.json';
$cacheTime = 30 * 30;
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
    $standing = json_decode(file_get_contents($cacheFile));
} else {
    $curl = curlInit($ApiUrl);
    $standing = json_decode($curl);
    file_put_contents($cacheFile, json_encode($standing));
}

renderConferenceTable('E', 'Eastern Conference', $standing, $detect);
renderConferenceTable('W', 'Western Conference', $standing, $detect);
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!$detect->isMobile()) { ?>
        let dt = new jsdatatables.JSDataTable('.conferenceTable', {
            paging: false,
            searchable: true,
        });
        <?php } ?>

        <?php if ($detect->isMobile()) { ?>
        let dt = '';
        <?php } ?>
    });
</script>