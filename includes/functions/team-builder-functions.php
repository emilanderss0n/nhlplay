<?php
// Performance-optimized team builder functions
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

    // Cache team color and logo for better performance
    $teamColor = teamToColor($activeTeam);
    $teamLogo = getTeamLogo($activeTeam);
    $positionType = rtrim($type, 's');

    // Batch render all players to reduce function call overhead
    foreach ($roster as $player) {
        renderTeamBuilderPlayer($player, $activeTeam, $positionType, $teamColor, $teamLogo);
    }
}

function renderTeamBuilderPlayer($player, $activeTeam, $type, $teamColor = null, $teamLogo = null) {
    // Use cached values if provided, otherwise compute
    $teamColor = $teamColor ?? teamToColor($activeTeam);
    $teamLogo = $teamLogo ?? getTeamLogo($activeTeam);
    
    // Pre-compute values for better performance
    $positionClass = strtolower(positionCodeToName2($player->positionCode));
    $rookieClass = (isset($player->rookie) && $player->rookie === 'true') ? ' rookie' : '';
    $positionName = positionCodeToName($player->positionCode);
    $fullName = ($player->firstName->default ?? '') . ' ' . ($player->lastName->default ?? '');
    ?>
    <a class="player <?= $positionClass ?><?= $rookieClass ?> swiper-slide" 
       href="javascript:void(0);" 
       style="background-image: linear-gradient(142deg, <?= $teamColor ?> -100%, rgba(255,255,255,0) 70%);"
       data-team-id="<?= $activeTeam ?>"
       data-player-id="<?= $player->id ?>">
        <div class="jersey"><span>#</span><?= $player->sweaterNumber ?></div>
        <div class="info">
            <div class="headshot">
                <img class="head" loading="lazy" height="400" width="400" src="<?= $player->headshot ?>" alt="<?= $fullName ?>">
                <img class="team-img" loading="lazy" height="400" width="400" src="<?= $teamLogo ?>" alt="Team logo">
            </div>
            <div class="text">
                <div class="position"><?= $positionName ?></div>
                <div class="name"><?= $fullName ?></div>
            </div>
        </div>
    </a>
    <?php
}
