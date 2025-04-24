<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
?>
<main>
    <div class="wrap">
        <div class="game-scores schedule grid grid-300 grid-gap-lg grid-gap-row-sm" grid-max-col-count="4">
            <?php 
            $dateDaysAgo = date('Y-m-d', strtotime('-4 days'));
            $ApiUrl = 'https://api-web.nhle.com/v1/schedule/'. $dateDaysAgo;
            $curl = curlInit($ApiUrl);
            $scores = json_decode($curl);

            gameScores($scores);
            ?>
        </div>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>