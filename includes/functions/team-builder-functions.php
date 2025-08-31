<?php

function getDraftPlayers() {
    try {
        global $season, $teamAbbrev;

        $position = $_POST['position'] ?? '';
        $round = intval($_POST['round'] ?? 1);
        $filtersRaw = $_POST['filters'] ?? [];
        $excludePlayerIdsRaw = $_POST['excludePlayerIds'] ?? [];

        // Parse filters properly - it might be JSON string
        if (is_string($filtersRaw)) {
            $filters = json_decode($filtersRaw, true) ?: [];
        } else {
            $filters = is_array($filtersRaw) ? $filtersRaw : [];
        }

        // Parse excluded player IDs
        if (is_string($excludePlayerIdsRaw)) {
            $excludePlayerIds = json_decode($excludePlayerIdsRaw, true) ?: [];
        } else {
            $excludePlayerIds = is_array($excludePlayerIdsRaw) ? $excludePlayerIdsRaw : [];
        }

        if (!in_array($position, ['forwards', 'defensemen', 'goalies'])) {
            return ['error' => 'Invalid position'];
        }

        // Check if globals are available
        if (!isset($teamAbbrev) || !is_array($teamAbbrev)) {
            return ['error' => 'Team abbreviation data not available'];
        }

        if (!isset($season)) {
            return ['error' => 'Season data not available'];
        }

        // Use a subset of teams for better performance (can expand to all 32 later)
        $teamIds = array_values($teamAbbrev); // Get all team IDs from global array
        $allPlayers = [];

        // Collect players from teams
        foreach ($teamIds as $teamId) {
            $teamAbbreviation = idToTeamAbbrev($teamId);
            if (!$teamAbbreviation) {
                continue;
            }

            $teamRosterInfo = getTeamRosterInfo($teamAbbreviation, $season);
            if (!$teamRosterInfo || isset($teamRosterInfo->error)) {
                continue;
            }

            $roster = match($position) {
                'forwards' => $teamRosterInfo->forwards ?? [],
                'defensemen' => $teamRosterInfo->defensemen ?? [],
                'goalies' => $teamRosterInfo->goalies ?? [],
                default => []
            };

            if (!is_array($roster)) {
                continue;
            }

            foreach ($roster as $player) {
                if (!is_object($player)) {
                    continue;
                }

                // Skip if player is already selected
                if (in_array($player->id, $excludePlayerIds)) {
                    continue;
                }

                $player->teamId = $teamId;
                $player->teamAbbrev = $teamAbbreviation;
                $allPlayers[] = $player;
            }
        }

        if (empty($allPlayers)) {
            return ['error' => 'No players found for position: ' . $position];
        }

        // Randomly select up to 3 players that have at least 1 career game played.
        // This check must be enforced regardless of the user's "career stats" filter.
        shuffle($allPlayers);
        $selectedPlayers = [];
        foreach ($allPlayers as $player) {
            if (count($selectedPlayers) >= 3) break;

            $playerId = $player->id ?? 0;

            // Defensive: skip invalid ids
            if (empty($playerId)) continue;

            try {
                $careerStats = getPlayerCareerStats($playerId);
            } catch (Exception $e) {
                // If we can't fetch career stats for a player, skip them
                error_log("Failed fetching career stats for player {$playerId}: " . $e->getMessage());
                continue;
            }

            // Require career stats exist and show at least 1 game played
            $gamesPlayed = null;
            if ($careerStats && isset($careerStats->gamesPlayed)) {
                $gamesPlayed = intval($careerStats->gamesPlayed);
            }

            if ($gamesPlayed !== null && $gamesPlayed >= 1) {
                $selectedPlayers[] = $player;
            } else {
                // Skip players with no career games
                continue;
            }
        }

        // If no eligible players were found, fail gracefully
        if (empty($selectedPlayers)) {
            return ['error' => 'No eligible players found for position: ' . $position . ' (requires >=1 career GP)'];
        }

        // Apply filters and render players
        $playersHtml = [];
        foreach ($selectedPlayers as $player) {
            try {
                $playerHtml = renderDraftPlayer($player, $filters);
                if (!empty($playerHtml)) {
                    $playersHtml[] = $playerHtml;
                }
            } catch (Exception $e) {
                error_log("Error rendering player: " . $e->getMessage());
                continue;
            }
        }

        if (empty($playersHtml)) {
            return ['error' => 'Failed to render any players'];
        }

        return [
            'players' => $playersHtml,
            'position' => $position,
            'round' => $round
        ];

    } catch (Exception $e) {
        return ['error' => 'Exception: ' . $e->getMessage()];
    } catch (Error $e) {
        return ['error' => 'Fatal Error: ' . $e->getMessage()];
    } catch (Throwable $e) {
        return ['error' => 'Throwable: ' . $e->getMessage()];
    }
}

function getRoundPlayers() {
    // Similar to getDraftPlayers but for specific round progression
    return getDraftPlayers();
}

