<?php
function getValue($value, $default = '0') {
    return isset($value) ? htmlspecialchars($value) : $default;
}

function getPercentage($value) {
    return isset($value) ? round($value * 100) . '%' : '0%';
}

function getStreak($streakCode, $streakNumber) {
    return htmlspecialchars($streakCode) . htmlspecialchars($streakNumber);
}

function hexToHSL($hex) {
    // Remove the # if present
    $hex = ltrim($hex, '#');
    
    // Convert to RGB
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    
    $h = $s = $l = ($max + $min) / 2;

    if ($max == $min) {
        $h = $s = 0;
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        
        switch($max) {
            case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
            case $g: $h = ($b - $r) / $d + 2; break;
            case $b: $h = ($r - $g) / $d + 4; break;
        }
        
        $h /= 6;
    }

    return [
        round($h * 360),
        round($s * 100),
        round($l * 100)
    ];
}

function convertCountryAlphas3To2($code) {
    $countries = [
        'AFG' => 'AF', 'ALA' => 'AX', 'ALB' => 'AL', 'DZA' => 'DZ', 'ASM' => 'AS',
        'AND' => 'AD', 'AGO' => 'AO', 'AIA' => 'AI', 'ATA' => 'AQ', 'ATG' => 'AG',
        'ARG' => 'AR', 'ARM' => 'AM', 'ABW' => 'AW', 'AUS' => 'AU', 'AUT' => 'AT',
        'AZE' => 'AZ', 'BHS' => 'BS', 'BHR' => 'BH', 'BGD' => 'BD', 'BRB' => 'BB',
        'BLR' => 'BY', 'BEL' => 'BE', 'BLZ' => 'BZ', 'BEN' => 'BJ', 'BMU' => 'BM',
        'BTN' => 'BT', 'BOL' => 'BO', 'BIH' => 'BA', 'BWA' => 'BW', 'BVT' => 'BV',
        'BRA' => 'BR', 'VGB' => 'VG', 'IOT' => 'IO', 'BRN' => 'BN', 'BUL' => 'BG',
        'BFA' => 'BF', 'BDI' => 'BI', 'KHM' => 'KH', 'CMR' => 'CM', 'CAN' => 'CA',
        'CPV' => 'CV', 'CYM' => 'KY', 'CAF' => 'CF', 'TCD' => 'TD', 'CHL' => 'CL',
        'CHN' => 'CN', 'HKG' => 'HK', 'MAC' => 'MO', 'CXR' => 'CX', 'CCK' => 'CC',
        'COL' => 'CO', 'COM' => 'KM', 'COG' => 'CG', 'COD' => 'CD', 'COK' => 'CK',
        'CRI' => 'CR', 'CIV' => 'CI', 'HRV' => 'HR', 'CUB' => 'CU', 'CYP' => 'CY',
        'CZE' => 'CZ', 'DEN' => 'DK', 'DKK' => 'DK', 'DJI' => 'DJ', 'DMA' => 'DM',
        'DOM' => 'DO', 'ECU' => 'EC', 'Sal' => 'El', 'GNQ' => 'GQ', 'ERI' => 'ER',
        'EST' => 'EE', 'ETH' => 'ET', 'FLK' => 'FK', 'FRO' => 'FO', 'FJI' => 'FJ',
        'FIN' => 'FI', 'FRA' => 'FR', 'GUF' => 'GF', 'PYF' => 'PF', 'ATF' => 'TF',
        'GAB' => 'GA', 'GMB' => 'GM', 'GEO' => 'GE', 'GER' => 'DE', 'GHA' => 'GH',
        'GIB' => 'GI', 'GRC' => 'GR', 'GRL' => 'GL', 'GRD' => 'GD', 'GLP' => 'GP',
        'GUM' => 'GU', 'GTM' => 'GT', 'GGY' => 'GG', 'GIN' => 'GN', 'GNB' => 'GW',
        'GUY' => 'GY', 'HTI' => 'HT', 'HMD' => 'HM', 'VAT' => 'VA', 'HND' => 'HN',
        'HUN' => 'HU', 'ISL' => 'IS', 'IND' => 'IN', 'IDN' => 'ID', 'IRN' => 'IR',
        'IRQ' => 'IQ', 'IRL' => 'IE', 'IMN' => 'IM', 'ISR' => 'IL', 'ITA' => 'IT',
        'JAM' => 'JM', 'JPN' => 'JP', 'JEY' => 'JE', 'JOR' => 'JO', 'KAZ' => 'KZ',
        'KEN' => 'KE', 'KIR' => 'KI', 'PRK' => 'KP', 'KOR' => 'KR', 'KWT' => 'KW',
        'KGZ' => 'KG', 'LAO' => 'LA', 'LAT' => 'LV', 'LBN' => 'LB', 'LSO' => 'LS',
        'LBR' => 'LR', 'LBY' => 'LY', 'LIE' => 'LI', 'LTU' => 'LT', 'LUX' => 'LU',
        'MKD' => 'MK', 'MDG' => 'MG', 'MWI' => 'MW', 'MYS' => 'MY', 'MDV' => 'MV',
        'MLI' => 'ML', 'MLT' => 'MT', 'MHL' => 'MH', 'MTQ' => 'MQ', 'MRT' => 'MR',
        'MUS' => 'MU', 'MYT' => 'YT', 'MEX' => 'MX', 'FSM' => 'FM', 'MDA' => 'MD',
        'MCO' => 'MC', 'MNG' => 'MN', 'MNE' => 'ME', 'MSR' => 'MS', 'MAR' => 'MA',
        'MOZ' => 'MZ', 'MMR' => 'MM', 'NAM' => 'NA', 'NRU' => 'NR', 'NPL' => 'NP',
        'NLD' => 'NL', 'ANT' => 'AN', 'NCL' => 'NC', 'NZL' => 'NZ', 'NIC' => 'NI',
        'NER' => 'NE', 'NGA' => 'NG', 'NIU' => 'NU', 'NFK' => 'NF', 'MNP' => 'MP',
        'NOR' => 'NO', 'OMN' => 'OM', 'PAK' => 'PK', 'PLW' => 'PW', 'PSE' => 'PS',
        'PAN' => 'PA', 'PNG' => 'PG', 'PRY' => 'PY', 'PER' => 'PE', 'PHL' => 'PH',
        'PCN' => 'PN', 'POL' => 'PL', 'PRT' => 'PT', 'PRI' => 'PR', 'QAT' => 'QA',
        'REU' => 'RE', 'ROU' => 'RO', 'RUS' => 'RU', 'RWA' => 'RW', 'BLM' => 'BL',
        'SHN' => 'SH', 'KNA' => 'KN', 'LCA' => 'LC', 'MAF' => 'MF', 'SPM' => 'PM',
        'VCT' => 'VC', 'WSM' => 'WS', 'SMR' => 'SM', 'STP' => 'ST', 'SAU' => 'SA',
        'SEN' => 'SN', 'SRB' => 'RS', 'SYC' => 'SC', 'SLE' => 'SL', 'SGP' => 'SG',
        'SVK' => 'SK', 'SLO' => 'SI', 'SLB' => 'SB', 'SOM' => 'SO', 'ZAF' => 'ZA',
        'SGS' => 'GS', 'SSD' => 'SS', 'ESP' => 'ES', 'LKA' => 'LK', 'SDN' => 'SD',
        'SUR' => 'SR', 'SJM' => 'SJ', 'SWZ' => 'SZ', 'SWE' => 'SE', 'CHE' => 'CH',
        'SUI' => 'CH', 'SYR' => 'SY', 'TWN' => 'TW', 'TJK' => 'TJ', 'TZA' => 'TZ',
        'THA' => 'TH', 'TLS' => 'TL', 'TGO' => 'TG', 'TKL' => 'TK', 'TON' => 'TO',
        'TTO' => 'TT', 'TUN' => 'TN', 'TUR' => 'TR', 'TKM' => 'TM', 'TCA' => 'TC',
        'TUV' => 'TV', 'UGA' => 'UG', 'UKR' => 'UA', 'ARE' => 'AE', 'GBR' => 'GB',
        'USA' => 'US', 'UMI' => 'UM', 'URY' => 'UY', 'UZB' => 'UZ', 'VUT' => 'VU',
        'VEN' => 'VE', 'VNM' => 'VN', 'VIR' => 'VI', 'WLF' => 'WF', 'ESH' => 'EH',
        'YEM' => 'YE', 'ZMB' => 'ZM', 'ZWE' => 'ZW', 'GBP' => 'GB', 'RUB' => 'RU',
        'NOK' => 'NO'
    ];
    
    return isset($countries[$code]) ? $countries[$code] : $code;
}

