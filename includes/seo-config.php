<?php
// SEO configuration and dynamic meta tag generation

// Ensure BASE_URL is available (defined in path.php)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../path.php';
}

// Include existing team data instead of duplicating it
require_once __DIR__ . '/data/team-data.php';
require_once __DIR__ . '/functions/team-functions.php';

// PageDetector class for robust page and context detection
class PageDetector {
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
                        $context['player_id'] = $pathParts[1];
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
}

class SEOConfig {
    private static $pageConfigs = [
        'home' => [
            'title' => 'NHL PLAY - Live NHL Scores, Stats & Team Analysis',
            'description' => 'The ultimate NHL hub! Get live scores, player stats, team analysis, trade tracker, and comprehensive hockey statistics. Follow your favorite NHL teams and players.',
            'keywords' => 'NHL scores, hockey stats, NHL teams, player statistics, live games, hockey analysis, NHL standings, trade tracker',
            'canonical' => '/',
            'type' => 'website'
        ],
        'scores' => [
            'title' => 'NHL Scores Today - Live Game Results & Schedule',
            'description' => 'Get real-time NHL scores, game schedules, and live updates. Track all 32 NHL teams with detailed game information and playoff standings.',
            'keywords' => 'NHL scores today, hockey scores, NHL schedule, live games, game results, hockey standings',
            'canonical' => '/scores'
        ],
        'stat-leaders' => [
            'title' => 'NHL Stat Leaders - Top Player Statistics & Rankings',
            'description' => 'Discover NHL stat leaders across all categories. Compare top players in goals, assists, points, saves, and advanced analytics for skaters, defense, and goalies.',
            'keywords' => 'NHL stat leaders, hockey statistics, player rankings, goals leaders, assists leaders, points leaders, goalie stats',
            'canonical' => '/stat-leaders'
        ],
        'standings' => [
            'title' => 'NHL Standings - Current League, Division & Conference Rankings',
            'description' => 'View current NHL standings by division, conference, and league-wide. Track playoff positions, points, wins, losses, and standings trends.',
            'keywords' => 'NHL standings, hockey standings, division standings, conference rankings, playoff race, league standings',
            'canonical' => '/standings'
        ],
        'team-builder' => [
            'title' => 'NHL Team Builder - Create Your Dream Hockey Lineup',
            'description' => 'Build your ultimate NHL team with our interactive team builder. Drag and drop players, create line combinations, and analyze your dream roster.',
            'keywords' => 'NHL team builder, hockey lineup, fantasy hockey, team creator, line combinations, roster builder',
            'canonical' => '/team-builder'
        ],
        'draft' => [
            'title' => 'NHL Draft Center - Prospects, Rankings & Analysis',
            'description' => 'Complete NHL draft coverage including prospect rankings, draft analysis, player profiles, and historical draft data.',
            'keywords' => 'NHL draft, hockey prospects, draft rankings, player prospects, draft analysis, future stars',
            'canonical' => '/draft'
        ],
        'trades' => [
            'title' => 'NHL Trades - Latest Trade News & Transaction Tracker',
            'description' => 'Stay updated on all NHL trades and transactions. Track the latest player moves, trade analysis, and roster changes across all 32 teams.',
            'keywords' => 'NHL trades, hockey trades, player transactions, trade tracker, roster moves, NHL news',
            'canonical' => '/trades'
        ],
        'recaps' => [
            'title' => 'NHL Game Recaps - Highlights & Post-Game Analysis',
            'description' => 'Read comprehensive NHL game recaps with highlights, key plays, player performances, and post-game analysis for all completed games.',
            'keywords' => 'NHL game recaps, hockey highlights, game analysis, post-game recap, NHL news, game summaries',
            'canonical' => '/recaps'
        ]
    ];

    public static function getPageSEO($page, $context = []) {
        // Handle team pages
        if (isset($context['team_abbr']) || isset($context['team_id'])) {
            return self::getTeamPageSEO($context);
        }

        // Handle player pages
        if (isset($context['player_id'])) {
            return self::getPlayerPageSEO($context);
        }

        // Handle game pages
        if (isset($context['game_id'])) {
            return self::getGamePageSEO($context);
        }

        // Return default page config
        return self::$pageConfigs[$page] ?? self::$pageConfigs['home'];
    }

    private static function getTeamPageSEO($context) {
        global $teamNames;
        
        $teamAbbr = $context['team_abbr'] ?? '';
        $teamId = $context['team_id'] ?? null;
        
        // Get team name from ID if we have it, otherwise use abbreviation
        if ($teamId && isset($teamNames[$teamId])) {
            $teamName = $teamNames[$teamId];
        } elseif ($teamAbbr && function_exists('abbrevToTeamId')) {
            $teamId = abbrevToTeamId($teamAbbr);
            $teamName = $teamId ? ($teamNames[$teamId] ?? $teamAbbr) : $teamAbbr;
        } else {
            $teamName = $teamAbbr;
        }
        
        return [
            'title' => "{$teamName} - NHL Team Stats, Roster & Schedule | NHL PLAY",
            'description' => "Complete {$teamName} coverage including player stats, team roster, game schedule, recent performance, and in-depth analysis. Follow the {$teamName} on NHL PLAY.",
            'keywords' => "{$teamName}, {$teamAbbr}, NHL team, hockey roster, player stats, game schedule, team analysis, NHL standings",
            'canonical' => $teamAbbr ? "/{$teamAbbr}" : "/team/{$teamId}",
            'type' => 'website'
        ];
    }

