// assets/js/core/page-detector.js
export class PageDetector {
    static getPageType() {
        const body = document.body;
        const path = window.location.pathname;
        const state = window.history.state;
        
        // Check history state first (for SPA navigation)
        if (state && state.type === 'team') return 'team-view';
        if (state && state.type === 'player') return 'player-view';
        if (state && state.type === 'game') return 'game-view';
        
        // Check for specific page classes
        if (body.classList.contains('team-builder-page')) return 'team-builder';
        if (body.classList.contains('live-game-page')) return 'live-game';
        if (body.classList.contains('player-stats-page')) return 'player-stats';
        
        // Check for specific DOM elements
        if (document.querySelector('.pre-game-cont')) return 'pre-game';
        if (document.querySelector('.post-game-cont')) return 'post-game';
        if (document.querySelector('.draft-container')) return 'draft';
        if (document.querySelector('.compare-players-container')) return 'compare-players';
        
        // Check URL patterns
        if (path.includes('/team/')) return 'team-view';
        if (path.includes('/player/')) return 'player-view';
        if (path.includes('/game/')) return 'game-view';
        if (path.includes('/standings')) return 'standings';
        if (path.includes('/draft')) return 'draft';
        if (path.includes('/compare')) return 'compare-players';
        
        return 'homepage';
    }

    static getRequiredModules(pageType) {
        const moduleMap = {
            'homepage': [
                'menu-handlers', 
                'route-handler', 
                'team-handlers',
                'player-handlers',
                'live-games', 
                'standings-handlers', 
                'ui-settings',
                'stat-leaders-handlers',
                'player-leaders',
                'trade-handlers'
            ],
            'team-builder': [
                'teambuilder', 
                'ui-settings',
                'menu-handlers'
            ],
            'live-game': [
                'live-games', 
                'game-handlers', 
                'reddit-handlers',
                'ui-settings',
                'menu-handlers'
            ],
            'pre-game': [
                'pre-game-handlers',
                'game-handlers',
                'reddit-handlers',
                'ui-settings',
                'menu-handlers'
            ],
            'post-game': [
                'game-handlers',
                'reddit-handlers',
                'ui-settings',
                'menu-handlers'
            ],
            'player-stats': [
                'player-handlers', 
                'ui-settings',
                'menu-handlers'
            ],
            'team-view': [
                'team-handlers', 
                'player-handlers', 
                'ui-settings',
                'menu-handlers'
            ],
            'player-view': [
                'player-handlers', 
                'ui-settings',
                'menu-handlers'
            ],
            'game-view': [
                'game-handlers', 
                'reddit-handlers', 
                'ui-settings',
                'menu-handlers'
            ],
            'standings': [
                'standings-handlers',
                'ui-settings',
                'menu-handlers'
            ],
            'draft': [
                'drafts',
                'ui-settings',
                'menu-handlers'
            ],
            'compare-players': [
                'player-handlers',
                'ui-settings',
                'menu-handlers'
            ]
        };
        
        return moduleMap[pageType] || ['ui-settings', 'menu-handlers'];
    }
}
