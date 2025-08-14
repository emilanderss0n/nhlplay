<?php
function gameScoringPlays($gameContent) {
    foreach ($gameContent->summary->scoring as $periodData) {
        $periodNumber = $periodData->periodDescriptor->number;
        $periodLabel = ($periodNumber == 4) ? 'Overtime' : (($periodNumber == 5) ? 'Shootout' : 'Period ' . $periodNumber);
        
        $goalsScoredInPeriod = false;
        $goalScorerDivs = [];
    
        if (!empty($periodData->goals)) {
            foreach ($periodData->goals as $scorer) {
                // Extract all the necessary data
                $scorerId = $scorer->playerId;
                $scorerFirstName = $scorer->firstName->default;
                $scorerLastName = $scorer->lastName->default;
                $scorerHeadshot = $scorer->headshot;
                $scorerTeamAbbrev = $scorer->teamAbbrev->default;
                $scorerStrength = $scorer->strength;
                $scorerAwayScore = $scorer->awayScore;
                $scorerHomeScore = $scorer->homeScore;
                $seasonGoals = $scorer->goalsToDate;
                $timeInPeriod = $scorer->timeInPeriod;
                $shotType = isset($scorer->shotType) ? $scorer->shotType : 'N/A';
                $teamGoal = abbrevToTeamId($scorerTeamAbbrev);
                $highlightClip = isset($scorer->highlightClipSharingUrl) ? $scorer->highlightClipSharingUrl : null;

                // Get team logos
                $teamLogo = '';
                $teamDarkLogo = '';
                if ($scorerTeamAbbrev === $gameContent->awayTeam->abbrev) {
                    $teamLogo = $gameContent->awayTeam->logo;
                    $teamDarkLogo = $gameContent->awayTeam->darkLogo;
                } else if ($scorerTeamAbbrev === $gameContent->homeTeam->abbrev) {
                    $teamLogo = $gameContent->homeTeam->logo;
                    $teamDarkLogo = $gameContent->homeTeam->darkLogo;
                }

                // Build the HTML for this goal
                $div = buildScoringPlayHtml(
                    $scorerId, $scorerFirstName, $scorerLastName, $scorerHeadshot,
                    $teamGoal, $highlightClip, $scorerStrength, $seasonGoals,
                    $timeInPeriod, $shotType, $scorerAwayScore, $scorerHomeScore,
                    $scorer->assists, $teamLogo, $teamDarkLogo
                );
        
                $goalsScoredInPeriod = true;
                $goalScorerDivs[] = $div;
            }
        }
        
        if ($goalsScoredInPeriod) {
            echo '<div class="break header-dashed">' . $periodLabel . '</div>';
            foreach ($goalScorerDivs as $goalScorerDiv) {
                echo $goalScorerDiv;
            }
        }
    }
}

function buildScoringPlayHtml($scorerId, $scorerFirstName, $scorerLastName, $scorerHeadshot, $teamGoal, $highlightClip, $scorerStrength, $seasonGoals, $timeInPeriod, $shotType, $scorerAwayScore, $scorerHomeScore, $assists, $teamLogo, $teamDarkLogo) {
    $videoId = '';
    if ($highlightClip) {
        preg_match('/(\d+)$/', $highlightClip, $matches);
        $videoId = $matches[1] ?? '';
    }

    $assistInfo = '';
    foreach ($assists as $assist) {
        $assistName = $assist->firstName->default . ' ' . $assist->lastName->default;
        $assistToDate = $assist->assistsToDate;
        $assistId = $assist->playerId;
        $assistInfo .= "<li><a href='#' id='player-link' data-link='". $assistId ."' class='assist'>$assistName <span>($assistToDate)</span></a></li>";
    }

    $assistDiv = !empty($assistInfo) ? '<div class="assists">'. $assistInfo .'</div>' : '';

    return '<div class="scoring-play">
    <div class="goal">
        <a class="headshot" href="#" id="player-link" data-link="'. $scorerId .'">      
            <svg class="headshot_wrap" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(0.805); z-index: 2;">
                <mask id="circleMask:r2:">
                    <svg>
                        <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                    </svg>
                </mask>
                <image mask="url(#circleMask:r2:)" fill="#000000" id="canTop" height="128" href="'. $scorerHeadshot .'"></image>
            </svg>
            <picture>
            <source srcset="'. $teamDarkLogo .'" media="(prefers-color-scheme: dark)">
            <img class="team-img" src="'. $teamLogo .'" />
            </picture>
            <svg class="team-fill" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(0.8);">
                <circle cx="64" cy="72" r="56" fill="'. teamToColor($teamGoal).'"></circle>
                <defs>
                    <linearGradient id="gradient:r2:" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                        <stop offset="65%" stop-opacity="0.35" stop-color="#000000"></stop>
                    </linearGradient>
                </defs>
                <circle cx="64" cy="72" r="56" fill="url(#gradient:r2:)"></circle>
            </svg>
        </a><!-- END .headshot -->
        <div class="player-info">
            <a href="#" id="player-link" data-link="'. $scorerId .'" class="name">'. $scorerFirstName .' '. $scorerLastName .' <span>('. $seasonGoals .')</span>'. ($scorerStrength == 'ev' ? '' : '<span class="special">'. $scorerStrength .'</span>' ) .'</a>
            <div class="info">
                <div class="highlight">'.
                    ($highlightClip ? '<a class="highlight-link" href="https://players.brightcove.net/6415718365001/EXtG1xJ7H_default/index.html?videoId='. htmlspecialchars($videoId) .'" target="_blank"><i class="bi bi-camera-video"></i></a>' : '') . '
                </div>
                <div class="time">'. $timeInPeriod .' / '. $shotType .' / '. $scorerAwayScore .' - '. $scorerHomeScore .'</div>
            </div>
            '. $assistDiv .'
        </div>
    </div></div>';
}

