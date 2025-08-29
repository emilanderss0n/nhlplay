<?php
/**
 * SEO-friendly Player Page Template
 * Displays both current season and career stats for a player
 * URL format: /player/firstname-lastname-playerid
 */

// Ensure this is being called properly
if (!defined('IN_PAGE')) define('IN_PAGE', true);

// Include necessary functions
include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/../includes/functions/player-slug-functions.php';
include_once __DIR__ . '/../includes/functions/stats-functions.php';
include_once __DIR__ . '/../includes/functions/player-functions.php';
include_once __DIR__ . '/../includes/controllers/player.php';

// Get the context from router
$pageContext = $pageContext ?? ($app['context'] ?? []);
$playerSlugOrId = $pageContext['player_slug'] ?? $pageContext['player_id'] ?? null;

// Handle both slug and direct ID
$playerId = null;
$playerSlug = null;

if ($playerSlugOrId) {
    if (is_numeric($playerSlugOrId)) {
        // Direct player ID
        $playerId = $playerSlugOrId;
    } else {
        // Player slug - extract ID
        $playerId = extractPlayerIdFromSlug($playerSlugOrId);
        $playerSlug = $playerSlugOrId;
    }
}

// Fallback to GET parameters
if (!$playerId) {
    if (isset($_GET['playerId'])) {
        $playerId = $_GET['playerId'];
        // Redirect to SEO-friendly URL
        if ($playerId) {
            try {
                $playerData = player_fetch_landing($playerId);
                if ($playerData && isset($playerData->playerSlug)) {
                    $canonicalUrl = getPlayerCanonicalUrl($playerData);
                    header("Location: $canonicalUrl", true, 301);
                    exit();
                }
            } catch (Exception $e) {
                // Continue with current flow if redirect fails
            }
        }
    } elseif (isset($_GET['player'])) {
        $playerId = $_GET['player'];
        // Redirect to SEO-friendly URL
        if ($playerId) {
            try {
                $playerData = player_fetch_landing($playerId);
                if ($playerData && isset($playerData->playerSlug)) {
                    $canonicalUrl = getPlayerCanonicalUrl($playerData);
                    header("Location: $canonicalUrl", true, 301);
                    exit();
                }
            } catch (Exception $e) {
                // Continue with current flow if redirect fails
            }
        }
    }
}

if (!$playerId) {
    http_response_code(404);
    echo '<div class="error-message"><h1>Player not found</h1><p>The requested player could not be found.</p></div>';
    return;
}

// Fetch player data
$player = player_fetch_landing($playerId);

if (!$player || !isset($player->playerId)) {
    http_response_code(404);
    echo '<div class="error-message"><h1>Player not found</h1><p>The requested player could not be found.</p></div>';
    return;
}

// Validate slug if provided
if ($playerSlug) {
    $expectedSlug = $player->playerSlug ?? createPlayerSlug($player);
    if ($playerSlug !== $expectedSlug) {
        // Redirect to canonical URL
        $canonicalUrl = getPlayerCanonicalUrl($player);
        header("Location: $canonicalUrl", true, 301);
        exit();
    }
}

// Set up variables similar to player-view.php
$cm = $player->heightInCentimeters ?? null;
$playerSeasonStats = $player->featuredStats->regularSeason->subSeason ?? null;
$playerPlayoffsStats = $player->featuredStats->playoffs->subSeason ?? null;
$statTotals = $player->featuredStats->regularSeason->career ?? null;