function calculateAge($birthDate) {
    if (empty($birthDate)) return '??';

    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birth)->y;
        return $age;
    } catch (Exception $e) {
        return '??';
    }
}

function getPlayerCareerStats($playerId) {
    if (empty($playerId)) return null;

    try {
        // Use the new NHL API utility
        $ApiUrl = NHLApi::playerLanding($playerId);
        $curl = curlInit($ApiUrl);
        $careerData = json_decode($curl);

        if (!$careerData || !isset($careerData->careerTotals->regularSeason)) {
            return null;
        }

        return $careerData->careerTotals->regularSeason;
    } catch (Exception $e) {
        return null;
    }
}

function renderDraftPlayer($player, $filters = []) {
    try {
        // Safely access player properties with fallbacks
        $teamColor = teamToColor($player->teamId ?? 1);
        $teamLogo = getTeamLogo($player->teamId ?? 1);
        $positionClass = strtolower(positionCodeToName2($player->positionCode ?? 'F'));
        $rookieClass = (isset($player->rookie) && $player->rookie === 'true') ? ' rookie' : '';
        $positionName = positionCodeToName($player->positionCode ?? 'F');

        // Safely access name properties
        $firstName = isset($player->firstName->default) ? $player->firstName->default : '';
        $lastName = isset($player->lastName->default) ? $player->lastName->default : '';
        $fullName = trim($firstName . ' ' . $lastName);

    // Safely access other properties
    $birthCountry = $player->birthCountry ?? '';
    $birthDate = $player->birthDate ?? '';
    $age = calculateAge($birthDate);
    $shoots = $player->shootsCatches ?? '??';
    if ($shoots === 'L') $shoots = 'Left';
    if ($shoots === 'R') $shoots = 'Right';
    $sweaterNumber = $player->sweaterNumber ?? '??';
    $playerId = $player->id ?? 0;
    $headshot = $player->headshot ?? '';
    // Height (cm) and weight (lbs) - used for draft card display
    $heightCm = $player->heightInCentimeters ?? null;
    $weightLbs = $player->weightInPounds ?? null;

    // Convert birth country for flag display
    $playerBirthplace = convertCountryAlphas3To2($birthCountry) ?? null;
    $playerBirthplaceLong = $playerBirthplace ? \Locale::getDisplayRegion('-' . $playerBirthplace, 'en') : $birthCountry;

    // Apply filters to hide information (when filter is active, information is HIDDEN)
    $showHeadshot = !in_array('headshot', $filters);
    $showFullName = !in_array('first_last_name', $filters);
    $showFirstName = !in_array('first_name', $filters);
    $showLastName = !in_array('last_name', $filters);
    $showBirthCountry = !in_array('birth_country', $filters);
    $showTeamInfo = !in_array('team_info', $filters);
    $showTeamLogo = !in_array('team_logo', $filters);
    $showCareerStats = !in_array('career_stats', $filters);
    $showHandedness = !in_array('handedness', $filters);
    $showPosition = !in_array('position', $filters);
    $showAge = !in_array('age', $filters);
    // Toggle display of height & weight in draft cards separately
    $showHeight = !in_array('height', $filters);
    $showWeight = !in_array('weight', $filters);
    $showJerseyNumber = !in_array('jersey_number', $filters);

    // Determine what name to show based on filters
    $displayName = '';

    if (in_array('first_last_name', $filters)) {
        // Hide full name completely
        $displayName = '???';
    } elseif (in_array('first_name', $filters)) {
        // Hide first name, show only last name
        $displayName = !empty($lastName) ? $lastName : '???';
    } elseif (in_array('last_name', $filters)) {
        // Hide last name, show only first name
        $displayName = !empty($firstName) ? $firstName : '???';
    } else {
        // No name filter active, show full name
        $displayName = !empty($fullName) ? $fullName : '???';
    }

    ob_start();
    ?>
    <div class="draft-player clickable <?= $positionClass ?><?= $rookieClass ?>" 
            data-team-id="<?= $player->teamId ?>"
            data-player-id="<?= $playerId ?>"
            data-player-data="<?= htmlspecialchars(json_encode($player)) ?>"
            style="background-image: linear-gradient(142deg, <?= $showTeamInfo ? $teamColor : '#666' ?> -100%, rgba(255,255,255,0) 70%);"
            title="Click to select this player">
        
        <?php if ($showJerseyNumber): ?>
            <div class="jersey"><span>#</span><?= $sweaterNumber ?></div>
        <?php else: ?>
            <div class="jersey"><span>#</span>??</div>
        <?php endif; ?>
        
        <div class="info">
            <?php if ($showHeadshot && !empty($headshot)): ?>
                <div class="headshot <?php if ($showTeamLogo): ?>has-logo<?php endif; ?>" <?php if ($showTeamLogo): ?>style="--team-img: url('<?= $teamLogo ?>');"<?php endif; ?>>
                    <img class="head" loading="lazy" height="200" width="200" src="<?= $headshot ?>" alt="<?= $displayName ?>">
                </div>
            <?php else: ?>
                <div class="headshot <?php if ($showTeamLogo): ?>has-logo<?php endif; ?>" <?php if ($showTeamLogo): ?>style="--team-img: url('<?= $teamLogo ?>');"<?php endif; ?>>
                    <div class="mystery-player">?</div>
                </div>
            <?php endif; ?>
            
            <div class="text">
                <div class="name-wrap">
                    <div class="name"><?= $displayName ?></div>
                </div>
                <div class="info-wrap">
                <?php if ($showPosition): ?>
                    <div class="position tagged"><?= $positionName ?></div>
                <?php else: ?>
                    <div class="position tagged">??</div>
                <?php endif; ?>

                <?php if ($showBirthCountry && !empty($birthCountry)): ?>
                    <div class="country">
                        <?php if ($playerBirthplace): ?>
                            <img class="flag" title="<?= htmlspecialchars($playerBirthplaceLong) ?>" src="<?= BASE_URL ?>/assets/img/flags/<?= $playerBirthplace ?>.svg" height="21" width="28" alt="<?= htmlspecialchars($playerBirthplaceLong) ?> flag" />
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($showHandedness && !empty($shoots)): ?>
                    <div class="shoots tagged" title="Shoots/Catches"><?= $shoots ?></div>
                <?php else: ?>
                    <div class="shoots tagged">??</div>
                <?php endif; ?>

                <?php if ($showAge && $age !== '??'): ?>
                    <div class="age tagged">Age: <?= $age ?></div>
                <?php else: ?>
                    <div class="age tagged">Age: ??</div>
                <?php endif; ?>

                <?php if ($showHeight && !empty($heightCm)): ?>
                    <div class="height tagged"><?= htmlspecialchars(convert_to_inches($heightCm), ENT_QUOTES) ?></div>
                <?php else: ?>
                    <div class="height tagged">??</div>
                <?php endif; ?>
                <?php if ($showWeight && !empty($weightLbs)): ?>
                    <div class="weight tagged"><?= htmlspecialchars($weightLbs, ENT_QUOTES) ?> lbs</div>
                <?php else: ?>
                    <div class="weight tagged">?? lbs</div>
                <?php endif; ?>
                </div>
                
                <?php if ($showCareerStats): ?>
                    <div class="career-stats">
                        <?php 
                        // Get career stats for the player
                        $careerStats = getPlayerCareerStats($playerId);
                        if ($careerStats && !empty($careerStats)): 
                            if ($player->positionCode === 'G'): // Goalie stats
                                $wins = $careerStats->wins ?? 0;
                                $losses = $careerStats->losses ?? 0;
                                $savePct = isset($careerStats->savePctg) ? number_format($careerStats->savePctg, 3) : '0.000';
                                $gaa = isset($careerStats->goalsAgainstAvg) ? number_format($careerStats->goalsAgainstAvg, 2) : '0.00';
                                ?>
                                <div class="stats-grid">
                                    <div class="stat-column">
                                        <div class="stat-header">GP</div>
                                        <div class="stat-value"><?= $careerStats->gamesPlayed ?? 0 ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">W</div>
                                        <div class="stat-value"><?= $wins ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">L</div>
                                        <div class="stat-value"><?= $losses ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">SV%</div>
                                        <div class="stat-value"><?= $savePct ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">GAA</div>
                                        <div class="stat-value"><?= $gaa ?></div>
                                    </div>
                                </div>
                                <?php
                            else: // Skater stats
                                $goals = $careerStats->goals ?? 0;
                                $assists = $careerStats->assists ?? 0;
                                $points = $careerStats->points ?? 0;
                                $games = $careerStats->gamesPlayed ?? 0;
                                ?>
                                <div class="stats-grid">
                                    <div class="stat-column">
                                        <div class="stat-header">GP</div>
                                        <div class="stat-value"><?= $games ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">G</div>
                                        <div class="stat-value"><?= $goals ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">A</div>
                                        <div class="stat-value"><?= $assists ?></div>
                                    </div>
                                    <div class="stat-column">
                                        <div class="stat-header">P</div>
                                        <div class="stat-value"><?= $points ?></div>
                                    </div>
                                </div>
                                <?php
                            endif;
                        else:
                            echo "Career stats unavailable";
                        endif;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();

    } catch (Exception $e) {
        error_log("Error rendering draft player: " . $e->getMessage());
        return '<div class="draft-player-error">Error loading player: ' . $e->getMessage() . '</div>';
    }
}

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
    $nameShort = ($player->firstName->default ? $player->firstName->default[0] . '.' : '') . ' ' . ($player->lastName->default ?? '');
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
                <div class="name"><?= $nameShort ?></div>
            </div>
        </div>
    </a>
    <?php
}
