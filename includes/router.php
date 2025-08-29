<?php
/**
 * Simple Router for NHL PLAY
 * - Uses PageDetector for page/context detection
 * - Maps known page types to allowed template/controller files
 * - Performs safe includes only from a white-list to avoid path traversal
 * - Sets HTTP status for 404s
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../path.php';
}

require_once __DIR__ . '/page-detector.php';
// Ensure MobileDetect class is available for server-side device detection
if (file_exists(__DIR__ . '/MobileDetect.php')) {
    require_once __DIR__ . '/MobileDetect.php';
}

class Router
{
    private static $detection = null;
    private static $pageMap = [
        'home' => ['pages/home.php'],
        'team' => ['pages/team.php', 'team-view.php', 'team.php'],
        'player' => ['pages/player-seo.php', 'pages/player.php', 'player-view.php', 'player.php'],
        'game' => ['pages/game.php', 'live-game.php', 'game.php'],
        'scores' => ['pages/scores.php', 'scores.php'],
        'stat-leaders' => ['pages/stat-leaders.php', 'stat-leaders.php'],
        'standings' => ['pages/standings.php', 'standings.php'],
        'team-builder' => ['pages/team-builder.php', 'team-builder.php'],
        'draft' => ['pages/draft.php', 'draft.php'],
        'trades' => ['pages/trades.php', 'trades.php'],
        'recaps' => ['pages/recaps.php', 'recaps.php'],
    ];

    public static function init()
    {
        if (self::$detection === null) {
            self::$detection = PageDetector::detectPageAndContext();
            // Build small app context to pass into templates and keep compatibility
            $detectInstance = null;
            if (class_exists('\Detection\\MobileDetect')) {
                try {
                    $detectInstance = new \Detection\MobileDetect();
                } catch (Throwable $e) {
                    $detectInstance = null;
                }
            }

            $app = [
                'page' => self::$detection['page'],
                'context' => self::$detection['context'],
                'detect' => $detectInstance,
                // defaults for template flags
                'seasonBreak' => $GLOBALS['seasonBreak'] ?? false,
                'playoffs' => $GLOBALS['playoffs'] ?? false,
            ];

            // Expose app for templates
            self::$detection['app'] = $app;
            $GLOBALS['app'] = $app;
        }
    }

    public static function getApp()
    {
        self::init();
        return self::$detection['app'] ?? null;
    }

    public static function getCurrentPage()
    {
        self::init();
        return self::$detection['page'];
    }

    public static function getContext()
    {
        self::init();
        return self::$detection['context'];
    }

    /**
     * Render the appropriate page template/controller.
     * This method only includes files from the white-list above.
     */
    public static function dispatch()
    {
        self::init();
        $page = self::$detection['page'];
        $context = self::$detection['context'];

        // AJAX endpoints should bypass full-page rendering
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            return;
        }

        $candidates = self::$pageMap[$page] ?? [];
        foreach ($candidates as $candidate) {
            $path = __DIR__ . '/../' . $candidate;
            if (file_exists($path)) {
                // make context available in included file
                $pageContext = $context; // local variable for includes

                // Make $app available for included templates and provide local variables
                $app = self::$detection['app'] ?? null;
                $pageContext = $context; // local variable for includes
                $detect = $app['detect'] ?? (class_exists('\Detection\\MobileDetect') ? new \Detection\MobileDetect : null);
                $seasonBreak = $app['seasonBreak'] ?? false;
                $playoffs = $app['playoffs'] ?? false;

                include $path;
                return;
            }
        }

        // No candidate found -> 404
        http_response_code(404);
        $GLOBALS['currentPage'] = '404';
        $GLOBALS['pageContext'] = [];
        $errorPage = __DIR__ . '/../404.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo '<h1>404 Not Found</h1>';
        }
    }
}

// Auto-init when required
Router::init();

?>
