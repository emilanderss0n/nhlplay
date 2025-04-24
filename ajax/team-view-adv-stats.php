<?php
include_once '../includes/functions.php';
include_once '../path.php';

$teamId = $_POST['active_team'];

$ApiUrl = 'https://api.nhle.com/stats/rest/en/team/summary?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl = curlInit($ApiUrl);
$teamSummary = json_decode($curl);

$ApiUrl2 = 'https://api.nhle.com/stats/rest/en/team/faceoffpercentages?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl2 = curlInit($ApiUrl2);
$teamFaceoff = json_decode($curl2);

$ApiUrl3 = 'https://api.nhle.com/stats/rest/en/team/goalsbyperiod?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl3 = curlInit($ApiUrl3);
$teamGoalsByPeriod = json_decode($curl3);

$ApiUrl4 = 'https://api.nhle.com/stats/rest/en/team/goalsagainstbystrength?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl4 = curlInit($ApiUrl4);
$teamGoalsAgainstByStrength = json_decode($curl4);

$ApiUrl5 = 'https://api.nhle.com/stats/rest/en/team/goalsforbystrength?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl5 = curlInit($ApiUrl5);
$teamGoalsForByStrength = json_decode($curl5);

$ApiUrl6 = 'https://api.nhle.com/stats/rest/en/team/realtime?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl6 = curlInit($ApiUrl6);
$defensiveTeamStats = json_decode($curl6);

$ApiUrl7 = 'https://api.nhle.com/stats/rest/en/team/penalties?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl7 = curlInit($ApiUrl7);
$teamPenaltyStats = json_decode($curl7);

$ApiUrl8 = 'https://api.nhle.com/stats/rest/en/team/summaryshooting?isAggregate=false&isGame=false&factCayenneExp=gamesPlayed%3E=1&cayenneExp=gameTypeId=2%20and%20teamId='. $teamId .'%20and%20seasonId%3C='. $season .'%20and%20seasonId%3E='. $season;
$curl8 = curlInit($ApiUrl8);
$teamShootingSummary = json_decode($curl8);

?>
<div class="team-roster-header team-adv-stats">
    <div class="team-roster-header-cont">
        <div class="stats">
            <a href="javascript:void(0)" id="closeTeamAdvStats" class="btn sm"><i class="bi bi-arrow-left"></i> Team Roster</a>
        </div>
    </div>
</div>

