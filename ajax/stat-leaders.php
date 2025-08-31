<?php
include_once '../path.php';
include_once '../includes/functions.php';
include_once __DIR__ . '/../includes/controllers/stat-leaders.php';

// Ensure $app is available but don't break if not
$app = $app ?? ($GLOBALS['app'] ?? []);

// Detect AJAX requests in a forgiving way
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0;
if (!$isAjax) {
    include '../header.php';
}

// Determine selected season/playoffs from query or app context with basic validation
$selectedPlayoffs = false;
if (isset($_GET['playoffs'])) {
    $selectedPlayoffs = filter_var($_GET['playoffs'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?: false;
} else {
    $selectedPlayoffs = $app['playoffs'] ?? false;
}

$selectedSeason = null;
if (isset($_GET['season']) && is_string($_GET['season'])) {
    // Accept 'current' or a numeric season like 20242025
    $s = trim($_GET['season']);
    if ($s === 'current' || preg_match('/^\d{8}$/', $s)) {
        $selectedSeason = $s;
    }
}
if (!$selectedSeason) {
    $selectedSeason = $app['context']['season'] ?? ($GLOBALS['season'] ?? date('Y'));
}

// Keep legacy $season variable for includes that rely on it
$season = $selectedSeason;

// Get available seasons and filter to those with data
$apiSeasons = json_decode(curlInit(NHLApi::season()));
$allSeasons = is_array($apiSeasons) ? array_slice($apiSeasons, -6) : [];
$allSeasons = array_reverse($allSeasons);
$availableSeasons = [];
foreach ($allSeasons as $s) {
    if (seasonHasStatData($s, false)) { // Check for regular season data
        $availableSeasons[] = $s;
    }
}

// Find the latest season with three stars data
$latestThreeStarsSeason = null;
foreach ($allSeasons as $s) {
    if (seasonHasThreeStarsData($s)) {
        $latestThreeStarsSeason = $s;
        break;
    }
}

// If no seasons selected or selected season not available, use the latest available
if (!$selectedSeason || !in_array($selectedSeason, $availableSeasons)) {
    $selectedSeason = !empty($availableSeasons) ? $availableSeasons[0] : $selectedSeason;
}

// Optionally fetch prepped data via controller (not required by renderStatHolder but useful)
// Wrap controller call to avoid fatal errors if API fails
try {
    $leadersData = statleaders_get_leaders($selectedSeason, $selectedPlayoffs);
} catch (Exception $e) {
    error_log('statleaders_get_leaders error: ' . $e->getMessage());
    $leadersData = [];
}

?>
<main>
    <div class="wrap">
        <div class="component-header stat-leaders">
            <h3 class="title">Stat Leaders</h3>
            <div class="multi">
                <?php
                // Build seasons list for the custom dropdown and keep a native select hidden for semantics
                $lastSeasons = $availableSeasons;
                ?>

                <div class="season-select-dropdown custom-select">
                    <input class="dropdown" type="checkbox" id="seasonDropdown" name="seasonDropdown" tabindex="-1" />
                    <label class="for-dropdown" for="seasonDropdown" tabindex="0" role="button" aria-expanded="false" aria-haspopup="true">
                        <span class="season-current"><?= htmlspecialchars($selectedSeason) ?></span>
                        <i class="bi bi-arrow-down-short"></i>
                    </label>
                    <div class="section-dropdown season-options" role="menu" aria-labelledby="seasonDropdown" aria-hidden="true">
                        <?php foreach ($lastSeasons as $s) {
                            $isActive = ($s == $selectedSeason) ? ' active' : '';
                            echo '<a href="#" class="season-select-link'. $isActive .'" data-value="'. htmlspecialchars($s) .'">'. htmlspecialchars($s) .'</a>';
                        } ?>
                    </div>
                    <?php // Hidden native select (keeps existing JS handlers working) ?>
                    <select id="seasonStatLeadersSelect" class="form-select" style="display:none" aria-hidden="true">
                        <?php foreach ($lastSeasons as $s) {
                            $isSelected = ($s == $selectedSeason) ? ' selected' : '';
                            echo '<option value="'. htmlspecialchars($s) .'"'. $isSelected .'>'. htmlspecialchars($s) .'</option>';
                        } ?>
                    </select>
                </div>
                <div class="season-select btn-group">
                    <i class="icon bi bi-filter"></i>
                    <a href="javascript:void(0);" class="btn sm <?= !$selectedPlayoffs ? 'active' : '' ?>" data-season="<?= $selectedSeason ?>" data-playoffs="false">Regular Season</a>
                    <a href="javascript:void(0);" class="btn sm <?= $selectedPlayoffs ? 'active' : '' ?>" data-season="<?= $selectedSeason ?>" data-playoffs="true">Playoffs</a>
                </div>
                <a href="javascript:void(0);" id="stat-leaders-toggle-table" class="btn sm" data-season="<?= $selectedSeason ?>" data-playoffs="<?= $selectedPlayoffs ? 'true' : 'false' ?>">Table</a>
            </div>
        </div>
        <div class="section-stats">
            <div class="stats-leaders skaters">
                <h3>Forwards</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="points" data-list="skaters" class="skaters option active">Points</a>
                    <a href="javascript:void(0);" data-type="goals" data-list="skaters" class="skaters option" data-load="true">Goals</a>
                    <a href="javascript:void(0);" data-type="assists" data-list="skaters" class="skaters option" data-load="true">Assists</a>
                </div>
                <div class="activity-content skaters"><span class="loader"></span></div>
                <div class="stat-points stat-holder skaters">
                    <?= renderStatHolder('skaters', 'points', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-goals stat-holder skaters"></div>
                <div class="stat-assists stat-holder skaters"></div>
            </div>
            <div class="stats-leaders defense">
                <h3>Defensemen</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="points" data-list="defense" class="defense option active">Points</a>
                    <a href="javascript:void(0);" data-type="goals" data-list="defense" class="defense option" data-load="true">Goals</a>
                    <a href="javascript:void(0);" data-type="assists" data-list="defense" class="defense option" data-load="true">Assists</a>
                </div>
                <div class="activity-content defense"><span class="loader"></span></div>
                <div class="stat-points stat-holder defense">
                    <?= renderStatHolder('defense', 'points', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-goals stat-holder defense"></div>
                <div class="stat-assists stat-holder defense"></div>
            </div>
            <div class="stats-leaders goalies">
                <h3>Goalies</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="svp" data-list="goalies" class="goalies option active">Save %</a>
                    <a href="javascript:void(0);" data-type="gaa" data-list="goalies" class="goalies option" data-load="true">GAA</a>
                </div>
                <div class="activity-content goalies"><span class="loader"></span></div>
                <div class="stat-svp stat-holder goalies">
                    <?= renderStatHolder('goalies', 'savePctg', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-gaa stat-holder goalies"></div>
            </div>
            <div class="stats-leaders rookie">
                <h3>Rookies</h3>
                <div class="stat-select">
                    <a href="javascript:void(0);" data-type="points" data-list="rookies" class="rookies option active">Points</a>
                    <a href="javascript:void(0);" data-type="goals" data-list="rookies" class="rookies option" data-load="true">Goals</a>
                    <a href="javascript:void(0);" data-type="assists" data-list="rookies" class="rookies option" data-load="true">Assists</a>
                </div>
                <div class="activity-content rookies"><span class="loader"></span></div>
                <div class="stat-points stat-holder rookies">
                    <?= renderStatHolder('rookies', 'points', $selectedSeason, $selectedPlayoffs); ?>
                </div>
                <div class="stat-goals stat-holder rookies"></div>
                <div class="stat-assists stat-holder rookies"></div>
            </div>
            <!-- Three Stars moved below as its own section -->
        </div><!-- END .section-stats -->

        <?php
        // Show Three Stars only for the latest season with data (not for playoffs)
        if (!$selectedPlayoffs && $latestThreeStarsSeason) {
            $threeStarsHtml = getThreeStars($latestThreeStarsSeason);
            $threeStarsTrim = trim($threeStarsHtml);
            // If getThreeStars explicitly returns a 'No data' message or empty, show alert
            $noDataPatterns = ["No data available", "No data available for the selected season"]; 
            $hasData = $threeStarsTrim !== '' && !preg_match('/' . implode('|', array_map('preg_quote', $noDataPatterns)) . '/i', $threeStarsTrim);
            ?>
            <div class="section-three-stars">
                    <div class="component-header three-stars">
                        <h3 class="title">Three Stars of the Week</h3>
                    </div>
                    <div class="three-stars content">
                        <?php if ($hasData) { echo $threeStarsHtml; } else { echo '<div class="alert">Three Stars are not available yet for the latest season with data.</div>'; } ?>
                    </div>
            </div>
        <?php } ?>
    </div>
</main>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>