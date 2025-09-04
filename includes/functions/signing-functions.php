<?php
/**
 * Signing-related functions for NHL Signing Tracker
 */

/**
 * Fetch signing data from the Sportsnet API
 * @return array|null Array of signing data or null on failure
 */
function fetchSigningData() {
    $apiUrl = NHLApi::sportsnetSigningsTracker();
    $cacheFile = 'cache/signings-tracker.json';
    $cacheLifetime = 3600; // 1 hour cache
    
    return fetchData($apiUrl, $cacheFile, $cacheLifetime);
}

/**
 * Render signing player section
 * @param object $signing Signing data
 * @param bool $useShortName Whether to use team short name instead of full name
 * @return string HTML for signing player section
 */
function renderSigningPlayer($signing, $useShortName = false) {
    $html = '<div class="signing-player">';
    
    // Team logo
    $html .= renderSigningTeamLogo($signing);
    
    // Player image
    $html .= '<div class="player-image">';
    if (isset($signing->player_image_headshot) && !empty($signing->player_image_headshot)) {
        $html .= '<img src="' . htmlspecialchars($signing->player_image_headshot) . '" alt="' . htmlspecialchars($signing->name ?? 'Player') . '" onerror="this.src=\'assets/img/no-image.png\'; this.onerror=null;" />';
    } else {
        $html .= '<img src="assets/img/no-image.png" alt="No Image" />';
    }
    $html .= '</div>';
    
    // Player info
    $html .= '<div class="player-info">';
    $html .= '<div class="name">' . htmlspecialchars($signing->name ?? 'Unknown Player') . '</div>';
    $html .= '<div class="details">';
    $html .= '<div class="position">' . htmlspecialchars($signing->player_position ?? 'Position TBD') . '</div>';
    if (isset($signing->age)) {
        $html .= '<div class="age">Age: ' . htmlspecialchars($signing->age) . '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}

/**
 * Render team logo for signing
 * @param object $signing Signing data
 * @return string HTML for team logo
 */
function renderSigningTeamLogo($signing) {
    $html = '<div class="team-logo">';
    
    if (isset($signing->team_shortname) && !empty($signing->team_shortname)) {
        $signingTeamAbbrev = strtoupper($signing->team_shortname);
        
        // Handle special cases for team abbreviations that differ between sources
        $teamAbbrevMap = [
            'CLB' => 'CBJ', // Columbus Blue Jackets
            'PHX' => 'ARI', // Arizona (used to be Phoenix)
        ];
        
        if (isset($teamAbbrevMap[$signingTeamAbbrev])) {
            $signingTeamAbbrev = $teamAbbrevMap[$signingTeamAbbrev];
        }
        
        // Check if the team abbreviation exists in our mapping
        global $teamAbbrev;
        if (isset($teamAbbrev[$signingTeamAbbrev])) {
            $teamId = abbrevToTeamId($signingTeamAbbrev);
            $html .= '<img src="assets/img/teams/' . $teamId . '.svg" height="100" width="100" alt="Team Logo" />';
        } else {
            // Fallback: use the original abbreviation in lowercase for direct file lookup
            $html .= '<img src="assets/img/teams/' . strtolower($signing->team_shortname) . '.svg" height="100" width="100" alt="Team Logo" />';
        }
    } else {
        $html .= '<div class="placeholder-logo">?</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Render contract details for signing
 * @param object $signing Signing data
 * @return string HTML for contract details
 */
function renderContractDetails($signing) {
    $html = '<div class="contract-details">';
    
    if (isset($signing->contract_details) && is_array($signing->contract_details)) {
        $html .= '<ul>';
        foreach ($signing->contract_details as $detail) {
            if (isset($detail->key) && isset($detail->value)) {
                $class = isset($detail->class) ? ' class="' . htmlspecialchars($detail->class) . '"' : '';
                $html .= '<li' . $class . '>';
                $html .= '<span class="detail-key">' . htmlspecialchars($detail->key) . '</span> ';
                $html .= '<span class="detail-value">' . htmlspecialchars($detail->value) . '</span>';
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
    } else {
        $html .= '<div class="no-details">Contract details pending...</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Render a complete signing HTML block
 * @param object $signing Signing data from API
 * @param bool $alternateLayout Whether to use alternate layout
 * @param bool $useShortName Whether to use team short name instead of full name
 * @param int $signingIndex Index of the signing (0-based)
 * @param bool $buttonMode Whether to render as a button (minimal view only) or full signing
 * @return string Complete HTML for signing block
 */
function renderSigning($signing, $alternateLayout = false, $useShortName = false, $signingIndex = 0, $buttonMode = null) {
    // Skip if no signing data
    if (!$signing) {
        return '';
    }
    
    $signingDate = isset($signing->signing_date) ? htmlspecialchars($signing->signing_date) : 'Date TBD';
    
    // Determine rendering mode
    if ($alternateLayout && $buttonMode !== null) {
        // New button/expanded layout
        if ($buttonMode) {
            // Render as button (minimal view)
            $isActive = $signingIndex === 0 ? ' active' : '';
            $signingClass = 'signing alt-layout' . $isActive;
            
            $html = '<div class="' . $signingClass . '" data-signing-index="' . $signingIndex . '" tabindex="0" role="button">';
            $html .= '<div class="date">' . $signingDate . '</div>';
            $html .= '<div class="signing-minimal">';
            
            $playerName = isset($signing->name) ? $signing->name : 'Unknown Player';
            $teamName = isset($signing->team_shortname) ? strtoupper($signing->team_shortname) : 'Team TBD';
            
            $html .= '<div class="signing-summary">';
            $html .= htmlspecialchars($playerName) . ' → ' . htmlspecialchars($teamName);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        } else {
            // Render as expanded view
            $signingClass = 'signing alt-layout expanded';
            
            $html = '<div class="' . $signingClass . '">';
            $html .= '<div class="signing-title">Signing</div>';
            
            $html .= '<div class="signing-content">';
            
            // Player section (includes team logo)
            $html .= renderSigningPlayer($signing, $useShortName);
            
            // Contract details
            $html .= renderContractDetails($signing);
            
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        }
    }
    
    // Standard layout
    $signingClass = 'signing';
    $html = '<div class="' . $signingClass . '">';
    
    $html .= '<div class="signing-content">';
    
    // Player section (includes team logo)
    $html .= renderSigningPlayer($signing, $useShortName);
    
    // Contract details
    $html .= renderContractDetails($signing);
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Render all signings from API data grouped by date
 * @param bool $alternateLayout Whether to use alternate layout
 * @param int $limit Maximum number of signings to display (default: 10)
 * @param bool $useShortName Whether to use team short name instead of full name
 * @return string Complete HTML for all signings
 */
function renderAllSignings($alternateLayout = false, $limit = 10, $useShortName = false) {
    $signingTracker = fetchSigningData();
    $html = '';
    
    if ($signingTracker && is_array($signingTracker)) {
        // Group signings by date
        $signingsByDate = [];
        foreach ($signingTracker as $signing) {
            $date = isset($signing->signing_date) ? $signing->signing_date : 'Date TBD';
            if (!isset($signingsByDate[$date])) {
                $signingsByDate[$date] = [];
            }
            $signingsByDate[$date][] = $signing;
        }
        
        // Sort dates in descending order (newest first)
        uksort($signingsByDate, function($a, $b) {
            $dateA = strtotime($a);
            $dateB = strtotime($b);
            if ($dateA === false) return 1; // Put 'Date TBD' at the end
            if ($dateB === false) return -1;
            return $dateB - $dateA; // Descending order
        });
        
        if ($alternateLayout) {
            // Special layout for frontpage with buttons + expanded view
            $html .= '<div class="signing-buttons">';
            
            $count = 0;
            foreach ($signingsByDate as $date => $signings) {
                foreach ($signings as $index => $signing) {
                    if ($limit > 0 && $count >= $limit) {
                        break 2; // Break out of both loops
                    }
                    
                    $signingHtml = renderSigning($signing, $alternateLayout, $useShortName, $count, true); // true for button mode
                    if (!empty($signingHtml)) {
                        $html .= $signingHtml;
                        $count++;
                    }
                }
            }
            
            $html .= '</div>';
            
            // Add expanded container with first signing
            $html .= '<div class="signing-expanded-container">';
            if (!empty($signingTracker)) {
                $firstSigning = $signingTracker[0];
                $expandedHtml = renderSigning($firstSigning, $alternateLayout, $useShortName, 0, false); // false for expanded mode
                $html .= $expandedHtml;
            }
            $html .= '</div>';
        } else {
            // Standard layout - grouped by date
            $totalCount = 0;
            foreach ($signingsByDate as $date => $signings) {
                // Add date header
                $html .= '<div class="signing-date-group">';
                $html .= '<h3 class="signing-date-header">' . htmlspecialchars($date) . '</h3>';
                
                $dateCount = 0;
                foreach ($signings as $signing) {
                    if ($limit > 0 && $totalCount >= $limit) {
                        break 2; // Break out of both loops
                    }
                    
                    $signingHtml = renderSigning($signing, $alternateLayout, $useShortName, $totalCount);
                    if (!empty($signingHtml)) {
                        $html .= $signingHtml;
                        $totalCount++;
                        $dateCount++;
                    }
                }
                
                $html .= '</div>'; // Close signing-date-group
            }
        }
    } else {
        $html .= '<div class="signing"';
        $html .= '<div class="date">No signings available</div>';
        $html .= '<div class="signing-content">';
        $html .= '<div class="alert info">';
        $html .= 'No signing data available at this time.';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Render just the signing content (for use inside existing .signings container)
 * @param bool $alternateLayout Whether to use alternate layout
 * @param int $limit Maximum number of signings to display (default: 10)
 * @param bool $useShortName Whether to use team short name instead of full name
 * @return string Complete HTML for signing content only
 */
function renderSigningContent($alternateLayout = false, $limit = 10, $useShortName = false) {
    return renderAllSignings($alternateLayout, $limit, $useShortName);
}
?>