<div class="team-advanced-stats">

    <h3 class="team-adv-stats-title">Scoring Statistics</h3>
    <table id="teamAdvTable1" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>GA/P/G</td>
            <td>GA/P1</td>
            <td>GA/P2</td>
            <td>GA/P3</td>
            <td>GA/OT</td>
            <td>GF/P/G</td>
            <td>GF/P1</td>
            <td>GF/P2</td>
            <td>GF/P3</td>
            <td>GF/OT</td>
            <td>P%</td>
            <td>EN G</td>
        </thead>
        <tbody>
            <tr>
                <td><?= number_format($teamSummary->data[0]->goalsAgainstPerGame, 1) ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->period1GoalsAgainst ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->period2GoalsAgainst ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->period3GoalsAgainst ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->periodOtGoalsAgainst ?></td>
                <td><?= number_format($teamSummary->data[0]->goalsForPerGame, 1) ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->period1GoalsFor ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->period2GoalsFor ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->period3GoalsFor ?></td>
                <td><?= $teamGoalsByPeriod->data[0]->periodOtGoalsFor ?></td>
                <td><?= number_format($teamSummary->data[0]->pointPct * 100, 1) ?>%</td>
                <td><?= $defensiveTeamStats->data[0]->emptyNetGoals ?></td>
            </tr>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>GA/P/G</strong> - Goals Against Per Game</p>
        <p><strong>GA/P1</strong> - Goals Against Period 1</p>
        <p><strong>GA/P2</strong> - Goals Against Period 2</p>
        <p><strong>GA/P3</strong> - Goals Against Period 3</p>
        <p><strong>GA/OT</strong> - Goals Against Overtime</p>
        <p><strong>GF/P/G</strong> - Goals For Per Game</p>
        <p><strong>GF/P1</strong> - Goals For Period 1</p>
        <p><strong>GF/P2</strong> - Goals For Period 2</p>
        <p><strong>GF/P3</strong> - Goals For Period 3</p>
        <p><strong>GF/OT</strong> - Goals For Overtime</p>
        <p><strong>P%</strong> - Points %</p>
        <p><strong>EN G</strong> - Empty Net Goals</p>
    </div>

    <h3 class="team-adv-stats-title">By Strength Statistics</h3>
    <table id="teamAdvTable2" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>GF/3v3</td>
            <td>GA/3v3</td>
            <td>GF/3v4</td>
            <td>GA/3v4</td>
            <td>GF/3v5</td>
            <td>GA/3v5</td>
            <td>GF/4v3</td>
            <td>GA/4v3</td>
            <td>GF/4v4</td>
            <td>GA/4v4</td>
            <td>GF/4v5</td>
            <td>GA/4v5</td>
            <td>GF/5v3</td>
            <td>GA/5v3</td>
            <td>GF/5v4</td>
            <td>GA/5v4</td>
            <td>GF/5v5</td>
            <td>GA/5v5</td>
        </thead>
        <tbody>
            <tr>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor3On3 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst3On3 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor3On4 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst3On4 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor3On5 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst3On5 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor4On3 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst4On3 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor4On4 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst4On4 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor4On5 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst4On5 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor5On3 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst5On3 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor5On4 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst5On4 ?></td>
                <td><?= $teamGoalsForByStrength->data[0]->goalsFor5On5 ?></td>
                <td><?= $teamGoalsAgainstByStrength->data[0]->goalsAgainst5On5 ?></td>
            </tr>
        </tbody>
    </table>

    <h3 class="team-adv-stats-title">Shooting Summary Statistics</h3>
    <table id="teamAdvTable6" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>SAT AGAINST</td>
            <td>SAT AHEAD</td>
            <td>SAT BEHIND</td>
            <td>SAT CLOSE</td>
            <td>SAT FOR</td>
            <td>SAT TIED</td>
            <td>SAT TOTAL</td>
            <td>USAT AGAINST</td>
            <td>USAT AHEAD</td>
            <td>USAT BEHIND</td>
            <td>USAT CLOSE</td>
            <td>USAT FOR</td>
            <td>USAT TIED</td>
            <td>USAT TOTAL</td>
        </thead>
        <tbody>
            <tr>
                <td><?= $teamShootingSummary->data[0]->satAgainst ?></td>
                <td><?= $teamShootingSummary->data[0]->satAhead ?></td>
                <td><?= $teamShootingSummary->data[0]->satBehind ?></td>
                <td><?= $teamShootingSummary->data[0]->satClose ?></td>
                <td><?= $teamShootingSummary->data[0]->satFor ?></td>
                <td><?= $teamShootingSummary->data[0]->satTied ?></td>
                <td><?= $teamShootingSummary->data[0]->satTotal ?></td>
                <td><?= $teamShootingSummary->data[0]->usatAgainst ?></td>
                <td><?= $teamShootingSummary->data[0]->usatAhead ?></td>
                <td><?= $teamShootingSummary->data[0]->usatBehind ?></td>
                <td><?= $teamShootingSummary->data[0]->usatClose ?></td>
                <td><?= $teamShootingSummary->data[0]->usatFor ?></td>
                <td><?= $teamShootingSummary->data[0]->usatTied ?></td>
                <td><?= $teamShootingSummary->data[0]->usatTotal ?></td>
            </tr>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>SAT AGAINST</strong> - Shot Attempt Differential Against</p>
        <p><strong>SAT AHEAD</strong> - Shot Attempt Differential Ahead</p>
        <p><strong>SAT BEHIND</strong> - Shot Attempt Differential Behind</p>
        <p><strong>SAT CLOSE</strong> - Shot Attempt Differential Close</p>
        <p><strong>SAT FOR</strong> - Shot Attempt Differential For</p>
        <p><strong>SAT TIED</strong> - Shot Attempt Differential Tied</p>
        <p><strong>SAT TOTAL</strong> - Shot Attempt Differential Total</p>
        <p><strong>USAT AGAINST</strong> - Unblocked Shot Attempt Differential Against</p>
        <p><strong>USAT AHEAD</strong> - Unblocked Shot Attempt Differential Ahead</p>
        <p><strong>USAT BEHIND</strong> - Unblocked Shot Attempt Differential Behind</p>
        <p><strong>USAT CLOSE</strong> - Unblocked Shot Attempt Differential Close</p>
        <p><strong>USAT FOR</strong> - Unblocked Shot Attempt Differential For</p>
        <p><strong>USAT TIED</strong> - Unblocked Shot Attempt Differential Tied</p>
        <p><strong>USAT TOTAL</strong> - Unblocked Shot Attempt Differential Total</p>
    </div>

    <h3 class="team-adv-stats-title">Face-Off Statistics</h3>
    <table id="teamAdvTable3" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>DZ/FO%</td>
            <td>NZ/FO%</td>
            <td>OZ/FO%</td>
            <td>FO%</td>
            <td>FO% EV</td>
            <td>FO% PP</td>
            <td>FO% PK</td>
        </thead>
        <tbody>
            <tr>
                <td><?= number_format($teamFaceoff->data[0]->defensiveZoneFaceoffPct * 100, 1) ?>%</td>
                <td><?= number_format($teamFaceoff->data[0]->neutralZoneFaceoffPct * 100, 1) ?>%</td>
                <td><?= number_format($teamFaceoff->data[0]->offensiveZoneFaceoffPct * 100, 1) ?>%</td>
                <td><?= number_format($teamFaceoff->data[0]->faceoffWinPct * 100, 1) ?>%</td>
                <td><?= number_format($teamFaceoff->data[0]->evFaceoffPct * 100, 1) ?>%</td>
                <td><?= number_format($teamFaceoff->data[0]->ppFaceoffPct * 100, 1) ?>%</td>
                <td><?= number_format($teamFaceoff->data[0]->shFaceoffPct * 100, 1) ?>%</td>
            </tr>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>DZ/FO%</strong> - Defensive Zone Faceoff %</p>
        <p><strong>NZ/FO%</strong> - Neutral Zone Faceoff %</p>
        <p><strong>OZ/FO%</strong> - Offensive Zone Faceoff %</p>
        <p><strong>FO%</strong> - Faceoff %</p>
        <p><strong>FO% EV</strong> - Faceoff % Even Strength</p>
        <p><strong>FO% PP</strong> - Faceoff % Power Play</p>
        <p><strong>FO% PK</strong> - Faceoff % Penalty Kill</p>
    </div>

    <h3 class="team-adv-stats-title">Defensive Statistics</h3>
    <table id="teamAdvTable4" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>Blocks/60</td>
            <td>Giveaways/60</td>
            <td>Takeaways/60</td>
            <td>Hits/60</td>
            <td>SAT%</td>
        </thead>
        <tbody>
            <tr>
                <td><?= number_format($defensiveTeamStats->data[0]->blockedShotsPer60, 1) ?></td>
                <td><?= number_format($defensiveTeamStats->data[0]->giveawaysPer60, 1) ?></td>
                <td><?= number_format($defensiveTeamStats->data[0]->takeawaysPer60, 1) ?></td>
                <td><?= number_format($defensiveTeamStats->data[0]->hitsPer60, 1) ?></td>
                <td><?= number_format($defensiveTeamStats->data[0]->satPct * 100, 1) ?>%</td>
            </tr>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>Blocks/60</strong> - Blocks Per 60 Minutes</p>
        <p><strong>Giveaways/60</strong> - Giveaways Per 60 Minutes</p>
        <p><strong>Takeaways/60</strong> - Takeaways Per 60 Minutes</p>
        <p><strong>Hits/60</strong> - Hits Per 60 Minutes</p>
        <p><strong>SAT%</strong> - Shot Attempt Differential %</p>
    </div>

    <h3 class="team-adv-stats-title">Penalty Statistics</h3>
    <table id="teamAdvTable5" class="hover sticky-header" data-order='[[ 0, "asc" ]]'>
        <thead>
            <td>BMP</td>
            <td>GMI</td>
            <td>MAP</td>
            <td>MAJ</td>
            <td>MIN</td>
            <td>MIS</td>
            <td>PD/60</td>
            <td>PT/60</td>
        </thead>
        <tbody>
            <tr>
                <td><?= $teamPenaltyStats->data[0]->benchMinorPenalties ?></td>
                <td><?= $teamPenaltyStats->data[0]->gameMisconducts ?></td>
                <td><?= $teamPenaltyStats->data[0]->matchPenalties ?></td>
                <td><?= $teamPenaltyStats->data[0]->majors ?></td>
                <td><?= $teamPenaltyStats->data[0]->minors ?></td>
                <td><?= $teamPenaltyStats->data[0]->misconducts ?></td>
                <td><?= number_format($teamPenaltyStats->data[0]->penaltiesDrawnPer60, 1) ?></td>
                <td><?= number_format($teamPenaltyStats->data[0]->penaltiesTakenPer60, 1) ?></td>
            </tr>
        </tbody>
    </table>
    <div class="table-description">
        <p><strong>BMP</strong> - Bench Minor Penalties</p>
        <p><strong>GMI</strong> - Game Misconducts</p>
        <p><strong>MAP</strong> - Match Penalties</p>
        <p><strong>MAJ</strong> - Majors</p>
        <p><strong>MIN</strong> - Minors</p>
        <p><strong>MIS</strong> - Misconducts</p>
        <p><strong>PD/60</strong> - Penalties Drawn Per 60 Minutes</p>
        <p><strong>PT/60</strong> - Penalties Taken Per 60 Minutes</p>
    </div>
</div> <!-- .team-advanced-stats -->