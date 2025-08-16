# SEO Implementation Guide for NHL PLAY

## Executive Summary

This SEO implementation guide provides actionable recommendations to improve search engine optimization for NHL PLAY without requiring major project restructuring. The focus is on balancing simplicity with functionality to achieve better search rankings, increased organic traffic, and improved user experience.

## Current SEO Assessment

### Existing Strengths
- ✅ Clean URL structure with `.htaccess` rewriting
- ✅ Basic meta tags in `header.php`
- ✅ Comprehensive `sitemap.xml` with all team pages
- ✅ Well-configured `robots.txt`
- ✅ Google Analytics integration
- ✅ Mobile-responsive design
- ✅ Fast loading with dynamic module loading
- ✅ Semantic HTML structure

### Areas for Improvement
- ❌ Static title/description for all pages
- ❌ No structured data (Schema.org)
- ❌ Missing Open Graph and Twitter Card meta tags
- ❌ No canonical URLs
- ❌ Limited internal linking optimization
- ❌ No breadcrumb navigation
- ❌ Missing meta robots directives
- ❌ No alt text optimization for team logos

## Implementation Plan

**Note:** This implementation leverages the existing `BASE_URL` constant defined in `path.php`, which automatically detects the correct base URL for both local development (`http://localhost/nhl`) and production (`https://nhlplay.online`) environments.

### Page Detection System

The implementation includes a robust `PageDetector` class that properly identifies page types and extracts relevant context. This system:

- **Supports multiple URL patterns**: Direct file access, clean URLs, and query parameters
- **Extracts route parameters**: Automatically captures team abbreviations, game IDs, player IDs
- **Handles edge cases**: Provides fallbacks and proper error handling
- **Mirrors JavaScript logic**: Complements the existing frontend `PageDetector` class
- **Follows project conventions**: Uses the same patterns as the existing routing system

Create a new file `includes/seo-config.php`:

```php
<?php
// SEO configuration and dynamic meta tag generation

// Ensure BASE_URL is available (defined in path.php)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../path.php';
}

// Include existing team data instead of duplicating it
require_once __DIR__ . '/data/team-data.php';

class PageDetector {
    public static function detectPageAndContext() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
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
        if (preg_match('/^[A-Z]{3}$/', $pathParts[0] ?? '')) {
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
        if (isset($context['team_abbr'])) {
            return self::getTeamPageSEO($context['team_abbr']);
        }

        // Handle player pages
        if (isset($context['player_id']) && isset($context['player_name'])) {
            return self::getPlayerPageSEO($context['player_id'], $context['player_name']);
        }

        // Handle game pages
        if (isset($context['game_id']) && isset($context['teams'])) {
            return self::getGamePageSEO($context['game_id'], $context['teams'], $context['game_type']);
        }

        // Return default page config
        return self::$pageConfigs[$page] ?? self::$pageConfigs['home'];
    }

    private static function getTeamPageSEO($teamAbbr) {
        global $teamAbbrev, $teamNames;
        
        $teamId = $teamAbbrev[$teamAbbr] ?? null;
        $teamName = $teamId ? $teamNames[$teamId] : $teamAbbr;
        
        return [
            'title' => "{$teamName} - NHL Team Stats, Roster & Schedule | NHL PLAY",
            'description' => "Complete {$teamName} coverage including player stats, team roster, game schedule, recent performance, and in-depth analysis. Follow the {$teamName} on NHL PLAY.",
            'keywords' => "{$teamName}, {$teamAbbr}, NHL team, hockey roster, player stats, game schedule, team analysis, NHL standings",
            'canonical' => "/{$teamAbbr}",
            'type' => 'website'
        ];
    }

    private static function getPlayerPageSEO($playerId, $playerName) {
        return [
            'title' => "{$playerName} - NHL Player Stats & Profile | NHL PLAY",
            'description' => "Complete {$playerName} profile with career stats, advanced analytics, recent performance, and biographical information. Track {$playerName}'s NHL career on NHL PLAY.",
            'keywords' => "{$playerName}, NHL player, hockey stats, career statistics, player profile, NHL analytics",
            'canonical' => "/player/{$playerId}",
            'type' => 'profile'
        ];
    }

    private static function getGamePageSEO($gameId, $teams, $gameType = 'live') {
        $title = "Live" === $gameType ? "Live Game" : "Game Recap";
        $action = "Live" === $gameType ? "Watch live" : "Read recap";
        
        return [
            'title' => "{$teams['away']} vs {$teams['home']} - {$title} | NHL PLAY",
            'description' => "{$action} of {$teams['away']} vs {$teams['home']}. Get real-time scores, stats, highlights, and comprehensive game analysis on NHL PLAY.",
            'keywords' => "{$teams['away']}, {$teams['home']}, NHL game, hockey score, live game, game recap, NHL highlights",
            'canonical' => "/game/{$gameId}",
            'type' => 'article'
        ];
    }

    public static function generateMetaTags($seoData) {
        $baseUrl = BASE_URL; // from path.php
        $currentUrl = $baseUrl . ($seoData['canonical'] ?? '');
        
        $tags = [];
        
        // Basic meta tags
        $tags[] = '<title>' . htmlspecialchars($seoData['title']) . '</title>';
        $tags[] = '<meta name="description" content="' . htmlspecialchars($seoData['description']) . '">';
        $tags[] = '<meta name="keywords" content="' . htmlspecialchars($seoData['keywords']) . '">';
        $tags[] = '<link rel="canonical" href="' . $currentUrl . '">';
        
        // Open Graph tags
        $tags[] = '<meta property="og:title" content="' . htmlspecialchars($seoData['title']) . '">';
        $tags[] = '<meta property="og:description" content="' . htmlspecialchars($seoData['description']) . '">';
        $tags[] = '<meta property="og:url" content="' . $currentUrl . '">';
        $tags[] = '<meta property="og:type" content="' . ($seoData['type'] ?? 'website') . '">';
        $tags[] = '<meta property="og:site_name" content="NHL PLAY">';
        $tags[] = '<meta property="og:image" content="' . $baseUrl . '/assets/img/nhlplay-social-card.jpg">';
        $tags[] = '<meta property="og:image:width" content="1200">';
        $tags[] = '<meta property="og:image:height" content="630">';
        
        // Twitter Card tags
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($seoData['title']) . '">';
        $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($seoData['description']) . '">';
        $tags[] = '<meta name="twitter:image" content="' . $baseUrl . '/assets/img/nhlplay-social-card.jpg">';
        $tags[] = '<meta name="twitter:site" content="@NHLPlayOnline">';
        
        return implode("\n    ", $tags);
    }
}
?>
```

Modify `header.php` to use the new SEO system:

```php
// Add after line 5 (after includes)
require_once 'includes/seo-config.php';

// Replace the existing static title and meta tags (lines 33-35) with:
<?php
// Use the improved page detection system
$pageData = PageDetector::detectPageAndContext();
$currentPage = $pageData['page'];
$seoContext = $pageData['context'];

$seoData = SEOConfig::getPageSEO($currentPage, $seoContext);
echo SEOConfig::generateMetaTags($seoData);
?>
```