// Determine player type and initialize flags
$isSkater = ($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D');
$isForward = ($player->position == 'C' || $player->position == 'L' || $player->position == 'R');
$needsAdvancedStats = false;

// Get season for advanced stats
$season = $lastSeason ?? '20242025';

// Get advanced stats for regular season and playoffs
if ($isSkater) {
    $regularSeasonAdvancedStats = getPlayerAdvancedStats($playerId, $season, 2);
    $playoffAdvancedStats = $playerPlayoffsStats ? getPlayerAdvancedStats($playerId, $season, 3) : null;
} else {
    $summary = $playerSeasonStats ?? null;
    
    if ($summary && isset($summary->savePct)) {
        $savePct = $summary->savePct;
        $evSavePct = $savePct;
        $ppSavePct = $savePct;
        $shSavePct = $savePct;
    } else {
        $savePct = $evSavePct = $ppSavePct = $shSavePct = 0;
    }
    
    $needsAdvancedStats = true;
}

$lastGames = $player->last5Games ?? [];

$dob = $player->birthDate ?? null;
$playerAge = $dob ? (date('Y') - date('Y',strtotime($dob))) : null;
$playerBirthplace = convertCountryAlphas3To2($player->birthCountry) ?? null;
$playerBirthplaceLong = \Locale::getDisplayRegion('-' . $playerBirthplace, 'en');

// Career data for the second section
$careerData = player_fetch_career($playerId);
$careerTotals = $careerData->careerTotals->regularSeason ?? null;
$careerTotalsPlayoffs = $careerData->careerTotals->playoffs ?? (object)[
    'gamesPlayed' => 0,
    'wins' => 0,
    'shutouts' => 0,
    'savePctg' => 0,
    'goalsAgainstAvg' => 0,
    'goals' => 0,
    'assists' => 0,
    'points' => 0,
    'shootingPctg' => 0,
    'plusMinus' => 0,
    'pim' => 0,
];
$careerAll = $careerData->seasonTotals ?? [];

// Set page metadata
$playerFullName = $player->firstName->default . ' ' . $player->lastName->default;
$teamNameSafe = '';
if (isset($player->fullTeamName)) {
    if (is_string($player->fullTeamName)) {
        $teamNameSafe = $player->fullTeamName;
    } elseif (is_object($player->fullTeamName) && isset($player->fullTeamName->default)) {
        $teamNameSafe = $player->fullTeamName->default;
    }
}

$pageTitle = $playerFullName . ' - NHL Stats';
$pageDescription = 'View career stats, current season performance, and game logs for ' . $playerFullName . ' (#' . $player->sweaterNumber . '), ' . positionCodeToName($player->position) . ($teamNameSafe ? ' for the ' . $teamNameSafe : '') . '.';
$canonicalUrl = getPlayerCanonicalUrl($player);

// Set meta tags for SEO
if (!headers_sent()) {
    header("Link: <$canonicalUrl>; rel=\"canonical\"");
}
?>

<!-- SEO Meta Tags -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "<?= htmlspecialchars($playerFullName) ?>",
  "jobTitle": "Professional Hockey Player",
  "memberOf": {
    "@type": "SportsTeam",
    "name": "<?= htmlspecialchars($teamNameSafe) ?>"
  },
  "birthDate": "<?= htmlspecialchars($player->birthDate ?? '') ?>",
  "nationality": "<?= htmlspecialchars($playerBirthplaceLong ?? '') ?>",
  "url": "<?= htmlspecialchars($canonicalUrl) ?>"
}
</script>

<style>
    #player-modal {
        background-color: transparent;
        height: auto;
        z-index: 0;
        top: 0;
        left: 0;
        padding: 0;
        max-height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        visibility: visible;
        transform: none;
        transition: none;
        max-width: 1380px;
        margin: 140px auto 7rem;
    }
</style>

