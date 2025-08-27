<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/scores.php';
$app = $app ?? ($GLOBALS['app'] ?? null);
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
?>
<main>
    <div class="wrap">
    <?php $seasonBreak = $app['seasonBreak'] ?? ($GLOBALS['seasonBreak'] ?? false); if (!$seasonBreak) {
    $scores = scores_get_recent(4);
        ?>
        <div class="game-scores schedule grid grid-300 grid-gap-lg grid-gap-row-sm" grid-max-col-count="4">
        <?php gameScores($scores); ?>
        </div>
    <?php } if ($seasonBreak) { ?>
        <div class="game-scores">
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