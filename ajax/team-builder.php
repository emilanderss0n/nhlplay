<?php
include_once '../path.php';
include_once '../includes/functions.php';
// Process request parameters based on request type
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $activeTeam = $_POST['active_team'] ?? null;
    $isAjax = true;
} else {
    if (!defined('IN_PAGE')) include_once '../header.php';
    // Ensure full-page views have a <main> wrapper so client-side routing can target it
    echo "<main>\n";
    $activeTeam = null;
    $isAjax = false;
}

$teamAbbrev = $activeTeam ? idToTeamAbbrev($activeTeam) : '';
$teamRosterInfo = $activeTeam ? getTeamRosterInfo($teamAbbrev, $season) : null;
$medianAge = $activeTeam ? getTeamMedianAge($teamRosterInfo) : 0;
$teamInfo = $activeTeam ? getTeamStats($teamAbbrev) : null;
$teamBuilderActive = true;
?>

<div popover id="team-builder-player-pool" class="tb-selection-pool">
    <div class="tb-selection-header component-header">
        <h3 class="title">Player Pool</h3>
        <div class="btn-group">
            <i class="bi bi-filter icon"></i>
            <a class="btn sm active" href="javascript:void(0);" data-target="forwards">Forwards</a>
            <a class="btn sm" href="javascript:void(0);" data-target="defensemen">Defensemen</a>
            <a class="btn sm" href="javascript:void(0);" data-target="goalies">Goalies</a>
        </div>
    </div>
    <div class="tb-selection-players">
        <div class="swiper" id="swiper-pool-1">
            <div class="forwards tb-pool swiper-wrapper" id="tb-pool-1">
                <?php renderTeamBuilderRoster($teamRosterInfo, $activeTeam, 'forwards'); ?>
            </div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-scrollbar"></div>
        </div>
        
        <div class="swiper" id="swiper-pool-2">
            <div class="defensemen tb-pool swiper-wrapper" id="tb-pool-2">
                <?php renderTeamBuilderRoster($teamRosterInfo, $activeTeam, 'defensemen'); ?>
            </div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-scrollbar"></div>
        </div>
        
        <div class="swiper" id="swiper-pool-3">
            <div class="goalies tb-pool swiper-wrapper" id="tb-pool-3">
                <?php renderTeamBuilderRoster($teamRosterInfo, $activeTeam, 'goalies'); ?>
            </div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-scrollbar"></div>
        </div>
    </div>
</div>

<div class="wrap">
    <div class="tb-mobile-alert alert">
        <div class="alert-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="alert-text">This feature is not made to be used with mobile devices.</div>
    </div>
    <div id="team-builder-interface">
        <div class="team-roster team-builder">

        <div class="select-players-button component-header">
            <div class="flex-default first">
                <h3 class="title">Team Builder</h3>
                <button class="btn sm" id="draft-mode-toggle"><i class="bi bi-stars"></i> Draft Mode</button>
            </div>
            <div class="flex-default">
                <div class="custom-select">
                    <input class="dropdown" type="checkbox" id="dropdownBuilder" name="dropdownBuilder">
                    <label class="for-dropdown" for="dropdownBuilder">Select Team <i class="bi bi-arrow-down-short"></i></label>
                    <div class="section-dropdown" id="team-selection-custom"> 
                        <div class="fader-top"></div>
                        <div class="container">
                            <?php include '../includes/teamSelection.php'; ?>
                        </div>
                        <div class="fader-bottom"></div>
                    </div>
                </div>
                <button popovertarget="team-builder-player-pool" class="btn sm disabled"><i class="bi bi-person-plus"></i> Add</button>
                <button class="btn sm" id="btn-clear-tb"><i class="bi bi-trash"></i> Clear</button>
            </div>
        </div>

        <div class="team-lines" id="team-builder-drop-area">
            <div class="line-group forwards-lines">
                <h3>Forward Lines</h3>
                <div class="line">
                    <div class="line-label">// Line 1</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                    </div>
                </div>
                <div class="line">
                    <div class="line-label">// Line 2</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                    </div>
                </div>
                <div class="line">
                    <div class="line-label">// Line 3</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                    </div>
                </div>
                <div class="line">
                    <div class="line-label">// Line 4</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                        <div class="player-slot" data-position="forward"></div>
                    </div>
                </div>
            </div>

            <div class="line-group defense-lines">
                <h3>Defense Pairing</h3>
                <div class="line">
                    <div class="line-label">// Pair 1</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="defenseman"></div>
                        <div class="player-slot" data-position="defenseman"></div>
                    </div>
                </div>
                <div class="line">
                    <div class="line-label">// Pair 2</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="defenseman"></div>
                        <div class="player-slot" data-position="defenseman"></div>
                    </div>
                </div>
                <div class="line">
                    <div class="line-label">// Pair 3</div>
                    <div class="line-slots">
                        <div class="player-slot" data-position="defenseman"></div>
                        <div class="player-slot" data-position="defenseman"></div>
                    </div>
                </div>

                <div class="line-group goalie-lines">
                <h3>Starting Goalies</h3>
                <div class="line">
                    <div class="line-slots">
                        <div class="player-slot" data-position="goalie"></div>
                        <div class="player-slot" data-position="goalie"></div>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </div>
    </div>
</div>

<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // AJAX call - footer handled by caller
} else {
    // Close the main wrapper added earlier and include footer for full page
    echo "</main>\n";
    include_once '../footer.php';
} ?>