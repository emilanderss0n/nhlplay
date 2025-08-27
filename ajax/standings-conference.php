<?php
include_once '../path.php';
include_once '../includes/functions.php';
$app = $app ?? ($GLOBALS['app'] ?? null);
$detect = $app['detect'] ?? null;

require_once __DIR__ . '/../includes/controllers/standings.php';
$standing = standings_get_data($app);

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