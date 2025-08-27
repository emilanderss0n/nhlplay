<?php
/**
 * Team builder controller: fetch roster info and render fragments
 */
function teambuilder_get_team_roster_html($teamId, $season = null)
{
    $teamAbbrev = idToTeamAbbrev($teamId);
    if (!$teamAbbrev) return null;
    $teamRosterInfo = getTeamRosterInfo($teamAbbrev, $season);
    if (!$teamRosterInfo) return null;
    ob_start();
    ?>
    <div class="team-roster-data" data-team-id="<?= $teamId ?>">
        <div class="forwards-data">
            <?php renderTeamBuilderRoster($teamRosterInfo, $teamId, 'forwards'); ?>
        </div>
        <div class="defensemen-data">
            <?php renderTeamBuilderRoster($teamRosterInfo, $teamId, 'defensemen'); ?>
        </div>
        <div class="goalies-data">
            <?php renderTeamBuilderRoster($teamRosterInfo, $teamId, 'goalies'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
