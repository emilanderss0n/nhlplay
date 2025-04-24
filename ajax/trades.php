<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

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
            <?php
            $ApiUrl = 'https://www.sportsnet.ca/wp-json/sportsnet/v1/trade-tracker';
            $curl = curlInit($ApiUrl);
            $tradeTracker = json_decode($curl);
            foreach ($tradeTracker as $trades) {
            ?>
            <div class="trade" style="background: linear-gradient(120deg, 
            <?= teamToColor(teamSNtoID($trades->details->{0}->team->term_id)) ?> -100%,
            var(--dark-bg-color) 40%,
            var(--dark-bg-color) 60%,
            <?= teamToColor(teamSNtoID($trades->details->{1}->team->term_id)) ?> 200%);">
                <div class="date"><?= $trades->trade_date ?></div>
                <div class="teams">
                    <div class="team-logo team-logo-1">
                        <img src="assets/img/teams/<?= teamSNtoID($trades->details->{0}->team->term_id) ?>.svg" alt="" />
                    </div>
                    <div class="team team-1">
                        <div class="team-info">
                            <div class="name"><?= $trades->details->{0}->team->name ?></div>
                            <div class="text">Acquire</div>
                        </div>
                        <div class="list">
                            <ul>
                            <?php foreach ($trades->details->{0}->acquires as $asset) {
                            if (!isset($asset->name)) { ?>
                                <li><?= $asset ?></li>
                            <?php } else { ?>
                                <li><?= $asset->name ?></li>
                            <?php }} ?>
                            </ul>
                        </div>
                    </div>
                    <div class="team team-2">
                        <div class="team-info">
                            <div class="name"><?= $trades->details->{1}->team->name ?></div>
                            <div class="text">Acquire</div>
                        </div>
                        <div class="list">
                            <ul>
                            <?php foreach ($trades->details->{1}->acquires as $asset) {
                            if (!isset($asset->name)) { ?>
                                <li><?= $asset ?></li>
                            <?php } else { ?>
                                <li><?= $asset->name ?></li>
                            <?php }} ?>
                            </ul>
                        </div>
                    </div>
                    <div class="team-logo team-logo-2">
                        <img src="assets/img/teams/<?= teamSNtoID($trades->details->{1}->team->term_id) ?>.svg" alt="" />
                    </div>
                </div>
            </div>
        <?php } ?>
        </div>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>