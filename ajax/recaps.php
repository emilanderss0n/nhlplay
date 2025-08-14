<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
?>
<main>
    <div class="wrap">
        <?php if (!$seasonBreak) {
        $startDate = date('Y-m-d', strtotime('-4 day'));
        // Use the new NHL API utility
        $ApiUrl = NHLApi::scheduleByDate($startDate);
        $curl = curlInit($ApiUrl);
        $schedules = json_decode($curl);
        ?>
        <div class="game-recaps schedule grid grid-300 grid-gap-lg grid-gap-row-sm" grid-max-col-count="4">
        <?php gameRecaps($schedules);  ?>
        </div>
        <?php } if ($seasonBreak) { ?>
        <div class="game-recaps">
            <div class="season-break-notice">
                <img src="assets/img/post-season-2.png" alt="Season Break" />
                <div>
                    <h3>It's post season baby!</h3>
                    <span>Meanwhile, check out <a href="last-season-overview" rel="page">last season overview</a> to see how teams and players performed</span>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>