function gameRosterStats($id, $name, $teamSide, $game) {
    $playerTypes = array();
    $teamId = $id;
    $teamName = $name;

    $playerTypesMapping = [
        'forwards' => 'Forward',
        'defense' => 'Defenseman',
        'goalies' => 'Goalie'
    ];

    foreach ($playerTypesMapping as $playerTypeKey => $playerType) {
        if (isset($game->playerByGameStats->{$teamSide}->{$playerTypeKey})) {
            $players = $game->playerByGameStats->{$teamSide}->{$playerTypeKey};

            foreach ($players as $player) {
                if ($playerTypeKey == 'goalies') {
                    $div = '
                    <tr>
                        <td><a href="#" id="player-link" data-link="'. $player->playerId .'" class="name">'. $player->name->default .'</a></td>
                        <td>'. $player->toi .'</td>
                        <td>'. (isset($player->savePctg) ? number_format((float)$player->savePctg * 100, 2) : '-') .'</td>
                        <td>'. (isset($player->saveShotsAgainst) ? $player->saveShotsAgainst : '-') .'</td>
                    </tr>
                    ';
                } else {
                    $plusMinus = $player->plusMinus;
                    $plusMinusDisplay = $plusMinus > 0 ? '+' . $plusMinus : $plusMinus;

                    $div = '
                    <tr>
                        <td><a href="#" id="player-link" data-link="'. $player->playerId .'" class="name">'. $player->name->default .'</a></td>
                        <td>'. (isset($player->toi) ? $player->toi : '00:00') .'</td>
                        <td>'. ($playerTypeKey !== 'goalies' ? $player->goals : '-') .'</td>
                        <td>'. ($playerTypeKey !== 'goalies' ? $player->assists : '-') .'</td>
                        <td>'. ($playerTypeKey !== 'goalies' ? $plusMinusDisplay : '-') .'</td>
                    </tr>
                    ';
                }

                if (!array_key_exists($playerType, $playerTypes)) {
                    $playerTypes[$playerType] = array();
                } 
                array_push($playerTypes[$playerType], $div); 
            }
        }
    }

    krsort($playerTypes);

    foreach ($playerTypes as $playerType => $divs) {
        $teamLogo = $teamSide === 'awayTeam' ? $game->awayTeam->logo : $game->homeTeam->logo;
        $teamLogoDark = $teamSide === 'awayTeam' ? $game->awayTeam->darkLogo : $game->homeTeam->darkLogo;
        echo '<div class="break header-dashed">
        <picture>
        <source srcset="'. $teamLogoDark .'" media="(prefers-color-scheme: dark)">
        <img src="'. $teamLogo .'" width="64" height="64" />
        </picture>
        <h3>'. $playerType .'s</h3>
        </div>';
        
        if ($playerType == 'Goalie') {
            echo '<table class="boxscore-table">
                <thead>
                    <td>Player</td><td>TOI</td><td>SV%</td><td>Saves / Shots</td>
                </thead>
                <tbody>';
        } else {
            echo '<table class="boxscore-table">
                <thead>
                    <td>Player</td><td>TOI</td><td>Goals</td><td>Assists</td><td>+/-</td>
                </thead>
                <tbody>';
        }

        foreach ($divs as $div) {
            echo $div;
        }

        echo '</tbody></table>';
    }
}

