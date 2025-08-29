<?php
// Page detection and routing logic for NHL PLAY application
// Handles URL parsing, page type detection, and context extraction

// Ensure BASE_URL is available (defined in path.php)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../path.php';
}

// Include team functions for validation
require_once __DIR__ . '/functions/team-functions.php';
require_once __DIR__ . '/functions/player-slug-functions.php';

/**
 * PageDetector class for robust page and context detection
 * Handles URL parsing, page type identification, and context extraction
 * for proper routing and SEO configuration
 */
class PageDetector {
    
    /**
     * Main method to detect current page type and extract context
     * @return array Array containing 'page' type and 'context' data
     */
    public static function detectPageAndContext() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $queryParams = $_GET;
        
        // Remove base path and clean URL
        $basePath = dirname($scriptName);
        if ($basePath !== '/') {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        $requestUri = trim($requestUri, '/');
        $pathParts = explode('/', $requestUri);
        
        // Remove query string
        $requestUri = explode('?', $requestUri)[0];
        
        $context = [];
        $pageType = 'home';
        
        // Check for direct file access patterns
        $currentScript = basename($_SERVER['SCRIPT_NAME'], '.php');
        
        // Team abbreviation pattern (3 uppercase letters) - prioritize this
        if (isset($pathParts[0]) && preg_match('/^[A-Z]{3}$/', $pathParts[0])) {
            $teamAbbr = $pathParts[0];
            // Validate it's a real team abbreviation using existing function
            if (function_exists('abbrevToTeamId')) {
                $teamId = abbrevToTeamId($teamAbbr);
                if ($teamId) {
                    $pageType = 'team';
                    $context['team_abbr'] = $teamAbbr;
                    $context['team_id'] = $teamId;
                }
            } else {
                // Fallback if function not available
                $pageType = 'team';
                $context['team_abbr'] = $teamAbbr;
            }
        }
        // Direct parameter detection
        elseif (isset($queryParams['team_abbr'])) {
            $pageType = 'team';
            $context['team_abbr'] = $queryParams['team_abbr'];
            if (function_exists('abbrevToTeamId')) {
                $context['team_id'] = abbrevToTeamId($queryParams['team_abbr']);
            }
        }
        elseif (isset($queryParams['active_team'])) {
            $pageType = 'team';
            $context['team_id'] = $queryParams['active_team'];
        }
        elseif (isset($queryParams['gameId'])) {
            $pageType = 'game';
            $context['game_id'] = $queryParams['gameId'];
            $context['game_type'] = self::detectGameType($queryParams);
        }
        elseif (isset($queryParams['playerId'])) {
            $pageType = 'player';
            $context['player_id'] = $queryParams['playerId'];
        }
        // Script-based detection (matches ajax file names)
        elseif (in_array($currentScript, ['live-game', 'post-game', 'pre-game'])) {
            $pageType = str_replace('-', '-', $currentScript);
            // Extract game context if available
            if (isset($queryParams['gameId'])) {
                $context['game_id'] = $queryParams['gameId'];
            }
        }
        elseif ($currentScript === 'scores') {
            $pageType = 'scores';
        }
        elseif (in_array($currentScript, ['stat-leaders', 'standings-league', 'standings-divisions', 'standings-conference'])) {
            $pageType = str_contains($currentScript, 'standings') ? 'standings' : 'stat-leaders';
        }
        elseif ($currentScript === 'team-builder') {
            $pageType = 'team-builder';
        }
        elseif ($currentScript === 'draft') {
            $pageType = 'draft';
        }
        elseif ($currentScript === 'trades') {
            $pageType = 'trades';
        }
        elseif ($currentScript === 'recaps') {
            $pageType = 'recaps';
        }
        elseif ($currentScript === 'team-view') {
            $pageType = 'team';
            // Extract team context
            if (isset($queryParams['team_abbr'])) {
                $context['team_abbr'] = $queryParams['team_abbr'];
                if (function_exists('abbrevToTeamId')) {
                    $context['team_id'] = abbrevToTeamId($queryParams['team_abbr']);
                }
            } elseif (isset($queryParams['active_team'])) {
                $context['team_id'] = $queryParams['active_team'];
            }
        }
        elseif ($currentScript === 'player-view') {
            $pageType = 'player';
            if (isset($queryParams['playerId'])) {
                $context['player_id'] = $queryParams['playerId'];
            }
        }
        // Path-based detection (for clean URLs via .htaccess)
        elseif (!empty($pathParts[0])) {
            switch ($pathParts[0]) {
                case 'scores':
                    $pageType = 'scores';
                    break;
                case 'stat-leaders':
                case 'stats':
                    $pageType = 'stat-leaders';
                    break;
                case 'standings':
                    $pageType = 'standings';
                    break;
                case 'team-builder':
                    $pageType = 'team-builder';
                    break;
                case 'draft':
                    $pageType = 'draft';
                    break;
                case 'trades':
                    $pageType = 'trades';
                    break;
                case 'recaps':
                    $pageType = 'recaps';
                    break;
                case 'game':
                    if (isset($pathParts[1])) {
                        $pageType = 'game';
                        $context['game_id'] = $pathParts[1];
                        $context['game_type'] = 'live'; // default, can be refined
                    }
                    break;
                case 'player':
                    if (isset($pathParts[1])) {
                        $pageType = 'player';
                        // Check if it's a player slug (format: firstname-lastname-playerid) or just a player ID
                        if (is_numeric($pathParts[1])) {
                            $context['player_id'] = $pathParts[1];
                        } else {
                            // Treat as player slug
                            $context['player_slug'] = $pathParts[1];
                        }
                    }
                    break;
                case 'team':
                    if (isset($pathParts[1])) {
                        $pageType = 'team';
                        $context['team_abbr'] = $pathParts[1];
                        if (function_exists('abbrevToTeamId')) {
                            $context['team_id'] = abbrevToTeamId($pathParts[1]);
                        }
                    }
                    break;
            }
        }
        
        return ['page' => $pageType, 'context' => $context];
    }
    
