<?php
/**
 * Trade-related functions for NHL Trade Tracker
 */

/**
 * Fetch trade data from the Sportsnet API
 * @return array|null Array of trade data or null on failure
 */
function fetchTradeData() {
    $ApiUrl = 'https://www.sportsnet.ca/wp-json/sportsnet/v1/trade-tracker';
    $curl = curlInit($ApiUrl);
    return json_decode($curl);
}

/**
 * Parse trade details from the API response
 * @param object $trade The trade object from API
 * @return array Array containing team1 and team2 data
 */
function parseTradeTeams($trade) {
    $team1 = null;
    $team2 = null;

    if (!isset($trade->details)) {
        return ['team1' => null, 'team2' => null];
    }

    if (is_array($trade->details)) {
        $team1 = isset($trade->details[0]) ? $trade->details[0] : null;
        $team2 = isset($trade->details[1]) ? $trade->details[1] : null;
    } elseif (is_object($trade->details)) {
        $team1 = isset($trade->details->{'0'}) ? $trade->details->{'0'} : null;
        $team2 = isset($trade->details->{'1'}) ? $trade->details->{'1'} : null;
    }

    return ['team1' => $team1, 'team2' => $team2];
}

/**
 * Generate background style for trade based on team colors
 * @param object|null $team1 First team data
 * @param object|null $team2 Second team data
 * @return string CSS background style
 */
function generateTradeBackgroundStyle($team1, $team2) {
    $backgroundStyle = "var(--dark-bg-color)";
    
    if ($team1 && isset($team1->team->term_id) && $team2 && isset($team2->team->term_id)) {
        $backgroundStyle = "linear-gradient(120deg, 
            " . teamToColor(teamSNtoID($team1->team->term_id)) . " -100%,
            var(--dark-bg-color) 40%,
            var(--dark-bg-color) 60%,
            " . teamToColor(teamSNtoID($team2->team->term_id)) . " 200%)";
    } elseif ($team1 && isset($team1->team->term_id)) {
        $backgroundStyle = "linear-gradient(120deg, 
            " . teamToColor(teamSNtoID($team1->team->term_id)) . " 0%,
            var(--dark-bg-color) 100%)";
    } elseif ($team2 && isset($team2->team->term_id)) {
        $backgroundStyle = "linear-gradient(120deg, 
            var(--dark-bg-color) 0%,
            " . teamToColor(teamSNtoID($team2->team->term_id)) . " 100%)";
    }
    
    return $backgroundStyle;
}

/**
 * Render team logo HTML
 * @param object|null $team Team data
 * @param string $position Position class (team-logo-1 or team-logo-2)
 * @param bool $alternateLayout Whether to use alternate layout (logo inside team-info)
 * @return string HTML for team logo
 */