function getAssetUrl($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // If path already contains the base URL, return as is
    if (strpos($path, BASE_URL) === 0) {
        return $path;
    }
    
    return rtrim(BASE_URL, '/') . '/' . $path;
}

function generateMergedCSS($cssFiles, $outputFile) {
    $rootPath = realpath(__DIR__ . '/../../');
    $outputFilePath = $rootPath . '/assets/css/' . $outputFile;
    $lockFile = $rootPath . '/assets/css/.css_lock';
    $needsUpdate = false;

    // Check if output directory exists and is writable
    $outputDir = dirname($outputFilePath);
    if (!is_dir($outputDir)) {
        if (!@mkdir($outputDir, 0777, true)) {
            error_log("Failed to create output directory: $outputDir");
            return false;
        }
    }
    
    if (!is_writable($outputDir)) {
        error_log("Output directory is not writable: $outputDir");
        return false;
    }

    // Check if the merged file needs to be regenerated
    foreach ($cssFiles as $cssFile) {
        $cssFilePath = $rootPath . '/' . $cssFile;
        if (!file_exists($cssFilePath)) {
            error_log("CSS file not found: $cssFilePath");
            return false;
        }
        if (!file_exists($outputFilePath) || filemtime($cssFilePath) > filemtime($outputFilePath)) {
            $needsUpdate = true;
            break;
        }
    }

    if ($needsUpdate) {
        // Acquire lock
        $lockHandle = @fopen($lockFile, 'w+');
        if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
            error_log("Could not acquire lock for CSS generation");
            if ($lockHandle) {
                fclose($lockHandle);
            }
            return 'assets/css/' . $outputFile . '?v=' . filemtime($outputFilePath); // Return existing file with version
        }

        try {
            $mergedCSS = '';
            foreach ($cssFiles as $cssFile) {
                $cssFilePath = $rootPath . '/' . $cssFile;
                $cssContent = @file_get_contents($cssFilePath);
                
                if ($cssContent === false) {
                    throw new Exception("Failed to read CSS file: $cssFilePath");
                }

                // Minify CSS
                $cssContent = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $cssContent);
                $cssContent = str_replace(["\r\n", "\r", "\n", "\t"], '', $cssContent);
                $cssContent = preg_replace('/\s+/', ' ', $cssContent);
                $cssContent = preg_replace('/;\s*}/', '}', $cssContent);
                $cssContent = preg_replace('/ {/', '{', $cssContent);
                $cssContent = preg_replace('/, /', ',', $cssContent);

                $mergedCSS .= $cssContent;
            }

            // Write to temporary file first
            $tempFile = $outputFilePath . '.tmp';
            if (@file_put_contents($tempFile, $mergedCSS) === false) {
                throw new Exception("Failed to write merged CSS to temporary file");
            }

            // Atomic rename
            if (!@rename($tempFile, $outputFilePath)) {
                @unlink($tempFile); // Clean up temp file
                throw new Exception("Failed to rename temporary CSS file");
            }

            // Log success
            error_log("Successfully created merged CSS file: $outputFilePath");
            error_log("Merged CSS size: " . strlen($mergedCSS) . " bytes");

        } catch (Exception $e) {
            error_log($e->getMessage());
            // Clean up
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);
            return false;
        }

        // Release lock
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        @unlink($lockFile);
    }

    // Return URL with version parameter to bust cache when file changes
    return 'assets/css/' . $outputFile . '?v=' . filemtime($outputFilePath);
}

