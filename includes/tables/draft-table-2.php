<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../functions/api-functions.php';
require_once __DIR__ . '/../functions/utility-functions.php';

$draftYearCurrent = $draftYearParam ?? $_GET['year'] ?? date('Y');
// Use the new NHL API utility
$ApiUrl = NHLApi::draftRankings($draftYearCurrent, '2');
$curl = curlInit($ApiUrl);
$draftRankings = json_decode($curl);
?>
<table id="draftRankings2" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
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
    <?php 
    foreach ($draftRankings->rankings as $draftRank) { 

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
            <td><?= htmlspecialchars($draftRank->lastAmateurClub ?? '') ?></td>
            <td><?= htmlspecialchars($draftRank->lastAmateurLeague ?? '') ?></td>
            <td><?= htmlspecialchars($draftRank->birthCountry ?? '') ?></td>
            <td><?= $age ?></td>
            <td><?= intval(($draftRank->heightInInches ?? 0)/12) . "'" . (($draftRank->heightInInches ?? 0)%12) . '"' ?></td>
            <td><?= $draftRank->weightInPounds ?? 0 ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>