function gameScores($scores) {
    if (!empty($scores->gameWeek)) {
        $reversedScores = array_reverse($scores->gameWeek);
        foreach ($reversedScores as $gameDates) {
            $scoreDates = $gameDates->date;
            $scoresLabel = $scoreDates;
            
            $dateHasGames = false;
            $scoreDiv = [];
        
            if (!empty($gameDates->games)) {
                foreach ($gameDates->games as $result) {
                    if ($result->gameState == 'OFF' || $result->gameState == 'FINAL') {
                        if (isset($result->seriesStatus->round)) {
                            $playoffs = true;
                        }
                        // Get game information
                        $gameID = $result->id;
                        $awayID = $result->awayTeam->id;
                        $homeID = $result->homeTeam->id;
                        $awayTeamLogoLight = $result->awayTeam->logo;
                        $awayTeamLogo = $result->awayTeam->darkLogo;
                        $homeTeamLogoLight = $result->homeTeam->logo;
                        $homeTeamLogo = $result->homeTeam->darkLogo;
                        $awayName = $result->awayTeam->abbrev;
                        $homeName = $result->homeTeam->abbrev;
                        $awayScore = $result->awayTeam->score;
                        $homeScore = $result->homeTeam->score;
                        $gameState = $result->gameState;
                        $time = $result->startTimeUTC;
                        $date = new DateTime($time);
                
                        $div = '<div class="game final scores" data-post-link="'. $gameID .'">
                        <div class="teams" style="
                            background-image: linear-gradient(120deg, 
                            '. teamToColor($awayID) .' -50%,
                            transparent 40%,
                            transparent 60%,
                            '. teamToColor($homeID) .' 150%);
                            ">
                            <div id="team-linko">
                                <picture>
                                    <source srcset="'. $awayTeamLogo .'" media="(prefers-color-scheme: dark)">
                                    <img src="'. $awayTeamLogoLight .'" alt="'. $awayName .'" />
                                </picture>
                            </div>
                            <p><span class="default">'. $awayScore .' - '. $homeScore .'</span></p>
                            <div id="team-linko">
                                <picture>
                                    <source srcset="'. $homeTeamLogo .'" media="(prefers-color-scheme: dark)">
                                    <img src="'. $homeTeamLogoLight .'" alt="'. $homeName .'" />
                                </picture>
                            </div>
                        </div>
                        <div class="more flex-default">
                            <div class="period">'. $result->gameOutcome->lastPeriodType .'</div>
                            '. ( $playoffs ? '<div class="series">'. formatPlayoffSeriesStatus($result->seriesStatus) .'</div>' : '') .'
                        </div>
                        </div>';

                        $dateHasGames = true;
                        $scoreDiv[] = $div;
                    }
                }
            }
            if ($dateHasGames) {
                echo '<div class="break component-header"><h3 class="title">' . $scoresLabel . '</h3></div>';
                foreach ($scoreDiv as $game) {
                    echo $game;
                }
            }
        }
    } else {
        echo '<div class="alert" style="margin-top: 2rem; grid-column: 1/5;">No recent games played</div>';
    }
}

function formatPlayoffSeriesStatus($seriesStatus) {
    $topWins = $seriesStatus->topSeedWins;
    $bottomWins = $seriesStatus->bottomSeedWins;
    $topAbbr = $seriesStatus->topSeedTeamAbbrev;
    $bottomAbbr = $seriesStatus->bottomSeedTeamAbbrev;
    $neededToWin = $seriesStatus->neededToWin;
    
    if ($topWins == $neededToWin) {
        return $topAbbr . ' wins ' . $topWins . '-' . $bottomWins;
    } elseif ($bottomWins == $neededToWin) {
        return $bottomAbbr . ' wins ' . $bottomWins . '-' . $topWins;
    } elseif ($topWins > $bottomWins) {
        return $topAbbr . ' leads ' . $topWins . '-' . $bottomWins;
    } elseif ($bottomWins > $topWins) {
        return $bottomAbbr . ' leads ' . $bottomWins . '-' . $topWins;
    } else {
        return 'Tied ' . $topWins . '-' . $bottomWins;
    }
}