/**
 * Renders the NHL Playoffs bracket
 * 
 * @param string $season The season year (e.g., '2025')
 * @param string $headerTitle Optional custom header title, defaults to "Stanley Cup Playoffs"
 * @param bool $showHeader Whether to show the component header, defaults to true
 * @param bool $showFilters Whether to show filters, defaults to true
 * @return string The HTML for the playoffs bracket
 */
function renderPlayoffsBracket($season = '2025', $headerTitle = 'Stanley Cup Playoffs', $showHeader = true, $showFilters = true) {
    // Convert single year to full season format only for the series objects
    $fullSeason = strlen($season) === 4 ? (intval($season) - 1) . $season : $season;
    
    $ApiUrl = "https://api-web.nhle.com/v1/playoff-bracket/{$season}";
    $playoffs = json_decode(curlInit($ApiUrl));
    
    if (!$playoffs || !isset($playoffs->series)) {
        return '<div class="error-message">No playoff data available.</div>';
    }
    
    $pSeries = $playoffs->series;
    $rounds = [
        'round-1' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
        'round-2' => ['I', 'J', 'K', 'L'],
        'round-3' => ['M', 'N'],
        'final' => ['O']
    ];
    
    // Create a mapping of series by letter for quick lookup and add season to each series
    $seriesByLetter = [];
    foreach ($pSeries as $series) {
        if (isset($series->seriesLetter)) {
            $series->season = $fullSeason; // Add season to each series object
            $seriesByLetter[$series->seriesLetter] = $series;
        }
    }
    
    ob_start();
    
    // Header section
    if ($showHeader) {
        echo '<div class="component-header" style="margin-top: 5rem">';
        echo '<h3 class="title">' . htmlspecialchars($headerTitle) . '</h3>';
        if ($showFilters) {
            echo '<div class="btn-group standings-filter">';
            echo '<i class="bi bi-filter icon"></i>';
            echo '<a class="btn sm" id="standings-league" href="#">League</a>';
            echo '<a class="btn sm" id="standings-conference" href="#">Conference</a>';
            echo '<a class="btn sm" id="standings-divisions" href="#">Divisions</a>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    echo '<div class="playoffs-bracket" id="standings-home">';
    echo '<style>';
    echo '.playoffs-bracket:not(:has(table))::before { background-image: url(assets/img/knights-stanley-cup.jpg); }';
    echo '</style>';
    
    // Render each round
    foreach ($rounds as $round => $letters) {
        $eastern = array_slice($letters, 0, (int)(count($letters) / 2));
        $western = array_slice($letters, (int)(count($letters) / 2));
        
        // Find round title from series data
        $roundTitle = str_replace('-', ' ', ucfirst($round));
        foreach ($pSeries as $series) {
            if (in_array($series->seriesLetter ?? '', $letters) && isset($series->seriesTitle)) {
                $roundTitle = $series->seriesTitle;
                break;
            }
        }
        
        // Special handling for different rounds
        if ($round === 'final') {
            renderFinalRound($seriesByLetter, $letters, $roundTitle);
        } else if ($round === 'round-3') {
            renderConferenceFinalsRound($seriesByLetter, $eastern, $western, $round);
        } else {
            renderRegularRound($seriesByLetter, $eastern, $western, $round, $roundTitle);
        }
    }
    
    echo '</style>';
    
    return ob_get_clean();
}

/**
 * Fetches the playoff series games
 * 
 * @param string $season The season year
 * @param string $seriesLetter The series letter (A-O)
 * @return object|null The series games data or null if not found
 */
function getPlayoffSeriesGames($season, $seriesLetter) {
    $ApiUrl = "https://api-web.nhle.com/v1/schedule/playoff-series/{$season}/{$seriesLetter}";
    return json_decode(curlInit($ApiUrl));
}

/**
 * Renders the playoff series modal content
 * 
 * @param object $seriesData The series data
 * @return string HTML content for the modal
 */
function renderPlayoffSeriesModal($seriesData) {
    if (!$seriesData || !isset($seriesData->games) || empty($seriesData->games)) {
        return '<div class="error-message">No series data available.</div>';
    }

    // Map team abbreviations to their logos
    $teamLogos = [
        $seriesData->topSeedTeam->abbrev => [
            'logo' => $seriesData->topSeedTeam->logo,
            'darkLogo' => $seriesData->topSeedTeam->darkLogo
        ],
        $seriesData->bottomSeedTeam->abbrev => [
            'logo' => $seriesData->bottomSeedTeam->logo,
            'darkLogo' => $seriesData->bottomSeedTeam->darkLogo
        ]
    ];

    ob_start();
    ?>
    <div class="series-modal-content">
        <div class="series-games grid grid-300 grid-gap grid-gap-row">
            <?php foreach ($seriesData->games as $game): 
                $awayScore = $game->awayTeam->score ?? null;
                $homeScore = $game->homeTeam->score ?? null;
                $isGameComplete = isset($awayScore) && isset($homeScore);
            ?>
                <div class="series-game">
                    <div class="game-date"><?= date('M j', strtotime($game->startTimeUTC)) ?></div>
                    <div class="game-teams">
                        <div class="team<?= $isGameComplete && $awayScore > $homeScore ? ' won' : '' ?> flex-default">
                            <picture>
                                <source srcset="<?= $teamLogos[$game->awayTeam->abbrev]['darkLogo'] ?>" media="(prefers-color-scheme: dark)">
                                <img src="<?= $teamLogos[$game->awayTeam->abbrev]['logo'] ?>" alt="<?= $game->awayTeam->abbrev ?>" class="team-img" />
                            </picture>
                            <span class="tag t-lg t-strong"><?= $game->awayTeam->abbrev ?></span>
                            <span class="team-score"><?= $awayScore ?? '-' ?></span>
                        </div>
                        <div class="team<?= $isGameComplete && $homeScore > $awayScore ? ' won' : '' ?> flex-default">
                            <picture>
                                <source srcset="<?= $teamLogos[$game->homeTeam->abbrev]['darkLogo'] ?>" media="(prefers-color-scheme: dark)">
                                <img src="<?= $teamLogos[$game->homeTeam->abbrev]['logo'] ?>" alt="<?= $game->homeTeam->abbrev ?>" class="team-img" />
                            </picture>
                            <span class="tag t-lg t-strong"><?= $game->homeTeam->abbrev ?></span>
                            <span class="team-score"><?= $homeScore ?? '-' ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders an empty placeholder matchup
 * 
 * @return string HTML for an empty matchup
 */
function renderEmptyMatchup() {
    ob_start();
    ?>
    <div class="game">
        <div class="game-matchup">
            <div class="team empty-team">
                <div class="placeholder-logo"></div>
                <span>TBD</span>
                <div class="game-score">
                    <span>0</span>
                </div>
            </div>
            <div class="team empty-team">
                <div class="placeholder-logo"></div>
                <span>TBD</span>
                <div class="game-score">
                    <span>0</span>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders the final round (Stanley Cup Final)
 */
function renderFinalRound($seriesByLetter, $letters, $roundTitle) {
    ?>
    <div class="playoffs-round final">
        <div class="round-title"><?= $roundTitle ?></div>
        <div class="round-games final-matchup">
            <?php 
            $hasSeries = false;
            foreach ($letters as $letter) {
                if (isset($seriesByLetter[$letter])) {
                    $series = $seriesByLetter[$letter];
                    if (isset($series->seriesUrl)) {
                        echo renderMatchup($series);
                        $hasSeries = true;
                    }
                }
            }
            
            // If no series exist for this round, show a placeholder
            if (!$hasSeries) {
                echo renderEmptyMatchup();
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the conference finals (round 3)
 */
function renderConferenceFinalsRound($seriesByLetter, $eastern, $western, $round) {
    ?>
    <div class="playoffs-round <?= $round ?>">
        <div class="round-title">Eastern Conference Finals</div>
        <div class="round-games eastern-conference <?= $round ?>">
            <?php 
            $hasEasternSeries = false;
            foreach ($eastern as $letter) {
                if (isset($seriesByLetter[$letter])) {
                    $series = $seriesByLetter[$letter];
                    if (isset($series->seriesUrl)) {
                        echo renderMatchup($series);
                        $hasEasternSeries = true;
                    }
                }
            }
            
            // If no eastern series exist, show a placeholder
            if (!$hasEasternSeries) {
                echo renderEmptyMatchup();
            }
            ?>
        </div>
        <div class="round-title">Western Conference Finals</div>
        <div class="round-games western-conference <?= $round ?>">
            <?php 
            $hasWesternSeries = false;
            foreach ($western as $letter) {
                if (isset($seriesByLetter[$letter])) {
                    $series = $seriesByLetter[$letter];
                    if (isset($series->seriesUrl)) {
                        echo renderMatchup($series);
                        $hasWesternSeries = true;
                    }
                }
            }
            
            // If no western series exist, show a placeholder
            if (!$hasWesternSeries) {
                echo renderEmptyMatchup();
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renders regular playoff rounds (round 1 and 2)
 */
function renderRegularRound($seriesByLetter, $eastern, $western, $round, $roundTitle) {
    ?>
    <div class="playoffs-round <?= $round ?>">
        <div class="round-title"><?= $roundTitle ?></div>
        <div class="round-games eastern-conference <?= $round ?>">
            <?php 
            $hasEasternSeries = false;
            $shownEasternGames = 0;
            foreach ($eastern as $letter) {
                if (isset($seriesByLetter[$letter])) {
                    $series = $seriesByLetter[$letter];
                    if (isset($series->seriesUrl)) {
                        echo renderMatchup($series);
                        $hasEasternSeries = true;
                        $shownEasternGames++;
                    }
                }
            }
            
            // If no eastern series exist for this round or we haven't shown enough games, show placeholders
            $requiredGames = ($round === 'round-1') ? 4 : 2;
            $remainingGames = $requiredGames - $shownEasternGames;
            if ($remainingGames > 0) {
                for ($i = 0; $i < $remainingGames; $i++) {
                    echo renderEmptyMatchup();
                }
            }
            ?>
        </div>
        <div class="round-title"><?= $roundTitle ?></div>
        <div class="round-games western-conference <?= $round ?>">
            <?php 
            $hasWesternSeries = false;
            $shownWesternGames = 0;
            foreach ($western as $letter) {
                if (isset($seriesByLetter[$letter])) {
                    $series = $seriesByLetter[$letter];
                    if (isset($series->seriesUrl)) {
                        echo renderMatchup($series);
                        $hasWesternSeries = true;
                        $shownWesternGames++;
                    }
                }
            }
            
            // If no western series exist for this round or we haven't shown enough games, show placeholders
            $remainingGames = $requiredGames - $shownWesternGames;
            if ($remainingGames > 0) {
                for ($i = 0; $i < $remainingGames; $i++) {
                    echo renderEmptyMatchup();
                }
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renders a team in the playoff bracket
 * 
 * @param object $team The team object
 * @param int $wins Number of wins
 * @return string HTML for the team
 */
function renderPlayoffTeam($team, $wins) {
    ob_start();
    ?>
    <div class="team">
        <picture>
            <source srcset="<?= $team->darkLogo ?>" media="(prefers-color-scheme: dark)">
            <img src="<?= $team->logo ?>" alt="<?= $team->abbrev ?>" class="team-img" />
        </picture>
        <div class="tag t-lg t-strong"><?= $team->abbrev ?></div>
        <div class="game-score">
            <span><?= $wins ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders a matchup in the playoff bracket
 * 
 * @param object $series Series data
 * @return string HTML for the matchup
 */
function renderMatchup($series) {
    ob_start();
    ?>
    <div class="game" data-series-letter="<?= htmlspecialchars($series->seriesLetter) ?>" data-season="<?= htmlspecialchars($series->season) ?>">
        <div class="game-matchup">
            <?= renderPlayoffTeam($series->topSeedTeam, $series->topSeedWins) ?>
            <?= renderPlayoffTeam($series->bottomSeedTeam, $series->bottomSeedWins) ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders in draft tables
 * 
 */
function rankArrow($mid, $final) {
    if ($final < $mid) return ['↑', 'up'];
    if ($final > $mid) return ['↓', 'down'];
    return ['→', ''];
}