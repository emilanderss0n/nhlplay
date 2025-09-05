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
 * @return string HTML for signing player section
 */
function renderSigningPlayer($signing) {
    $html = '<div class="signing-player">';
    
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
    $html .= '<div class="team">' . htmlspecialchars($signing->team_shortname ?? '???') . '</div><div class="separator">&#x2022;</div>';
    $html .= '<div class="position">' . htmlspecialchars($signing->player_position ?? 'Position TBD') . '</div><div class="separator">&#x2022;</div>';
    if (isset($signing->age)) {
        $html .= '<div class="age">Age: ' . htmlspecialchars($signing->age) . '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
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
 * @return string Complete HTML for signing block
 */
function renderSigning($signing) {
    // Skip if no signing data
    if (!$signing) {
        return '';
    }
    
    $signingDate = isset($signing->signing_date) ? htmlspecialchars($signing->signing_date) : 'Date TBD';
    
    // Standard layout
    $signingClass = 'signing';
    $html = '<div class="' . $signingClass . '">';
    
    $html .= '<div class="date">' . $signingDate . '</div>';
    
    $html .= '<div class="signing-content">';
    
    // Player section (includes team logo)
    $html .= renderSigningPlayer($signing);
    
    // Contract details
    $html .= renderContractDetails($signing);
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Render all signings from API data grouped by date
 * @param int $limit Maximum number of signings to display (default: 10)
 * @param bool $frontpage Whether this is for frontpage display (no date grouping)
 * @return string Complete HTML for all signings
 */
function renderAllSignings($limit = 10, $frontpage = false) {
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
        
        if ($frontpage) {
            // Frontpage layout - show signings directly without date grouping
            $count = 0;
            foreach ($signingsByDate as $date => $signings) {
                foreach ($signings as $index => $signing) {
                    if ($limit > 0 && $count >= $limit) {
                        break 2; // Break out of both loops
                    }
                    
                    // Render signing with full details directly
                    $signingHtml = renderSigning($signing);
                    if (!empty($signingHtml)) {
                        $html .= $signingHtml;
                        $count++;
                    }
                }
            }
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
                    
                    $signingHtml = renderSigning($signing);
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
        $html .= '<div class="signing">';
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
 * @param int $limit Maximum number of signings to display (default: 10)
 * @param bool $frontpage Whether this is for frontpage display (no date grouping)
 * @return string Complete HTML for signing content only
 */
function renderSigningContent($limit = 10, $frontpage = false) {
    return renderAllSignings($limit, $frontpage);
}
?>
