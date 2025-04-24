<?php
include_once '../path.php';
include_once '../includes/functions.php';
require_once "../includes/MobileDetect.php";
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

$ApiUrl = 'https://api-web.nhle.com/v1/draft/rankings/now';
$curl = curlInit($ApiUrl);
$draftRankings = json_decode($curl);

$draftYear = $draftRankings->draftYear;

$ApiUrl2 = 'https://api-web.nhle.com/v1/draft/picks/'. $draftYear .'/1';
$curl2 = curlInit($ApiUrl2);
$draftPicks = json_decode($curl2);

// Helper for arrow icons
function rankArrow($mid, $final) {
    if ($final < $mid) return ['↑', 'up'];
    if ($final > $mid) return ['↓', 'down'];
    return ['→', ''];
}
?>
<main>
    <div class="wrap">
        <?php if (!empty($draftPicks)) { ?>
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
                <a href="javascript:void(0)" class="btn sm" id="show-previous-draft" data-draft-year="<?= $draftYear ?>">Previous Picks</a>
            </div>
        <?php } ?>
        <div class="previous-draft draft-picks-result grid grid-300 grid-gap-lg grid-gap-row-lg"></div>
        <div class="component-header">
            <h3 class="title">Draft Rankings</h3>
            <p class="sm">Draft rankings from NHL official API, draft year: <?= $draftYear ?></p>
        </div>
        <div class="draft-rankings-table">
            <table id="draftRankings" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
                <thead>
                    <tr>
                        <td>Final</td>
                        <td>Mid</td>
                        <td>Name</td>
                        <td>Pos</td>
                        <td>Club</td>
                        <td>League</td>
                        <td>Birth</td>
                        <td>Age</td>
                        <td>Height</td>
                        <td>Weight</td>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($draftRankings->rankings as $draftRank) { 
                    $finalRank = isset($draftRank->finalRank) ? $draftRank->finalRank : '-';
                    $midtermRank = isset($draftRank->midtermRank) ? $draftRank->midtermRank : '-';
                    // Calculate age
                    $birthDate = new DateTime($draftRank->birthDate);
                    $today = new DateTime();
                    $age = $today->diff($birthDate)->y;

                    // Skip if either rank is '-'
                    if ($finalRank === '-' || $midtermRank === '-') {
                        continue;
                    }

                    $rankInfo = rankArrow($midtermRank, $finalRank);
                ?>
                    <tr>
                        <td style="font-weight:bold;"><span class="final-rank"><span class="number"><?= $finalRank ?></span><span class="trend <?= $rankInfo[1] ?>"><?= $rankInfo[0] ?></span></span></td>
                        <td><?= $midtermRank ?></td>
                        <td>
                            <?= htmlspecialchars($draftRank->firstName . ' ' . $draftRank->lastName) ?>
                        </td>
                        <td><?= $draftRank->positionCode ?></td>
                        <td><?= htmlspecialchars($draftRank->lastAmateurClub) ?></td>
                        <td><?= htmlspecialchars($draftRank->lastAmateurLeague) ?></td>
                        <td><?= htmlspecialchars($draftRank->birthCountry) ?></td>
                        <td><?= $age ?></td>
                        <td><?= intval($draftRank->heightInInches/12) . "'" . ($draftRank->heightInInches%12) . '"' ?></td>
                        <td><?= $draftRank->weightInPounds ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>