<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
?>
<main>
    <div class="wrap">
        <div class="game-recaps schedule grid grid-300 grid-gap-lg grid-gap-row-sm" grid-max-col-count="4">
            <div class="alert">No recaps available, try older games below</div>
            <?php 
            $startDate = date('Y-m-d', strtotime('-4 day'));
            $ApiUrl = 'https://api-web.nhle.com/v1/schedule/'. $startDate;
            $curl = curlInit($ApiUrl);
            $schedules = json_decode($curl);
            gameRecaps($schedules);
           ?>
        </div>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>