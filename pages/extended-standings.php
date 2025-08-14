<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once '../header-extended.php';
?>
<main>
    <div class="wrap extended">
        <div class="standings">
            <div id="standings-home">
                <table id="leagueTable" data-order='[[ 0, "asc" ]]'>
                    <thead>
                        <td>POS</td>
                        <td>TEAMS</td>
                        <td>GP</td>
                        <td>W</td>
                        <td>L</td>
                        <td>OT</td>
                        <td>PTS</td>
                        <td>P%</td>
                        <td>GS</td>
                        <td>GA</td>
                        <td>S/O</td>
                        <td>L10</td>
                        <td>L10R</td>
                        <td class="no-sort">ST</td>
                    </thead>
                    <tbody>
                        <?php 
                        // Use the new NHL API utility
                        $expands = ['standings.record'];
                        $ApiUrl = NHLApi::legacyStandings('byLeague', $expands);
                        $curl = curlInit($ApiUrl);
                        $standing = json_decode($curl);
                        foreach ($standing->records[0]->teamRecords as $teamStand) { ?>
                        <tr class="team">
                            <td class="position"><strong><?= $teamStand->leagueRank ?></strong></td>
                            <td class="name">
                                <img height="32" width="32" src="<?= BASE_URL ?>/assets/img/teams/<?= $teamStand->team->id ?>.svg" alt="<?= $teamStand->team->name ?>" />
                                <a id="team-link" href="#" data-link="<?= $teamStand->team->id ?>"><?= $teamStand->team->name ?></a>
                            </td>
                            <td class="gp"><?= $teamStand->gamesPlayed ?></td>
                            <td class="wins"><?= $teamStand->leagueRecord->wins ?></td>
                            <td class="losses"><?= $teamStand->leagueRecord->losses ?></td>
                            <td class="ot"><?= $teamStand->leagueRecord->ot ?></td>
                            <td class="points"><strong><?= $teamStand->points ?></strong></td>
                            <td class="pointPerc"><strong><?= number_format((float)$teamStand->pointsPercentage, 2, '.', '') ?></strong></td>
                            <td class="goalsScored"><?= $teamStand->goalsScored ?></td>
                            <td class="goalsagainst"><?= $teamStand->goalsAgainst ?></td>
                            <td class="lastTen"><?= $teamStand->records->overallRecords[2]->wins ?>-<?= $teamStand->records->overallRecords[2]->losses ?></td>
                            <td class="lastTen"><?= $teamStand->records->overallRecords[3]->wins ?>-<?= $teamStand->records->overallRecords[3]->losses ?>-<?= $teamStand->records->overallRecords[3]->ot ?></td>
                            <td class="lastRank"><?= $teamStand->leagueL10Rank ?></td>
                            <td class="streak"><?= $teamStand->streak->streakCode ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="table-description">
                <p><strong>GP</strong> - Games Played</p>
                <p><strong>W</strong> - Wins</p>
                <p><strong>L</strong> - Losses</p>
                <p><strong>OT</strong> - Overtime Losses</p>
                <p><strong>PTS</strong> - Points</p>
                <p><strong>GS</strong> - Goals Scored</p>
                <p><strong>GA</strong> - Goals Against</p>
                <p><strong>S/O</strong> - Wins / loss in shootouts</p>
                <p><strong>L10</strong> - Record for 10 latest games</p>
                <p><strong>L10R</strong> - League ranking for 10 latest games</p>
                <p><strong>ST</strong> - Streak</p>
            </div>
        </div>
    </div>
</main>
<?php include_once '../footer.php'; ?>