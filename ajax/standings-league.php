<?php
include_once '../path.php';
include_once '../includes/functions.php';
// Prefer centralized app context
$app = $app ?? ($GLOBALS['app'] ?? null);
$detect = $app['detect'] ?? null;

require_once __DIR__ . '/../includes/controllers/standings.php';
$standing = standings_get_data($app);
renderLeagueTable($standing, $detect);
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!$detect->isMobile()) { ?>
        let dt = new jsdatatables.JSDataTable('#leagueTable', {
            paging: false,
            searchable: true,
        });
        <?php } ?>

        <?php if ($detect->isMobile()) { ?>
        let dt = '';
        <?php } ?>
    });
</script>