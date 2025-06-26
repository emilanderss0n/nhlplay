<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
} else {
    include '../header.php';
}

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

            if ($tradeTracker && is_array($tradeTracker)) {
                foreach ($tradeTracker as $trades) {
                    // Check if we have basic trade information
                    if (!isset($trades->details)) {
                        continue; // Skip trades without details
                    }

                    // Handle both array and object format for details
                    $team1 = null;
                    $team2 = null;

                    if (is_array($trades->details)) {
                        $team1 = isset($trades->details[0]) ? $trades->details[0] : null;
                        $team2 = isset($trades->details[1]) ? $trades->details[1] : null;
                    } elseif (is_object($trades->details)) {
                        $team1 = isset($trades->details->{'0'}) ? $trades->details->{'0'} : null;
                        $team2 = isset($trades->details->{'1'}) ? $trades->details->{'1'} : null;
                    }

                    // Skip if no teams found
                    if (!$team1 && !$team2) {
                        continue;
                    }

                    // Determine background style based on available teams
                    $backgroundStyle = "var(--dark-bg-color)";
                    if ($team1 && isset($team1->team->term_id) && $team2 && isset($team2->team->term_id)) {
                        $backgroundStyle = "linear-gradient(120deg, 
                            " . teamToColor(teamSNtoID($team1->team->term_id)) . " -100%,
                            var(--dark-bg-color) 40%,
                            var(--dark-bg-color) 60%,
                            " . teamToColor(teamSNtoID($team2->team->term_id)) . " 200%)";
                    } elseif ($team1 && isset($team1->team->term_id)) {
                        $backgroundStyle = "linear-gradient(120deg, 
                            " . teamToColor(teamSNtoID($team1->team->term_id)) . " 0%,
                            var(--dark-bg-color) 100%)";
                    } elseif ($team2 && isset($team2->team->term_id)) {
                        $backgroundStyle = "linear-gradient(120deg, 
                            var(--dark-bg-color) 0%,
                            " . teamToColor(teamSNtoID($team2->team->term_id)) . " 100%)";
                    }
                    ?>
                    <div class="trade" style="background: <?= $backgroundStyle ?>;">
                        <div class="date"><?= isset($trades->trade_date) ? $trades->trade_date : 'Date TBD' ?></div>
                        <?php if (!$team2): ?>
                            <div class="trade-status" style="color: #ffa500; font-size: 0.9em; margin-bottom: 10px;">
                                ⚠️ Incomplete Trade Information
                            </div>
                        <?php endif; ?>
                        <div class="teams">
                            <?php if ($team1): ?>
                                <div class="team-logo team-logo-1">
                                    <?php if (isset($team1->team->term_id)): ?>
                                        <img src="assets/img/teams/<?= teamSNtoID($team1->team->term_id) ?>.svg" alt="" />
                                    <?php else: ?>
                                        <div
                                            style="width: 40px; height: 40px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                                            ?</div>
                                    <?php endif; ?>
                                </div>
                                <div class="team team-1">
                                    <div class="team-info">
                                        <div class="name"><?= isset($team1->team->name) ? $team1->team->name : 'Team TBD' ?></div>
                                        <div class="text">Acquire</div>
                                    </div>
                                    <div class="list">
                                        <ul>
                                            <?php
                                            if (isset($team1->acquires) && is_array($team1->acquires)) {
                                                foreach ($team1->acquires as $asset) {
                                                    if (!isset($asset->name)) { ?>
                                                        <li><?= $asset ?></li>
                                                    <?php } else { ?>
                                                        <li><?= $asset->name ?></li>
                                                    <?php }
                                                }
                                            } else { ?>
                                                <li style="color: #999; font-style: italic;">Details pending...</li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="team-logo team-logo-1">
                                    <div
                                        style="width: 40px; height: 40px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                                        ?</div>
                                </div>
                                <div class="team team-1">
                                    <div class="team-info">
                                        <div class="name">Team TBD</div>
                                        <div class="text">Acquire</div>
                                    </div>
                                    <div class="list">
                                        <ul>
                                            <li style="color: #999; font-style: italic;">Details pending...</li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($team2): ?>
                                <div class="team team-2">
                                    <div class="team-info">
                                        <div class="name"><?= isset($team2->team->name) ? $team2->team->name : 'Team TBD' ?></div>
                                        <div class="text">Acquire</div>
                                    </div>
                                    <div class="list">
                                        <ul>
                                            <?php
                                            if (isset($team2->acquires) && is_array($team2->acquires)) {
                                                foreach ($team2->acquires as $asset) {
                                                    if (!isset($asset->name)) { ?>
                                                        <li><?= $asset ?></li>
                                                    <?php } else { ?>
                                                        <li><?= $asset->name ?></li>
                                                    <?php }
                                                }
                                            } else { ?>
                                                <li style="color: #999; font-style: italic;">Details pending...</li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="team-logo team-logo-2">
                                    <?php if (isset($team2->team->term_id)): ?>
                                        <img src="assets/img/teams/<?= teamSNtoID($team2->team->term_id) ?>.svg" alt="" />
                                    <?php else: ?>
                                        <div
                                            style="width: 40px; height: 40px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                                            ?</div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="team team-2">
                                    <div class="team-info">
                                        <div class="name">Team TBD</div>
                                        <div class="text">Acquire</div>
                                    </div>
                                    <div class="list">
                                        <ul>
                                            <li style="color: #999; font-style: italic;">Details pending...</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="team-logo team-logo-2">
                                    <div
                                        style="width: 40px; height: 40px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                                        ?</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                }
            } else { ?>
                <div class="trade" style="background: var(--dark-bg-color);">
                    <div class="date">No trades available</div>
                    <div class="teams">
                        <div style="text-align: center; color: #999; padding: 20px;">
                            No trade data available at this time.
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</main>
<?php if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
} else {
    include_once '../footer.php';
} ?>