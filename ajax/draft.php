<?php
include_once '../path.php';
include_once '../includes/functions.php';
$app = $app ?? ($GLOBALS['app'] ?? null);
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

$detect = $app['detect'] ?? null;
$deviceType = ($detect ? ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer') : 'computer');

// Use the new NHL API utility
$ApiUrl = NHLApi::draftRankingsNow();
$curl = curlInit($ApiUrl);
$draftRankings = json_decode($curl);

$draftYearCurrent = $draftRankings->draftYear;
$draftYearLast = $draftYearCurrent - 1;

// Use the new NHL API utility
$ApiUrl2 = NHLApi::draftPicks($draftYearCurrent, '1');
$curl2 = curlInit($ApiUrl2);
$draftPicks = json_decode($curl2);
$draftPicksPicksExists = isset($draftPicks->picks) && !empty($draftPicks->picks);

?>
<main>
    <div class="wrap">
        <?php if ($draftPicksPicksExists) { ?>
        <div class="component-header">
            <h3 class="title">Draft Picks</h3>
        </div>
        <div class="draft-picks-result grid grid-300 grid-gap-lg grid-gap-row-lg" grid-max-col-count="3">
            <?php foreach ($draftPicks->picks as $draftPick) { ?>
                <div class="draft-pick" style="background-image: linear-gradient(22deg, <?= teamToColor($draftPick->teamId) ?> 0%, rgba(255,255,255,0) 120%);">
                    <div class="pick-info">
                        <div class="pick-number">#<?= $draftPick->overallPick ?></div>
                        <div class="pick-team-logo">
                            <picture>
                                <source srcset="<?= $draftPick->teamLogoDark ?>" media="(prefers-color-scheme: dark)">
                                <img src="<?= $draftPick->teamLogoLight ?>" alt="<?= $draftPick->teamName->default ?> logo" class="team-logo">
                            </picture>
                        </div>
                    </div>
                    <div class="pick-name">
                        <?php
                        if (isset($draftPick->firstName->default) && isset($draftPick->lastName->default)) {
                            echo htmlspecialchars($draftPick->firstName->default . ' ' . $draftPick->lastName->default);
                        } else {
                            echo 'TBD';
                        }
                        ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php } else { ?>
            <div class="alert alert-warning" role="alert" style="margin-bottom: 3rem;">
                <span>1st round draft picks will show up here on draft selection in June</span>
                <a href="javascript:void(0)" class="btn sm" id="show-previous-draft" data-draft-year="<?= $draftYearLast ?>">Previous Picks</a>
            </div>
        <?php } ?>
        <div class="previous-draft draft-picks-result grid grid-300 grid-gap-lg grid-gap-row-lg"></div>
        <div class="component-header">
            <h3 class="title">Draft Rankings <span class="lower-contrast">(<?= $draftYearCurrent ?>)</span></h3>
            <div class="btn-group draft-filter">
                <i class="bi bi-filter icon"></i>
                <a class="btn sm active" id="draft-table-1" data-table="1" href="javascript:void(0)" data-tooltip="North American Skaters">N.A. Skaters</a>
                <a class="btn sm" id="draft-table-2" data-table="2" href="javascript:void(0)" data-tooltip="International Skaters">Int. Skaters</a>
                <a class="btn sm" id="draft-table-3" data-table="3" href="javascript:void(0)" data-tooltip="North American Goalies">N.A. Goalies</a>
                <a class="btn sm" id="draft-table-4" data-table="4" href="javascript:void(0)" data-tooltip="International Goalies">Int. Goalies</a>
            </div>
        </div>
        <div class="draft-rankings-table">
            <div id="rankings-container">
                <?php 
                $draftYearParam = isset($_GET['year']) ? $_GET['year'] : $draftYearCurrent;
                include_once '../includes/tables/draft-table-1.php'; 
                ?>
            </div>
            <div class="loading-spinner" style="display: none;">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>