function renderTeamLogo($team, $position, $alternateLayout = false) {
    $html = '<div class="team-logo ' . $position . '">';
    
    if ($team && isset($team->team->term_id)) {
        $html .= '<img src="assets/img/teams/' . teamSNtoID($team->team->term_id) . '.svg" alt="" />';
    } else {
        $html .= '<div style="width: 40px; height: 40px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">?</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Render team acquisition list
 * @param object|null $team Team data
 * @return string HTML for acquisition list
 */
function renderTeamAcquisitions($team) {
    $html = '<ul>';
    
    if ($team && isset($team->acquires) && is_array($team->acquires)) {
        foreach ($team->acquires as $asset) {
            if (!isset($asset->name)) {
                $html .= '<li>' . htmlspecialchars($asset) . '</li>';
            } else {
                $html .= '<li>' . htmlspecialchars($asset->name) . '</li>';
            }
        }
    } else {
        $html .= '<li style="color: #999; font-style: italic;">Details pending...</li>';
    }
    
    $html .= '</ul>';
    return $html;
}

/**
 * Render a complete team section
 * @param object|null $team Team data
 * @param string $side 'left' or 'right' for positioning
 * @param bool $alternateLayout Whether to use alternate layout (logo inside team-info)
 * @param bool $useShortName Whether to use team short name instead of full name
 * @return string HTML for complete team section
 */
function renderTeamSection($team, $side = 'left', $alternateLayout = false, $useShortName = false) {
    $teamClass = $side === 'left' ? 'team-1' : 'team-2';
    $logoClass = $side === 'left' ? 'team-logo-1' : 'team-logo-2';
    
    $html = '';
    
    // Standard layout: logo outside team div
    if (!$alternateLayout && $side === 'left') {
        $html .= renderTeamLogo($team, $logoClass, $alternateLayout);
    }
    
    $html .= '<div class="team ' . $teamClass . '">';
    $html .= '<div class="team-info">';
    
    // Alternate layout: logo inside team-info
    if ($alternateLayout) {
        $html .= renderTeamLogo($team, $logoClass, $alternateLayout);
    }
    
    // Determine team name to display
    $teamName = 'Team TBD';
    if ($team && isset($team->team)) {
        if ($useShortName && isset($team->team->team_shortname)) {
            $teamName = $team->team->team_shortname;
        } elseif (isset($team->team->name)) {
            $teamName = $team->team->name;
        }
    }
    
    $html .= '<div class="name">' . htmlspecialchars($teamName) . '</div>';
    $html .= '<div class="text">Acquire</div>';
    $html .= '</div>';
    $html .= '<div class="list">';
    $html .= renderTeamAcquisitions($team);
    $html .= '</div>';
    $html .= '</div>';
    
    // Standard layout: logo outside team div (right side)
    if (!$alternateLayout && $side === 'right') {
        $html .= renderTeamLogo($team, $logoClass, $alternateLayout);
    }
    
    return $html;
}

/**
 * Render a complete trade HTML block
 * @param object $trade Trade data from API
 * @param bool $alternateLayout Whether to use alternate layout (logo inside team-info)
 * @param bool $useShortName Whether to use team short name instead of full name
 * @param int $tradeIndex Index of the trade (0-based), used to determine if first trade when alternateLayout is true
 * @param bool $buttonMode Whether to render as a button (minimal view only) or full trade
 * @return string Complete HTML for trade block
 */
function renderTrade($trade, $alternateLayout = false, $useShortName = false, $tradeIndex = 0, $buttonMode = null) {
    $teams = parseTradeTeams($trade);
    $team1 = $teams['team1'];
    $team2 = $teams['team2'];
    
    // Skip if no teams found
    if (!$team1 && !$team2) {
        return '';
    }
    
    $backgroundStyle = generateTradeBackgroundStyle($team1, $team2);
    $tradeDate = isset($trade->trade_date) ? htmlspecialchars($trade->trade_date) : 'Date TBD';
    
    // Determine rendering mode
    if ($alternateLayout && $buttonMode !== null) {
        // New button/expanded layout
        if ($buttonMode) {
            // Render as button (minimal view)
            $isActive = $tradeIndex === 0 ? ' active' : '';
            $tradeClass = 'trade alt-layout' . $isActive;
            
            $html = '<div class="' . $tradeClass . '" style="background: ' . $backgroundStyle . ';" data-trade-index="' . $tradeIndex . '">';
            $html .= '<div class="date">' . $tradeDate . '</div>';
            $html .= '<div class="teams-minimal">';
            
            // Get team names for minimal view
            $team1Name = 'Team TBD';
            $team2Name = 'Team TBD';
            
            if ($team1 && isset($team1->team)) {
                if ($useShortName && isset($team1->team->team_shortname)) {
                    $team1Name = $team1->team->team_shortname;
                } elseif (isset($team1->team->name)) {
                    $team1Name = $team1->team->name;
                }
            }
            
            if ($team2 && isset($team2->team)) {
                if ($useShortName && isset($team2->team->team_shortname)) {
                    $team2Name = $team2->team->team_shortname;
                } elseif (isset($team2->team->name)) {
                    $team2Name = $team2->team->name;
                }
            }
            
            $html .= '<div class="trade-summary">';
            $html .= htmlspecialchars($team1Name) . ' ↔ ' . htmlspecialchars($team2Name);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        } else {
            // Render as expanded view
            $tradeClass = 'trade alt-layout expanded';
            
            $html = '<div class="' . $tradeClass . '" style="background: ' . $backgroundStyle . ';">';
            $html .= '<div class="trade-title">Trade</div>';
            
            // Show incomplete trade warning if only one team
            if (!$team2) {
                $html .= '<div class="trade-status" style="color: #ffa500; font-size: 0.9em; margin-bottom: 10px;">';
                $html .= '⚠️ Incomplete Trade Information';
                $html .= '</div>';
            }
            
            $html .= '<div class="teams">';
            
            // Render team sections
            if ($team1) {
                $html .= renderTeamSection($team1, 'left', $alternateLayout, $useShortName);
            } else {
                $html .= renderTeamSection(null, 'left', $alternateLayout, $useShortName);
            }
            
            if ($team2) {
                $html .= renderTeamSection($team2, 'right', $alternateLayout, $useShortName);
            } else {
                $html .= renderTeamSection(null, 'right', $alternateLayout, $useShortName);
            }
            
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        }
    }
    
    // Original logic for backward compatibility
    // Determine if this trade should be expanded (only first trade when alternateLayout is true)
    $isExpanded = !$alternateLayout || $tradeIndex === 0;
    $expandedClass = $isExpanded ? ' expanded' : '';
    
    $tradeClass = $alternateLayout ? 'trade alt-layout' . $expandedClass : 'trade';
    $html = '<div class="' . $tradeClass . '" style="background: ' . $backgroundStyle . ';">';
    $html .= '<div class="date">' . $tradeDate . '</div>';
    
    // For alternate layout, show both minimal and full details
    if ($alternateLayout) {
        // Get team names for minimal view
        $team1Name = 'Team TBD';
        $team2Name = 'Team TBD';
        
        if ($team1 && isset($team1->team)) {
            if ($useShortName && isset($team1->team->team_shortname)) {
                $team1Name = $team1->team->team_shortname;
            } elseif (isset($team1->team->name)) {
                $team1Name = $team1->team->name;
            }
        }
        
        if ($team2 && isset($team2->team)) {
            if ($useShortName && isset($team2->team->team_shortname)) {
                $team2Name = $team2->team->team_shortname;
            } elseif (isset($team2->team->name)) {
                $team2Name = $team2->team->name;
            }
        }
        
        // Minimal view (always present for CSS to show/hide)
        $html .= '<div class="teams-minimal">';
        $html .= '<div class="trade-summary">';
        $html .= htmlspecialchars($team1Name) . ' ↔ ' . htmlspecialchars($team2Name);
        $html .= '</div>';
        $html .= '</div>';
        
        // Full details view (always present for CSS to show/hide)
        $html .= '<div class="teams-full">';
        
        // Show incomplete trade warning if only one team
        if (!$team2) {
            $html .= '<div class="trade-status" style="color: #ffa500; font-size: 0.9em; margin-bottom: 10px;">';
            $html .= '⚠️ Incomplete Trade Information';
            $html .= '</div>';
        }
        
        $html .= '<div class="teams">';
        
        // Render team sections
        if ($team1) {
            $html .= renderTeamSection($team1, 'left', $alternateLayout, $useShortName);
        } else {
            $html .= renderTeamSection(null, 'left', $alternateLayout, $useShortName);
        }
        
        if ($team2) {
            $html .= renderTeamSection($team2, 'right', $alternateLayout, $useShortName);
        } else {
            $html .= renderTeamSection(null, 'right', $alternateLayout, $useShortName);
        }
        
        $html .= '</div>';
        $html .= '</div>';
    } else {
        // Standard layout - show full trade details only
        
        // Show incomplete trade warning if only one team
        if (!$team2) {
            $html .= '<div class="trade-status" style="color: #ffa500; font-size: 0.9em; margin-bottom: 10px;">';
            $html .= '⚠️ Incomplete Trade Information';
            $html .= '</div>';
        }
        
        $html .= '<div class="teams">';
        
        // Render team sections
        if ($team1) {
            $html .= renderTeamSection($team1, 'left', $alternateLayout, $useShortName);
        } else {
            $html .= renderTeamSection(null, 'left', $alternateLayout, $useShortName);
        }
        
        if ($team2) {
            $html .= renderTeamSection($team2, 'right', $alternateLayout, $useShortName);
        } else {
            $html .= renderTeamSection(null, 'right', $alternateLayout, $useShortName);
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render all trades from API data
 * @param bool $alternateLayout Whether to use alternate layout (logo inside team-info)
 * @param int $limit Maximum number of trades to display (default: 10)
 * @param bool $useShortName Whether to use team short name instead of full name
 * @return string Complete HTML for all trades
 */
function renderAllTrades($alternateLayout = false, $limit = 10, $useShortName = false) {
    $tradeTracker = fetchTradeData();
    $html = '';
    
    if ($tradeTracker && is_array($tradeTracker)) {
        if ($alternateLayout) {
            // Special layout for frontpage with buttons + expanded view
            $html .= '<div class="trade-buttons">';
            
            $count = 0;
            foreach ($tradeTracker as $index => $trade) {
                if ($limit > 0 && $count >= $limit) {
                    break;
                }
                
                $tradeHtml = renderTrade($trade, $alternateLayout, $useShortName, $count, true); // true for button mode
                if (!empty($tradeHtml)) {
                    $html .= $tradeHtml;
                    $count++;
                }
            }
            
            $html .= '</div>';
            
            // Add expanded container with first trade
            $html .= '<div class="trade-expanded-container">';
            if (!empty($tradeTracker)) {
                $firstTrade = $tradeTracker[0];
                $expandedHtml = renderTrade($firstTrade, $alternateLayout, $useShortName, 0, false); // false for expanded mode
                $html .= $expandedHtml;
            }
            $html .= '</div>';
        } else {
            // Standard layout
            $count = 0;
            foreach ($tradeTracker as $index => $trade) {
                if ($limit > 0 && $count >= $limit) {
                    break;
                }
                
                $tradeHtml = renderTrade($trade, $alternateLayout, $useShortName, $count);
                if (!empty($tradeHtml)) {
                    $html .= $tradeHtml;
                    $count++;
                }
            }
        }
    } else {
        $html .= '<div class="trade" style="background: var(--dark-bg-color);">';
        $html .= '<div class="date">No trades available</div>';
        $html .= '<div class="teams">';
        $html .= '<div style="text-align: center; color: #999; padding: 20px;">';
        $html .= 'No trade data available at this time.';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Render just the trade content (for use inside existing .trades container)
 * @param bool $alternateLayout Whether to use alternate layout (logo inside team-info)
 * @param int $limit Maximum number of trades to display (default: 10)
 * @param bool $useShortName Whether to use team short name instead of full name
 * @return string Complete HTML for trade content only
 */
function renderTradeContent($alternateLayout = false, $limit = 10, $useShortName = false) {
    return renderAllTrades($alternateLayout, $limit, $useShortName);
}
?>