function gameRecaps($schedules) {
    if (isset($schedules->gameWeek)) {
        $reversedGameWeeks = array_reverse($schedules->gameWeek);
        foreach ($reversedGameWeeks as $gameWeek) {
            $gameDateG = $gameWeek->date;
            $recapsLabel = $gameDateG;
            
            $dateHasRecaps = false;
            $recapDiv = [];
            
            foreach ($gameWeek->games as $result) {
                // Get basic game info
                $gameID = $result->id;
                $awayID = $result->awayTeam->id;
                $homeID = $result->homeTeam->id;
                $awayName = $result->awayTeam->abbrev;
                $homeName = $result->homeTeam->abbrev;
                $gameState = $result->gameState;
                
                $gameVideo = '';
                if ($gameState == 'OFF' || $gameState == 'OVER' || $gameState == 'FINAL') {
                    if (isset($result->condensedGame) && !empty($result->condensedGame)) {
                        $gameVideoSource = $result->condensedGame;
                        $videoURLParts = explode("-", $gameVideoSource);
                        $gameVideo = end($videoURLParts);
                    } else {
                        $gameVideoSource = $result->threeMinRecap;
                        $videoURLParts = explode("-", $gameVideoSource);
                        $gameVideo = end($videoURLParts);
                    }
                }
                
                $gameDate = date('Y-m-d', strtotime($gameDateG));
                $time = $result->startTimeUTC;
                $date = new DateTime($time);
                
                if ($gameState == 'OFF') {
                    $div = '<div class="game recap">
                        <div class="watch-recap">
                            <a href="https://players.brightcove.net/6415718365001/EXtG1xJ7H_default/index.html?videoId=' . $gameVideo . '" target="_blank"><i class="bi bi-camera-video"></i> Watch</a>
                        </div>
                        <div class="teams" style="
                            background-image: linear-gradient(120deg, 
                            '. teamToColor($awayID) .' -50%,
                            transparent 40%,
                            transparent 60%,
                            '. teamToColor($homeID) .' 150%);
                            ">
                            <div id="team-linko">
                                <picture>
                                    <source srcset="'. $result->awayTeam->darkLogo .'" media="(prefers-color-scheme: dark)">
                                    <img src="'. $result->awayTeam->logo .'" alt="'. $result->awayTeam->commonName->default .'" />
                                </picture>
                            </div>
                            <p><span class="default">VS</span></p>
                            <div id="team-linko">
                                <picture>
                                    <source srcset="'. $result->homeTeam->darkLogo .'" media="(prefers-color-scheme: dark)">
                                    <img src="'. $result->homeTeam->logo .'" alt="'. $result->homeTeam->commonName->default .'" />
                                </picture>
                            </div>
                        </div>
                        <div class="time">Game Ended: ' . $date->format('Y-m-d') . '</div>
                    </div>';
                    
                    $dateHasRecaps = true;
                    $recapDiv[] = $div;
                }
            }
            
            if ($dateHasRecaps) {
                echo '<div class="break component-header"><h3 class="title">' . $recapsLabel . '</h3></div>';
                foreach ($recapDiv as $recap) {
                    echo $recap;
                }
            }
        }
    } else {
        echo '<div class="alert">No recaps available, try older games below</div>';
    }
}

function gamePenalties($gameContent) {
    foreach ($gameContent->summary->penalties as $periodData) {
        $periodNumber = $periodData->periodDescriptor->number;
        $periodLabel = ($periodNumber == 1) ? 'st' : (($periodNumber == 2) ? 'nd' : 'rd');
        
        if (!empty($periodData->penalties)) {
            foreach ($periodData->penalties as $violator) {
                $violatorName = isset($violator->committedByPlayer) ? $violator->committedByPlayer->default : 'Team Penalty';
                $violationTeam = $violator->teamAbbrev->default ?? '';
                $violationTime = $violator->timeInPeriod ?? '';
                $violationDuration = $violator->duration ?? '';
                $violationType = $violator->descKey ?? '';
                $violationMinMaj = $violator->type ?? '';
                $teamID = $violationTeam ? abbrevToTeamId($violationTeam) : '';
                $teamColor = $teamID ? teamToColor($teamID) : '#000000';

                echo "<div class='penalty item'>";
                echo "<div class='team-logo' style='background-image: url(assets/img/teams/". $teamID .".svg);'></div>";
                echo "<div class='team-fill' style='background: linear-gradient(142deg, ".$teamColor." 0%, var(--dark-bg-color) 80%);: ". $teamColor ."'></div>";
                echo "<div class='content'>";
                echo "<div class='head'>";
                echo "<h3><span>".$violationDuration." ".$violationMinMaj."</span> - <span>".$violatorName."</span></h3>";
                echo "</div>";
                echo "<span>".$periodNumber."".$periodLabel."</span> - <span>".$violationTime."</span> - <span class='type'>".$violationType."</span>";
                echo "</div>";
                echo "</div>";
            }
        }
    }
}

