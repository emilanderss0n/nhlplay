<?php
function renderTeamBuilderRoster($teamRosterInfo, $activeTeam, $type) {
    if (!$teamRosterInfo || !$activeTeam) {
        return;
    }

    if (!is_object($teamRosterInfo)) {
        return;
    }

    $roster = match($type) {
        'forwards' => $teamRosterInfo->forwards ?? null,
        'defensemen' => $teamRosterInfo->defensemen ?? null,
        'goalies' => $teamRosterInfo->goalies ?? null,
        default => null
    };

    if (!$roster || !is_array($roster)) {
        return;
    }

    foreach ($roster as $player) {
        renderTeamBuilderPlayer($player, $activeTeam, rtrim($type, 's'));
    }
}

function renderTeamBuilderPlayer($player, $activeTeam, $type) {
    ?>
    <a class="player <?= strtolower(positionCodeToName2($player->positionCode)) ?><?php if (isset($player->rookie) == 'true') { echo ' rookie'; } ?> swiper-slide" 
       href="javascript:void(0);" 
       style="background-image: linear-gradient(142deg, <?= teamToColor($activeTeam) ?> -100%, rgba(255,255,255,0) 70%);"
       data-team-id="<?= $activeTeam ?>"
       data-player-id="<?= $player->id ?>">
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
