<?php
/**
 * NHL API Utility Class
 * Streamlines NHL API endpoint management and parameter building
 * 
 * This class provides a centralized way to build NHL API URLs with proper parameter handling
 * and supports all the different NHL API endpoints used throughout the application.
 */
class NHLApi {
    
    // API Base URLs
    const API_WEB_BASE = 'https://api-web.nhle.com/v1';
    const API_STATS_BASE = 'https://api.nhle.com/stats/rest/en';
    const API_LEGACY_BASE = 'https://statsapi.web.nhl.com/api/v1';
    const API_RECORDS_BASE = 'https://records.nhl.com/site/api';
    
    /**
     * Build a URL with query parameters
     * @param string $baseUrl The base URL
     * @param array $params Associative array of parameters
     * @return string Complete URL with parameters
     */
    private static function buildUrl($baseUrl, $params = []) {
        if (empty($params)) {
            return $baseUrl;
        }
        
        $queryString = http_build_query($params);
        $separator = strpos($baseUrl, '?') === false ? '?' : '&';
        return $baseUrl . $separator . $queryString;
    }
    
    /**
     * Build Cayenne expression for stats API
     * @param array $conditions Array of conditions
     * @return string Encoded Cayenne expression
     */
    private static function buildCayenneExp($conditions) {
        $expressions = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Handle operators like >=, <=, etc.
                foreach ($value as $operator => $val) {
                    $expressions[] = "{$field}{$operator}{$val}";
                }
            } else {
                $expressions[] = "{$field}={$value}";
            }
        }
        // Don't urlencode here - let http_build_query handle it
        return implode(' and ', $expressions);
    }
    
    // ==========================================
    // GAME CENTER ENDPOINTS (api-web.nhle.com)
    // ==========================================
    
    /**
     * Get game center landing data
     * @param string $gameId Game ID
     * @return string API URL
     */
    public static function gameCenterLanding($gameId) {
        return self::API_WEB_BASE . "/gamecenter/{$gameId}/landing";
    }
    
    /**
     * Get game center boxscore
     * @param string $gameId Game ID
     * @return string API URL
     */
    public static function gameCenterBoxscore($gameId) {
        return self::API_WEB_BASE . "/gamecenter/{$gameId}/boxscore";
    }
    
    /**
     * Get game center right rail data
     * @param string $gameId Game ID
     * @return string API URL
     */
    public static function gameCenterRightRail($gameId) {
        return self::API_WEB_BASE . "/gamecenter/{$gameId}/right-rail";
    }
    
    // ==========================================
    // SCHEDULE ENDPOINTS
    // ==========================================
    
    /**
     * Get current schedule
     * @return string API URL
     */
    public static function scheduleNow() {
        return self::API_WEB_BASE . '/schedule/now';
    }
    
    /**
     * Get schedule for specific date
     * @param string $date Date in YYYY-MM-DD format
     * @return string API URL
     */
    public static function scheduleByDate($date) {
        return self::API_WEB_BASE . "/schedule/{$date}";
    }
    
    /**
     * Get playoff series schedule
     * @param string $season Season (e.g., "20242025")
     * @param string $seriesLetter Series letter (A, B, C, etc.)
     * @return string API URL
     */
    public static function playoffSeries($season, $seriesLetter) {
        return self::API_WEB_BASE . "/schedule/playoff-series/{$season}/{$seriesLetter}";
    }
    
    // ==========================================
    // STANDINGS ENDPOINTS
    // ==========================================
    
    /**
     * Get current standings
     * example: https://api-web.nhle.com/v1/standings/now
     * @return string API URL
     */
    public static function standingsNow() {
        return self::API_WEB_BASE . '/standings/now';
    }
    
    // ==========================================
    // TEAM ENDPOINTS
    // ==========================================
    
    /**
     * Get team statistics
     * @param string $teamAbbrev Team abbreviation (e.g., "BOS", "TOR")
     * @param string $season Season (e.g., "20242025")
     * @param string $gameType Game type ("2" for regular season, "3" for playoffs)
     * @return string API URL
     */
    public static function teamStats($teamAbbrev, $season, $gameType = '2') {
        return self::API_WEB_BASE . "/club-stats/{$teamAbbrev}/{$season}/{$gameType}/";
    }
    
    /**
     * Get team roster
     * @param string $teamAbbrev Team abbreviation
     * @param string $season Season
     * @return string API URL
     */
    public static function teamRoster($teamAbbrev, $season) {
        return self::API_WEB_BASE . "/roster/{$teamAbbrev}/{$season}";
    }
    
    /**
     * Get team schedule for season
     * @param string $teamAbbrev Team abbreviation
     * @param string $season Season
     * @return string API URL
     */
    public static function teamSchedule($teamAbbrev, $season) {
        return self::API_WEB_BASE . "/club-schedule-season/{$teamAbbrev}/{$season}";
    }

    /**
     * Get team prospects
     * Example: https://api-web.nhle.com/v1/prospects/BOS
     * @param string $teamAbbrev Team abbreviation (e.g., "BOS")
     * @return string API URL
     */
    public static function teamProspects($teamAbbrev) {
        return self::API_WEB_BASE . "/prospects/{$teamAbbrev}";
    }
    
    /**
     * Get team scoreboard
     * @param string $teamAbbrev Team abbreviation
     * @return string API URL
     */
    public static function teamScoreboard($teamAbbrev) {
        return self::API_WEB_BASE . "/scoreboard/{$teamAbbrev}/now";
    }
    
    // ==========================================
    // PLAYER ENDPOINTS
    // ==========================================
    
    /**
     * Get player landing page data
     * @param string $playerId Player ID
     * @return string API URL
     */
    public static function playerLanding($playerId) {
        return self::API_WEB_BASE . "/player/{$playerId}/landing";
    }
    
    /**
     * Get player game log
     * @param string $playerId Player ID
     * @param string $season Season
     * @param string $seasonType Season type ("2" for regular, "3" for playoffs)
     * @return string API URL
     */
    public static function playerGameLog($playerId, $season, $seasonType = '2') {
        return self::API_WEB_BASE . "/player/{$playerId}/game-log/{$season}/{$seasonType}";
    }
    
    // ==========================================
    // STATS LEADERS ENDPOINTS
    // ==========================================
    
    /**
     * Get skater stats leaders
     * @param string $season Season or "current"
     * @param string $gameType Game type ("2" for regular, "3" for playoffs)
     * @param array $categories Categories array (e.g., ["points", "goals"])
     * @return string API URL
     */
    public static function skaterStatsLeaders($season, $gameType = '2', $categories = ['points', 'goals']) {
        $categoriesStr = implode(',', $categories);
        $params = ['categories' => $categoriesStr];
        
    // Always include gameType segment in the path to match records API expectations
    $url = self::API_WEB_BASE . "/skater-stats-leaders/{$season}/{$gameType}";
        
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get goalie stats leaders
     * @param string $season Season or "current"
     * @param string $gameType Game type ("2" for regular, "3" for playoffs)
     * @param array $categories Categories array (e.g., ["goalsAgainstAverage", "wins"])
     * @return string API URL
     */
    public static function goalieStatsLeaders($season, $gameType = '2', $categories = ['goalsAgainstAverage', 'wins']) {
        $categoriesStr = implode(',', $categories);
        $params = ['categories' => $categoriesStr];
        
    // Always include gameType segment in the path to match records API expectations
    $url = self::API_WEB_BASE . "/goalie-stats-leaders/{$season}/{$gameType}";
        
        return self::buildUrl($url, $params);
    }
    
    // ==========================================
    // DRAFT ENDPOINTS
    // ==========================================
    
    /**
     * Get draft rankings
     * @param string $year Draft year
     * @param string $round Round number (1-4)
     * @return string API URL
     */
    public static function draftRankings($year, $round) {
        return self::API_WEB_BASE . "/draft/rankings/{$year}/{$round}";
    }
    
    // ==========================================
    // PLAYOFF BRACKET
    // ==========================================
    
    /**
     * Get playoff bracket
     * @param string $season Season
     * @return string API URL
     */
    public static function playoffBracket($season) {
        return self::API_WEB_BASE . "/playoff-bracket/{$season}";
    }
    
    // ==========================================
    // SEASON ENDPOINTS
    // ==========================================
    
    /**
     * Get draft rankings for current draft
     * @return string API URL
     */
    public static function draftRankingsNow() {
        return self::API_WEB_BASE . '/draft/rankings/now';
    }
    
    /**
     * Get draft picks
     * @param string $year Draft year
     * @param string $round Round number
     * @return string API URL
     */
    public static function draftPicks($year, $round) {
        return self::API_WEB_BASE . "/draft/picks/{$year}/{$round}";
    }
    
    /**
     * Get season information
     * @return string API URL
     */
    public static function season() {
        return self::API_WEB_BASE . '/season';
    }
    
    /**
     * Get streams
     * @return string API URL
     */
    public static function streams() {
        return self::API_WEB_BASE . '/streams';
    }
    
    // ==========================================
    // STATS API ENDPOINTS (api.nhle.com/stats)
    // ==========================================
    
    /**
     * Get season data from stats API
     * @param int $limit Number of seasons to return
     * @param string $sort Sort direction (DESC or ASC)
     * @return string API URL
     */
    public static function seasonsStats($limit = 3, $sort = 'DESC') {
        $sortParam = urlencode('[{"property":"id","direction":"' . $sort . '"}]');
        $params = [
            'sort' => $sortParam,
            'limit' => $limit
        ];
        return self::buildUrl(self::API_STATS_BASE . '/season', $params);
    }

    /**
     * Get component season (active season) from stats API
     * Example: https://api.nhle.com/stats/rest/en/componentSeason
     * @return string API URL
     */
    public static function componentSeason() {
        return self::API_STATS_BASE . '/componentSeason';
    }
    
    /**
     * Get player statistics
     * example url: https://api.nhle.com/stats/rest/en/skater/summary?isAggregate=false&isGame=false&limit=1&cayenneExp=playerId=8478402
     * @param string $playerType "skater" or "goalie"
     * @param string $endpoint Endpoint like "summary", "bios", etc.
     * @param array $conditions Conditions for cayenneExp
     * @param array $params Additional parameters
     * @return string API URL
     */
    public static function playerStats($playerType, $endpoint, $conditions = [], $params = []) {
        $defaultParams = [
            'isAggregate' => 'false',
            'isGame' => 'false',
            'limit' => 1
        ];
        
        $params = array_merge($defaultParams, $params);
        
        if (!empty($conditions)) {
            $params['cayenneExp'] = self::buildCayenneExp($conditions);
        }
        
        $url = self::API_STATS_BASE . "/{$playerType}/{$endpoint}";
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get team statistics from stats API
     * @param string $endpoint Endpoint like "summary", "percentages"
     * @param array $conditions Conditions for cayenneExp
     * @param array $params Additional parameters
     * @return string API URL
     */
    public static function teamStatsApi($endpoint, $conditions = [], $params = []) {
        $defaultParams = [
            'isAggregate' => 'false',
            'isGame' => 'false',
            'limit' => 1
        ];
        
        $params = array_merge($defaultParams, $params);
        
        if (!empty($conditions)) {
            $params['cayenneExp'] = self::buildCayenneExp($conditions);
        }
        
        $url = self::API_STATS_BASE . "/team/{$endpoint}";
        return self::buildUrl($url, $params);
    }

    /**
     * Get team summary from stats API
     * Example: /team/summary?sort=points&cayenneExp=seasonId=20242025%20and%20gameTypeId=2
     * @param string $season Season (e.g., '20242025')
     * @param int $gameTypeId Game type id (1-3). Defaults to 2 (regular season).
     * @param string $sort Sort field (e.g., 'points')
     * @return string API URL
     */
    public static function teamSummary($season, $gameTypeId = 2, $sort = 'points') {
        $gameTypeId = intval($gameTypeId);
        if ($gameTypeId < 1 || $gameTypeId > 3) {
            $gameTypeId = 2;
        }

        $params = [
            'sort' => $sort,
            'cayenneExp' => "seasonId={$season} and gameTypeId={$gameTypeId}"
        ];

        return self::buildUrl(self::API_STATS_BASE . '/team/summary', $params);
    }

    /**
     * Get milestone tracking for skaters (league-wide list).
     * This endpoint does not accept additional query parameters in our usage — it returns
     * a full list of players and their milestone progress.
     * @return string API URL
     */
    public static function milestonesSkaters() {
        return self::API_STATS_BASE . '/milestones/skaters';
    }
    
    /**
     * Get milestone tracking for goalies (league-wide list).
     * This endpoint returns a full list of goalies and their milestone progress.
     * @return string API URL
     */
    public static function milestonesGoalies() {
        return self::API_STATS_BASE . '/milestones/goalies';
    }
    
    /**
     * Get stat leaders from stats API
     * @param string $type "skaters", "goalies", "rookies", "defense"
     * @param string $category Category like "points", "goals", "assists"
     * @param string $season Season
     * @param string $gameType Game type
     * @param int $limit Number of results
     * @return string API URL
     */
    public static function statLeaders($type, $category, $season, $gameType = '2', $limit = null) {
        // Build the base cayenne expression
        $cayenneExp = "season={$season} and gameType={$gameType}";
        
        // Determine the API endpoint type - defense and rookies use 'skaters' endpoint
        $apiType = ($type === 'defense' || $type === 'rookies') ? 'skaters' : $type;
        
        // Add type-specific filters
        if ($type === 'defense') {
            $cayenneExp .= " and player.positionCode='D'";
        } elseif ($type === 'rookies') {
            $cayenneExp .= " and isRookie='Y'";
        } elseif ($type === 'goalies') {
            $cayenneExp .= " and gamesPlayed>=5";
        }
        
        $params = [
            'cayenneExp' => $cayenneExp
        ];
        
        // Only add limit if specified and not null
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        
        $url = self::API_STATS_BASE . "/leaders/{$apiType}/{$category}";
        return self::buildUrl($url, $params);
    }
    
    // ==========================================
    // RECORDS API ENDPOINTS (records.nhl.com)
    // ==========================================
    
    /**
     * Get award details
     * @param string $season Season
     * @param array $includes Fields to include
     * @return string API URL
     */
    public static function awardDetails($season, $includes = []) {
        $defaultIncludes = [
            'seasonId', 'trophy.name', 'trophy.imageUrl', 'player.firstName', 
            'player.lastName', 'player.position', 'player.id', 'value', 
            'team.id', 'team.franchiseId', 'team.fullName', 'team.placeName', 
            'team.commonName', 'team.triCode', 'team.league.abbreviation', 
            'status', 'imageUrl', 'playerImageUrl', 'coach.fullName'
        ];
        
        $includes = empty($includes) ? $defaultIncludes : $includes;
        
        $params = [
            'cayenneExp' => "seasonId={$season}",
            'sort' => 'seasonId',
            'dir' => 'DESC'
        ];

        // Build include parameters as repeated include=... entries (records API expects repeated include params)
        $includeParams = '';
        foreach ($includes as $include) {
            $includeParams .= '&include=' . urlencode($include);
        }

        // Build base URL with the main params, then append the include params string
        $baseUrl = self::buildUrl(self::API_RECORDS_BASE . '/award-details', $params);
        return $baseUrl . $includeParams;
    }
    
    /**
     * Get three star data
     * @param string $season Season
     * @param array $includes Fields to include
     * @return string API URL
     */
    public static function threeStars($season) {
        // Build the exact URL that was working before
        $baseUrl = self::API_RECORDS_BASE . '/media-three-star';
        $includes = [
            'threeStarType',
            'player.fullName', 
            'player.id', 
            'player.sweaterNumber', 
            'player.position', 
            'team.fullName', 
            'team.triCode', 
            'team.id'
        ];
        
        $includeParams = '';
        foreach ($includes as $include) {
            $includeParams .= '&include=' . urlencode($include);
        }
        
        return $baseUrl . '?mapBy=seasonId' . $includeParams . 
               '&cayenneExp=' . urlencode("seasonId=\"{$season}\"") . 
               '&sort=id&dir=ASC';
    }
    
    // ==========================================
    // LEGACY API ENDPOINTS (statsapi.web.nhl.com)
    // ==========================================
    
    /**
     * Get team data from legacy API
     * @param string $teamId Team ID
     * @param array $expands Expand parameters
     * @return string API URL
     */
    public static function legacyTeam($teamId, $expands = []) {
        $params = [];
        foreach ($expands as $expand) {
            $params['expand'] = $expand;
        }
        
        $url = self::API_LEGACY_BASE . "/teams/{$teamId}";
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get team leaders from legacy API
     * @param string $teamId Team ID
     * @param array $params Parameters like leaderCategories, limit, season, expand
     * @return string API URL
     */
    public static function legacyTeamLeaders($teamId, $params = []) {
        $url = self::API_LEGACY_BASE . "/teams/{$teamId}/leaders";
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get schedule from legacy API
     * @param array $params Parameters like teamId, startDate, endDate, expand
     * @return string API URL
     */
    public static function legacySchedule($params = []) {
        $url = self::API_LEGACY_BASE . '/schedule';
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get standings from legacy API
     * @param string $type Standings type (e.g., "byLeague")
     * @param array $expands Expand parameters
     * @return string API URL
     */
    public static function legacyStandings($type = 'byLeague', $expands = []) {
        $params = [];
        foreach ($expands as $expand) {
            $params['expand'] = $expand;
        }
        
        $url = self::API_LEGACY_BASE . "/standings/{$type}/";
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get team leaders from legacy API
     * @param string $teamId Team ID
     * @param array $params Parameters array
     * @return string API URL
     */
    /**
     * Get game content from legacy API
     * @param string $gameId Game ID
     * @return string API URL
     */
    public static function legacyGameContent($gameId) {
        return self::API_LEGACY_BASE . "/game/{$gameId}/content";
    }
    
    // ==========================================
    // CONVENIENCE METHODS
    // ==========================================
    
    /**
     * Helper method to build common Cayenne conditions
     * @param string $gameType Game type ("2" for regular, "3" for playoffs)
     * @param string $season Season
     * @param string $teamId Team ID (optional)
     * @param string $playerId Player ID (optional)
     * @return array Conditions array
     */
    public static function commonConditions($gameType = '2', $season = null, $teamId = null, $playerId = null) {
        $conditions = ['gameTypeId' => $gameType];
        
        if ($season) {
            $conditions['seasonId'] = $season;
        }
        
        if ($teamId) {
            $conditions['teamId'] = $teamId;
        }
        
        if ($playerId) {
            $conditions['playerId'] = $playerId;
        }
        
        return $conditions;
    }
    
    /**
     * Helper method for season range conditions
     * @param string $season Season
     * @param string $comparison Comparison type ('<=' or '>=' or '=')
     * @return array Conditions array for season comparison
     */
    public static function seasonCondition($season, $comparison = '=') {
        if ($comparison === '<=') {
            return ['seasonId' => ['<=' => $season]];
        } elseif ($comparison === '>=') {
            return ['seasonId' => ['>=' => $season]];
        } else {
            return ['seasonId' => $season];
        }
    }
    
    /**
     * Helper method for common sort parameters
     * @param array $sorts Array of sort objects [['property' => 'points', 'direction' => 'DESC']]
     * @return string JSON sort parameter (not URL encoded)
     */
    public static function buildSort($sorts) {
        return json_encode($sorts);
    }
}

// ==========================================
// CONVENIENCE FUNCTIONS (backward compatibility)
// ==========================================

/**
 * Get current NHL API instance (for quick access)
 * @return NHLApi
 */
function nhlApi() {
    return new NHLApi();
}

/**
 * Quick method to get a game center URL
 * @param string $gameId Game ID
 * @param string $type Type: 'landing', 'boxscore', 'right-rail'
 * @return string API URL
 */
function getGameCenterUrl($gameId, $type = 'landing') {
    switch ($type) {
        case 'boxscore':
            return NHLApi::gameCenterBoxscore($gameId);
        case 'right-rail':
            return NHLApi::gameCenterRightRail($gameId);
        default:
            return NHLApi::gameCenterLanding($gameId);
    }
}

/**
 * Quick method to get team stats URL
 * @param string $teamAbbrev Team abbreviation
 * @param string $season Season
 * @param string $gameType Game type
 * @return string API URL
 */
function getTeamStatsUrl($teamAbbrev, $season, $gameType = '2') {
    return NHLApi::teamStats($teamAbbrev, $season, $gameType);
}

/**
 * Quick method to get player URL
 * @param string $playerId Player ID
 * @param string $type Type: 'landing', 'game-log'
 * @param string $season Season (for game-log)
 * @param string $seasonType Season type (for game-log)
 * @return string API URL
 */
function getPlayerUrl($playerId, $type = 'landing', $season = null, $seasonType = '2') {
    if ($type === 'game-log' && $season) {
        return NHLApi::playerGameLog($playerId, $season, $seasonType);
    }
    return NHLApi::playerLanding($playerId);
}

/**
 * Quick method to get milestones skaters URL
 * @param array $conditions Cayenne conditions array
 * @param array $params Additional query params
 * @return string API URL
 */
function getMilestonesSkatersUrl($conditions = [], $params = []) {
    return NHLApi::milestonesSkaters();
}

/**
 * Quick method to get milestones goalies URL
 * @return string API URL
 */
function getMilestonesGoaliesUrl() {
    return NHLApi::milestonesGoalies();
}

/**
 * Quick method to get team summary URL
 * @param string $season Season (e.g., '20242025')
 * @param int $gameTypeId Game type id (1-3)
 * @param string $sort Sort field
 * @return string API URL
 */
function getTeamSummaryUrl($season, $gameTypeId = 2, $sort = 'points') {
    return NHLApi::teamSummary($season, $gameTypeId, $sort);
}

/**
 * Quick method to get component season (active season) URL
 * @return string API URL
 */
function getComponentSeasonUrl() {
    return NHLApi::componentSeason();
}

/**
 * Quick method to get team prospects URL
 * @param string $teamAbbrev Team abbreviation (e.g., 'BOS')
 * @return string API URL
 */
function getTeamProspectsUrl($teamAbbrev) {
    return NHLApi::teamProspects($teamAbbrev);
}