function renderSeasonSeries($seasonSeries) {
    foreach ($seasonSeries as $seriesGame) {
        $gameStateClass = '';
        switch ($seriesGame->gameState) {
            case 'LIVE':
            case 'CRIT':
                $gameStateClass = 'live';
                break;
            case 'FUT':
            case 'PRE':
                $gameStateClass = 'preview';
                break;
            case 'OFF':
            case 'OVER':
            case 'FINAL':
                $gameStateClass = 'final';
                break;
        }
        
        // Check if game is scheduled for today
        $isToday = false;
        $gameDate = new DateTime($seriesGame->gameDate);

        if ($seriesGame->gameState == 'FUT' || $seriesGame->gameState == 'PRE') {
            $today = new DateTime('today');
            $isToday = $gameDate->format('Y-m-d') === $today->format('Y-m-d');
        }
        
        // Add disabled class for today's games
        echo '<div class="game" data-tooltip="Season Series Game">';
        echo '<div class="teams">';
        echo '<span id="team-linko" href="#">';
        echo '<h3>'. $seriesGame->awayTeam->abbrev .'</h3>';
        echo '</span>';
        echo '<p><span class="scoring">';
        
        if ($seriesGame->gameState == 'OFF' || $seriesGame->gameState == 'LIVE' || $seriesGame->gameState == 'CRIT' || $seriesGame->gameState == 'OVER' || $seriesGame->gameState == 'FINAL') {
            echo $seriesGame->awayTeam->score . ' - ' . $seriesGame->homeTeam->score;
        } elseif ($seriesGame->gameState == 'FUT' || $seriesGame->gameState == 'PRE') {
            $today = new DateTime('today');
            $tomorrow = new DateTime('tomorrow');
            
            if ($gameDate->format('Y-m-d') === $today->format('Y-m-d')) {
                echo '<div class="theTime tag">Today</div>';
            } elseif ($gameDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                echo '<div class="theTime tag">Tomorrow</div>';
            } else {
                echo '<div class="theTime tag">' . date('y/m/d', strtotime($seriesGame->gameDate)) . '</div>';
            }
        }
        
        echo '</span></p>';
        echo '<span id="team-linko" href="#">';
        echo '<h3>'. $seriesGame->homeTeam->abbrev .'</h3>';
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }
}

