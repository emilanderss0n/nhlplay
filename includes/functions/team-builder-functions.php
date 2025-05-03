<?php
function renderTeamBuilderRoster($teamRosterInfo, $activeTeam, $type) {
    if ($type === 'forwards') {
        foreach ($teamRosterInfo->forwards as $player) {
            renderTeamBuilderPlayer($player, $activeTeam, 'forward');
        }
    } else if ($type === 'defensemen') {
        foreach ($teamRosterInfo->defensemen as $player) {
            renderTeamBuilderPlayer($player, $activeTeam, 'defenseman');
        }
    } else if ($type === 'goalies') {
        foreach ($teamRosterInfo->goalies as $player) {
            renderTeamBuilderPlayer($player, $activeTeam, 'goalie');
        }
    }
}

function renderTeamBuilderPlayer($player, $activeTeam, $type) {
    ?>
    <a class="player <?= strtolower(positionCodeToName2($player->positionCode)) ?><?php if (isset($player->rookie) == 'true') { echo ' rookie'; } ?> swiper-slide" href="javacript:void(0);" style="background-image: linear-gradient(142deg, <?= teamToColor($activeTeam) ?> -100%, rgba(255,255,255,0) 70%);">
        <div class="jersey"><span>#</span><?= $player->sweaterNumber ?></div>
        <div class="info">
            <div class="headshot">
                <img class="head" id="canTop" height="400" width="400" src="<?= $player->headshot ?>"></img>
                <img class="team-img" height="400" width="400" src="<?= getTeamLogo($activeTeam) ?>" />
            </div>
            <div class="text">
                <div class="position"><?= positionCodeToName($player->positionCode) ?></div>
                <div class="name"><?= $player->firstName->default ?> <?= $player->lastName->default ?></div>
            </div>
        </div>
    </a>
    <?php
}