    private static function getPlayerPageSEO($context) {
        $playerId = $context['player_id'];
        $playerName = $context['player_name'] ?? "Player #{$playerId}";
        
        return [
            'title' => "{$playerName} - NHL Player Stats & Profile | NHL PLAY",
            'description' => "Complete {$playerName} profile with career stats, advanced analytics, recent performance, and biographical information. Track {$playerName}'s NHL career on NHL PLAY.",
            'keywords' => "{$playerName}, NHL player, hockey stats, career statistics, player profile, NHL analytics",
            'canonical' => "/player/{$playerId}",
            'type' => 'profile'
        ];
    }

    private static function getGamePageSEO($context) {
        $gameId = $context['game_id'];
        $gameType = $context['game_type'] ?? 'live';
        $teams = $context['teams'] ?? ['away' => 'Team A', 'home' => 'Team B'];
        
        $title = $gameType === 'live' ? "Live Game" : ($gameType === 'pre-game' ? "Game Preview" : "Game Recap");
        $action = $gameType === 'live' ? "Watch live" : ($gameType === 'pre-game' ? "Preview" : "Read recap");
        
        return [
            'title' => "{$teams['away']} vs {$teams['home']} - {$title} | NHL PLAY",
            'description' => "{$action} of {$teams['away']} vs {$teams['home']}. Get real-time scores, stats, highlights, and comprehensive game analysis on NHL PLAY.",
            'keywords' => "{$teams['away']}, {$teams['home']}, NHL game, hockey score, live game, game recap, NHL highlights",
            'canonical' => "/game/{$gameId}",
            'type' => 'article'
        ];
    }

    public static function generateMetaTags($seoData) {
        // Get proper base URL - handle AJAX endpoints correctly
        $baseUrl = self::getCleanBaseUrl();
        $currentUrl = $baseUrl . ($seoData['canonical'] ?? '');
        
        $tags = [];
        
        // Basic meta tags
        $tags[] = '<title>' . htmlspecialchars($seoData['title']) . '</title>';
        $tags[] = '<meta name="description" content="' . htmlspecialchars($seoData['description']) . '">';
        $tags[] = '<meta name="keywords" content="' . htmlspecialchars($seoData['keywords']) . '">';
        $tags[] = '<link rel="canonical" href="' . $currentUrl . '">';
        
        // Meta robots directive for better crawling
        $tags[] = '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">';
        
        // Open Graph tags
        $tags[] = '<meta property="og:title" content="' . htmlspecialchars($seoData['title']) . '">';
        $tags[] = '<meta property="og:description" content="' . htmlspecialchars($seoData['description']) . '">';
        $tags[] = '<meta property="og:url" content="' . $currentUrl . '">';
        $tags[] = '<meta property="og:type" content="' . ($seoData['type'] ?? 'website') . '">';
        $tags[] = '<meta property="og:site_name" content="NHL PLAY">';
        $tags[] = '<meta property="og:image" content="' . $baseUrl . '/assets/img/og-image-nhlplay.jpg">';
        $tags[] = '<meta property="og:image:width" content="180">';
        $tags[] = '<meta property="og:image:height" content="180">';
        $tags[] = '<meta property="og:locale" content="en_US">';
        
        // Twitter Card tags
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($seoData['title']) . '">';
        $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($seoData['description']) . '">';
        $tags[] = '<meta name="twitter:image" content="' . $baseUrl . '/assets/img/og-image-nhlplay-twitter.jpg">';
        $tags[] = '<meta name="twitter:site" content="@NHLPlayOnline">';
        
        // Additional structured data hints
        $tags[] = '<meta name="theme-color" content="#041e42">';
        $tags[] = '<meta name="msapplication-TileColor" content="#041e42">';
        
        // Add JSON-LD structured data
        $tags[] = self::generateStructuredData($seoData, $currentUrl);
        
        return implode("\n    ", $tags);
    }
    
    private static function getCleanBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // For NHL PLAY, we know the project is in the /nhl directory on localhost
        // and at the root on production (nhlplay.online)
        if ($host === 'localhost' || strpos($host, 'localhost') !== false) {
            return $protocol . $host . '/nhl';
        } else {
            return $protocol . $host;
        }
    }
    
    private static function generateStructuredData($seoData, $currentUrl) {
        $baseUrl = self::getCleanBaseUrl();
        
        // Base organization data
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "name" => "NHL PLAY",
            "url" => $baseUrl,
            "description" => "The ultimate NHL hub for live scores, player stats, team analysis, and comprehensive hockey statistics.",
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => $baseUrl . "/search?q={search_term_string}"
                ],
                "query-input" => "required name=search_term_string"
            ]
        ];
        
        // Add page-specific structured data
        if (isset($seoData['type'])) {
            switch ($seoData['type']) {
                case 'website':
                    // Already handled above
                    break;
                case 'profile':
                    $structuredData["@type"] = "ProfilePage";
                    break;
                case 'article':
                    $structuredData["@type"] = "Article";
                    $structuredData["headline"] = $seoData['title'];
                    $structuredData["description"] = $seoData['description'];
                    $structuredData["url"] = $currentUrl;
                    break;
            }
        }
        
        return '<script type="application/ld+json">' . json_encode($structuredData, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
?>