function preGameAdvantage($homeTeamId, $awayTeamId, $homeTeam, $awayTeam, $homeRecord, $awayRecord, $homeLast10, $awayLast10, $seasonSeries, $season) {
    $homePoints = 0;
    $awayPoints = 0;
    $totalPossiblePoints = 17.5; // Adjusted total when last10 might be unavailable

    // Basic Stats (4 points total)
    // Goals For/Against per Game (1 point each)
    if($homeTeam->goalsForPerGamePlayed > $awayTeam->goalsForPerGamePlayed) $homePoints += 1;
    else if($awayTeam->goalsForPerGamePlayed > $homeTeam->goalsForPerGamePlayed) $awayPoints += 1;
    
    if($homeTeam->goalsAgainstPerGamePlayed < $awayTeam->goalsAgainstPerGamePlayed) $homePoints += 1;
    else if($awayTeam->goalsAgainstPerGamePlayed < $homeTeam->goalsAgainstPerGamePlayed) $awayPoints += 1;
    
    // Special Teams (2 points total)
    // PP and PK (0.5 points each, total 1 point)
    if($homeTeam->ppPctg > $awayTeam->ppPctg) $homePoints += 0.5;
    else if($awayTeam->ppPctg > $homeTeam->ppPctg) $awayPoints += 0.5;
    
    if($homeTeam->pkPctg > $awayTeam->pkPctg) $homePoints += 0.5;
    else if($awayTeam->pkPctg > $homeTeam->pkPctg) $awayPoints += 0.5;
    
    // Recent Performance (4 points total)
    // Season record (1.5 points)
    $homeRecParts = explode("-", $homeRecord);
    $awayRecParts = explode("-", $awayRecord);
    $homeWinPct = intval($homeRecParts[0]) / (array_sum($homeRecParts));
    $awayWinPct = intval($awayRecParts[0]) / (array_sum($awayRecParts));
    if($homeWinPct > $awayWinPct) $homePoints += 1.5;
    else if($awayWinPct > $homeWinPct) $awayPoints += 1.5;
    
    // Last 10 games (2.5 points - more weight on recent performance)
    // Only calculate if last10 records are available
    if ($homeLast10 && $awayLast10) {
        $totalPossiblePoints = 20; // Restore original total if last10 is available
        $homeLast10Parts = explode("-", $homeLast10);
        $awayLast10Parts = explode("-", $awayLast10);
        $homeLast10WinPct = intval($homeLast10Parts[0]) / (array_sum($homeLast10Parts));
        $awayLast10WinPct = intval($awayLast10Parts[0]) / (array_sum($awayLast10Parts));
        if($homeLast10WinPct > $awayLast10WinPct) $homePoints += 2.5;
        else if($awayLast10WinPct > $homeLast10WinPct) $awayPoints += 2.5;
    }

    // Get advanced stats from API using new NHL API utility
    $homeConditions = [
        'gameTypeId' => '2',
        'seasonId' => ['<=' => $season, '>=' => $season],
        'teamId' => $homeTeamId
    ];
    $ApiUrl1 = NHLApi::teamStatsApi('percentages', $homeConditions, ['limit' => 1]);
    $curl1 = curlInit($ApiUrl1);
    $homeTeamStats = json_decode($curl1);

    $awayConditions = [
        'gameTypeId' => '2',
        'seasonId' => ['<=' => $season, '>=' => $season],
        'teamId' => $awayTeamId
    ];
    $ApiUrl2 = NHLApi::teamStatsApi('percentages', $awayConditions, ['limit' => 1]);
    $curl2 = curlInit($ApiUrl2);
    $awayTeamStats = json_decode($curl2);

    if (!empty($homeTeamStats->data) && !empty($awayTeamStats->data)) {
        $homeAdvanced = $homeTeamStats->data[0];
        $awayAdvanced = $awayTeamStats->data[0];

        // Possession Metrics (6 points total)
        // SAT% in close games (2 points - most important possession metric)
        if($homeAdvanced->satPctClose > $awayAdvanced->satPctClose) $homePoints += 2;
        else if($awayAdvanced->satPctClose > $homeAdvanced->satPctClose) $awayPoints += 2;

        // USAT% in close games (2 points)
        if($homeAdvanced->usatPctClose > $awayAdvanced->usatPctClose) $homePoints += 2;
        else if($awayAdvanced->usatPctClose > $homeAdvanced->usatPctClose) $awayPoints += 2;

        // Zone Start % at 5v5 (2 points - indicates offensive/defensive deployment)
        if($homeAdvanced->zoneStartPct5v5 > $awayAdvanced->zoneStartPct5v5) $homePoints += 2;
        else if($awayAdvanced->zoneStartPct5v5 > $homeAdvanced->zoneStartPct5v5) $awayPoints += 2;

        // Finishing Ability (6 points total)
        // Shooting + Save % at 5v5 (3 points - key predictor of success)
        if($homeAdvanced->shootingPlusSavePct5v5 > $awayAdvanced->shootingPlusSavePct5v5) $homePoints += 3;
        else if($awayAdvanced->shootingPlusSavePct5v5 > $homeAdvanced->shootingPlusSavePct5v5) $awayPoints += 3;

        // Points Percentage (3 points - overall team success)
        if($homeAdvanced->pointPct > $awayAdvanced->pointPct) $homePoints += 3;
        else if($awayAdvanced->pointPct > $homeAdvanced->pointPct) $awayPoints += 3;
    }
    
    // If neither team has points, give them 50-50
    if ($homePoints == 0 && $awayPoints == 0) {
        return [50, 50];
    }
    
    // Convert to percentages
    $homePercentage = ($homePoints / $totalPossiblePoints) * 100;
    $awayPercentage = ($awayPoints / $totalPossiblePoints) * 100;
    
    // Normalize percentages to total 100%
    $total = $homePercentage + $awayPercentage;
    if ($total > 0) {
        $homePercentage = ($homePercentage / $total) * 100;
        $awayPercentage = ($awayPercentage / $total) * 100;
    }
    
    return [$awayPercentage, $homePercentage];
}