<div class="player-page-container">
    <div class="wrap">
        <div id="player-modal">
            <div class="player-header">
                <div class="left">
                    <div class="headshot">
                        <svg class="headshot_wrap" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(2.009);">
                            <mask id="circleMask:r0:">
                                <svg>
                                    <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                                </svg>
                            </mask>
                            <image mask="url(#circleMask:r0:)" fill="#000000" id="canTop" height="128" href="<?= $player->headshot ?>"></image>
                        </svg>
                        <img class="team-img" src="<?= $player->teamLogo ?>" alt="<?= htmlspecialchars($teamNameSafe) ?> logo" />
                        <svg class="team-fill" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(2);">
                            <circle cx="64" cy="72" r="56" fill="<?= teamToColor($player->currentTeamId) ?>"></circle>
                            <defs>
                                <linearGradient id="gradient:r0:" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                                    <stop offset="65%" stop-opacity=".25" stop-color="#000000"></stop>
                                </linearGradient>
                            </defs>
                            <circle cx="64" cy="72" r="56" fill="url(#gradient:r0:)"></circle>
                        </svg>
                    </div><!-- END .headshot -->
                </div>
                <div class="right">
                    <div class="name">
                        <h1 class="player-name <?php if (($player->position == 'C' || $player->position == 'L' || $player->position == 'R' || $player->position == 'D') && $playerSeasonStats && $playerSeasonStats->points / $playerSeasonStats->gamesPlayed > 1) { echo 'hot'; } ?>">
                            <?= $player->firstName->default ?> <?= $player->lastName->default ?>
                        </h1>
                    </div>
                    <?php if (!empty($player->draftDetails->overallPick)) {
                    echo '<div class="drafted"><i class="bi bi-check2-circle"></i> Drafted #' . $player->draftDetails->overallPick . ' overall by ' . $player->draftDetails->teamAbbrev . ', ' . $player->draftDetails->year . '</div>';
                    } ?>
                    <div class="player-header-info">
                        <div class="info"><div class="label">Position</div><p><?= positionCodeToName($player->position) ?></p></div>
                        <div class="info"><div class="label">Nationality</div><img class="flag" title="<?= $playerBirthplaceLong ?>" src="<?= BASE_URL ?>/assets/img/flags/<?= $playerBirthplace ?>.svg" height="78" width="102" alt="<?= $playerBirthplaceLong ?> flag" /></div>
                        <div class="info"><div class="label">Age</div><p><?= $playerAge ?></p></div>
                        <div class="info"><div class="label">Number</div><p>#<?= $player->sweaterNumber ?></p></div>
                        <label class="info" for="switchHeight"><i class="bi bi-globe"><input type="checkbox" class="switch-system" id="switchHeight"></i><div class="label">Height</div><p class="height imperial" data-imperial-val="<?= htmlspecialchars(convert_to_inches($player->heightInCentimeters), ENT_QUOTES) ?>" data-metric-val="<?= $player->heightInCentimeters ?>"><?= convert_to_inches($player->heightInCentimeters) ?></p></label>
                        <label class="info" for="switchWeight"><i class="bi bi-globe"><input type="checkbox" class="switch-system" id="switchWeight"></i><div class="label">Weight</div><p class="weight imperial" data-imperial-val="<?= $player->weightInPounds ?>" data-metric-val="<?= $player->weightInKilograms ?>"><?= $player->weightInPounds ?></p></label>
                    </div>
                </div>
            </div>
        
            <!-- Current Season Stats Section -->
            <div class="title stats">
                <h2 id="season-career" class="header-text">Current Season Stats</h2>
            </div>
            <div class="stats-player">
                <div class="stats-player-inner">
                    <div class="phone-show">
                        <?php if ($isSkater) { ?>
                            <?= renderPhoneStatsDisplay($playerSeasonStats, $regularSeasonAdvancedStats['formattedSAT'], $regularSeasonAdvancedStats['formattedUSAT'], $regularSeasonAdvancedStats['evenStrengthGoalDiff'], true) ?>
                        <?php } else { ?>
                            <?= renderPhoneStatsDisplay($playerSeasonStats, null, null, null, false) ?>
                        <?php } ?>
                    </div>
                    <table class="phone-hide">
                        <?php if ($isSkater) { ?>
                            <thead>
                                <tr>
                                    <td>Games</td>
                                    <td>Goals</td>
                                    <td>Assists</td>
                                    <td>Points</td>
                                    <td>PPG</td>
                                    <td>+/-</td>
                                    <td>PIM</td>
                                    <td>Shots</td>
                                    <td>S%</td>
                                    <td data-tooltip="Shot Attempts For Percentage">SAT%</td>
                                    <td data-tooltip="Unblocked Shot Attempts For Percentage">USAT%</td>
                                    <td data-tooltip="Even Strength Goal Differential">EV GD</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?= renderPlayerStatsRow($playerSeasonStats, $regularSeasonAdvancedStats['formattedSAT'], $regularSeasonAdvancedStats['formattedUSAT'], $regularSeasonAdvancedStats['evenStrengthGoalDiff']) ?>
                            </tbody>
                        <?php } else { ?>
                            <thead>
                                <tr>
                                    <td>GP</td>
                                    <td>SV%</td>
                                    <td>GAA</td>
                                    <td>W</td>
                                    <td>L (OT)</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?= renderGoalieStatsRow($playerSeasonStats) ?>
                            </tbody>
                        <?php } ?>
                    </table>

                    <?php if ($playerPlayoffsStats) { ?>
                        <div class="title stats">
                            <h3 class="header-text">Playoffs</h3>
                        </div>
                        <div class="phone-show">
                            <?php if ($isSkater) { ?>
                                <?= renderPhoneStatsDisplay($playerPlayoffsStats, $playoffAdvancedStats['formattedSAT'], $playoffAdvancedStats['formattedUSAT'], $playoffAdvancedStats['evenStrengthGoalDiff'], true) ?>
                            <?php } else { ?>
                                <?= renderPhoneStatsDisplay($playerPlayoffsStats, null, null, null, false) ?>
                            <?php } ?>
                        </div>
                        <table class="phone-hide">
                            <?php if ($isSkater) { ?>
                                <thead>
                                    <tr>
                                        <td>Games</td>
                                        <td>Goals</td>
                                        <td>Assists</td>
                                        <td>Points</td>
                                        <td>PPG</td>
                                        <td>+/-</td>
                                        <td>PIM</td>
                                        <td>Shots</td>
                                        <td>S%</td>
                                        <td data-tooltip="Shot Attempts For Percentage - The percentage of shot attempts (on goal, missed, or blocked) taken by the player's team while they are on the ice">SAT%</td>
                                        <td data-tooltip="Unblocked Shot Attempts For Percentage - The percentage of unblocked shot attempts (on goal or missed) taken by the player's team while they are on the ice">USAT%</td>
                                        <td data-tooltip="Even Strength Goal Differential - The difference between goals for and against at even strength while the player is on the ice">EV GD</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?= renderPlayerStatsRow($playerPlayoffsStats, $playoffAdvancedStats['formattedSAT'], $playoffAdvancedStats['formattedUSAT'], $playoffAdvancedStats['evenStrengthGoalDiff']) ?>
                                </tbody>
                            <?php } else { ?>
                                <thead>
                                    <tr>
                                        <td>GP</td>
                                        <td>SV%</td>
                                        <td>GAA</td>
                                        <td>W</td>
                                        <td>L (OT)</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?= renderGoalieStatsRow($playerPlayoffsStats) ?>
                                </tbody>
                            <?php } ?>
                        </table>
                    <?php } ?>
                    
                    <?= renderLastGames($lastGames, $isSkater) ?>
                </div>
            </div>

            <!-- Career Stats Section -->
            <?php if ($careerTotals) { ?>
            <div class="title stats">
                <h2 class="header-text">Career Stats</h2>
            </div>
            <div class="stats-player">
                <div class="stats-player-inner">
                    <div class="career-stats">
                        <?php if ($player->position == 'G') { ?>
                        <table class="phone-hide">
                            <thead>
                                <tr>
                                    <td>Games</td>
                                    <td>Wins</td>
                                    <td>Shutouts</td>
                                    <td>SV%</td>
                                    <td>GAA</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= $careerTotals->gamesPlayed ?></td>
                                    <td><?= $careerTotals->wins ?></td>
                                    <td><?= $careerTotals->shutouts ?></td>
                                    <td><?= number_format((float)$careerTotals->savePctg, 3, '.', '') ?></td>
                                    <td><?= number_format((float)$careerTotals->goalsAgainstAvg, 2, '.', '') ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="phone-show">
                            <div class="stat">
                                <div class="label">Games</div>
                                <div class="value"><?= $careerTotals->gamesPlayed ?? '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">GAA</div>
                                <div class="value"><?= isset($careerTotals->goalsAgainstAvg) ? number_format((float)$careerTotals->goalsAgainstAvg, 2, '.', '') : '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">SV%</div>
                                <div class="value"><?= isset($careerTotals->savePctg) ? number_format((float)$careerTotals->savePctg, 3, '.', '') : '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Wins</div>
                                <div class="value"><?= $careerTotals->wins ?? '' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Shutouts</div>
                                <div class="value"><?= $careerTotals->shutouts ?? '' ?></div>
                            </div>
                        </div>
                        <?php } else { ?>
                        <table class="phone-hide">
                            <thead>
                                <tr>
                                    <td>Games</td>
                                    <td>Goals</td>
                                    <td>Assists</td>
                                    <td>Points</td>
                                    <td>PPG</td>
                                    <td>+/-</td>
                                    <td>PIM</td>
                                    <td>Shots</td>
                                    <td>S%</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= $careerTotals->gamesPlayed ?></td>
                                    <td><?= $careerTotals->goals ?></td>
                                    <td><?= $careerTotals->assists ?></td>
                                    <td><?= $careerTotals->points ?></td>
                                    <td><?= $careerTotals->gamesPlayed > 0 ? number_format($careerTotals->points / $careerTotals->gamesPlayed, 2) : '0.00' ?></td>
                                    <td><?= $careerTotals->plusMinus ?></td>
                                    <td><?= $careerTotals->pim ?></td>
                                    <td><?= $careerTotals->shots ?? 0 ?></td>
                                    <td><?= isset($careerTotals->shootingPctg) ? number_format($careerTotals->shootingPctg, 1) . '%' : '0.0%' ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="phone-show">
                            <div class="stat">
                                <div class="label">Games</div>
                                <div class="value"><?= $careerTotals->gamesPlayed ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Goals</div>
                                <div class="value"><?= $careerTotals->goals ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Assists</div>
                                <div class="value"><?= $careerTotals->assists ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Points</div>
                                <div class="value"><?= $careerTotals->points ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">PPG</div>
                                <div class="value"><?= $careerTotals->gamesPlayed > 0 ? number_format($careerTotals->points / $careerTotals->gamesPlayed, 2) : '0.00' ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">+/-</div>
                                <div class="value"><?= $careerTotals->plusMinus ?></div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div><!-- END #player-modal -->
    </div>
</div>

<script type="module">
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize player handlers for any interactive elements
        if (typeof initPlayerHandlers === 'function') {
            initPlayerHandlers({
                playerModal: null, // No modal on this page
                playerActivityElement: null
            });
        }
        
        // Initialize unit switching
        document.querySelectorAll(".switch-system").forEach(switchEl => {
            switchEl.addEventListener("change", () => {
                const infoBox = switchEl.closest(".info");
                const p = infoBox.querySelector("p");

                if (!p) return;

                if (switchEl.checked) {
                    // Metric
                    p.classList.remove("imperial");
                    p.classList.add("metric");
                    p.textContent = p.dataset.metricVal;
                } else {
                    // Imperial
                    p.classList.remove("metric");
                    p.classList.add("imperial");
                    p.textContent = p.dataset.imperialVal;
                }
            });
        });
    });
</script>
