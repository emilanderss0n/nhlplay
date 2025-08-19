<?php

function getDraftPlayers() {
    // Set JSON header immediately
    header('Content-Type: application/json');

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
            echo json_encode(['error' => 'Invalid position']);
            return;
        }

        // Check if globals are available
        if (!isset($teamAbbrev) || !is_array($teamAbbrev)) {
            echo json_encode(['error' => 'Team abbreviation data not available']);
            return;
        }

        if (!isset($season)) {
            echo json_encode(['error' => 'Season data not available']);
            return;
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
            echo json_encode(['error' => 'No players found for position: ' . $position]);
            return;
        }

        // Randomly select 3 players
        shuffle($allPlayers);
        $selectedPlayers = array_slice($allPlayers, 0, 3);

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
            echo json_encode(['error' => 'Failed to render any players']);
            return;
        }

        echo json_encode([
            'success' => true,
            'players' => $playersHtml,
            'position' => $position,
            'round' => $round
        ]);

    } catch (Exception $e) {
        echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
    } catch (Error $e) {
        echo json_encode(['error' => 'Fatal Error: ' . $e->getMessage()]);
    } catch (Throwable $e) {
        echo json_encode(['error' => 'Throwable: ' . $e->getMessage()]);
    }
}

function getRoundPlayers() {
    // Similar to getDraftPlayers but for specific round progression
    getDraftPlayers();
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
        $shoots = $player->shootsCatches ?? '';
        $sweaterNumber = $player->sweaterNumber ?? '??';
        $playerId = $player->id ?? 0;
        $headshot = $player->headshot ?? '';

        // Apply filters to hide information (when filter is active, information is HIDDEN)
        $showHeadshot = !in_array('headshot', $filters);
        $showFullName = !in_array('first_last_name', $filters);
        $showFirstName = !in_array('first_name', $filters);
        $showLastName = !in_array('last_name', $filters);
        $showBirthCountry = !in_array('birth_country', $filters);
        $showTeamInfo = !in_array('team_info', $filters);
        $showCareerStats = !in_array('career_stats', $filters);
        $showHandedness = !in_array('handedness', $filters);
        $showPosition = !in_array('position', $filters);
        $showAge = !in_array('age', $filters);
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
                    <div class="headshot">
                        <img class="head" loading="lazy" height="200" width="200" src="<?= $headshot ?>" alt="<?= $displayName ?>">
                        <?php if ($showTeamInfo): ?>
                            <img class="team-img" loading="lazy" height="600" width="600" src="<?= $teamLogo ?>" alt="Team logo">
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="headshot">
                        <div class="mystery-player">?</div>
                        <?php if ($showTeamInfo): ?>
                            <img class="team-img" loading="lazy" height="600" width="600" src="<?= $teamLogo ?>" alt="Team logo">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="text">
                    <div class="name-wrap">
                        <div class="name"><?= $displayName ?></div>
                    </div>
                    <div class="info-wrap">
                    <?php if ($showPosition): ?>
                        <div class="position"><?= $positionName ?></div>
                    <?php else: ?>
                        <div class="position">???</div>
                    <?php endif; ?>
                    
                    <?php if ($showAge && $age !== '??'): ?>
                        <div class="age">Age: <?= $age ?></div>
                    <?php endif; ?>
                    
                    <?php if ($showBirthCountry && !empty($birthCountry)): ?>
                        <div class="country"><?= $birthCountry ?></div>
                    <?php endif; ?>
                    
                    <?php if ($showHandedness && !empty($shoots)): ?>
                        <div class="shoots"><?= $shoots ?></div>
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
