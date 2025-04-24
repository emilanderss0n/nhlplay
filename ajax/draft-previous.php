<?php
include_once '../path.php';
include_once '../includes/functions.php';

$ApiUrl = 'https://api-web.nhle.com/v1/draft/picks/'. $draftYear .'/1';
$curl = curlInit($ApiUrl);
$draftPicks = json_decode($curl);

?>
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