    /**
     * Detect the specific type of game page (pre-game, live, post-game)
     * @param array $params Query parameters
     * @return string Game type
     */
    private static function detectGameType($params) {
        // Enhanced game type detection based on script and parameters
        $script = basename($_SERVER['SCRIPT_NAME'], '.php');
        
        if ($script === 'pre-game') {
            return 'pre-game';
        } elseif ($script === 'post-game') {
            return 'post-game';
        } elseif ($script === 'live-game') {
            return 'live';
        } elseif (isset($params['type'])) {
            return $params['type'];
        }
        
        // Default to live for now
        return 'live';
    }
    
    /**
     * Get the current page type for use in frontend JavaScript
     * This method can be used to determine which JS modules to load
     * @return string Page type identifier
     */
    public static function getPageType() {
        $detection = self::detectPageAndContext();
        return $detection['page'];
    }
    
    /**
     * Get the current page context for use in frontend JavaScript
     * @return array Context data
     */
    public static function getPageContext() {
        $detection = self::detectPageAndContext();
        return $detection['context'];
    }
    
    /**
     * Check if current page matches a specific type
     * @param string $pageType Page type to check
     * @return bool True if current page matches the type
     */
    public static function isPageType($pageType) {
        return self::getPageType() === $pageType;
    }
    
    /**
     * Get required JavaScript modules for the current page
     * This helps with dynamic module loading in the frontend
     * @return array Array of module names to load
     */
    public static function getRequiredModules() {
        $pageType = self::getPageType();
        
        // Base modules that are always loaded
        $modules = ['utils', 'dom-elements'];
        
        // Page-specific modules based on page type
        switch ($pageType) {
            case 'home':
                $modules = array_merge($modules, ['live-games', 'scores']);
                break;
            case 'scores':
                $modules = array_merge($modules, ['live-games', 'scores']);
                break;
            case 'team':
                $modules = array_merge($modules, ['team-handlers', 'live-games']);
                break;
            case 'player':
                $modules = array_merge($modules, ['player-handlers']);
                break;
            case 'game':
            case 'live-game':
            case 'pre-game':
            case 'post-game':
                $modules = array_merge($modules, ['game-handlers', 'live-games']);
                break;
            case 'team-builder':
                $modules = array_merge($modules, ['teambuilder']);
                break;
            case 'stat-leaders':
                $modules = array_merge($modules, ['stat-leaders']);
                break;
            case 'standings':
                $modules = array_merge($modules, ['standings']);
                break;
            case 'draft':
                $modules = array_merge($modules, ['draft-handlers']);
                break;
            case 'trades':
                $modules = array_merge($modules, ['trades-handlers']);
                break;
            case 'recaps':
                $modules = array_merge($modules, ['recaps-handlers']);
                break;
        }
        
        return $modules;
    }
}
?>
