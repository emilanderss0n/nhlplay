import { ModuleLoader } from './core/module-loader.js';
import { PageDetector } from './core/page-detector.js';
import { FeatureObserver } from './core/feature-observer.js';
import { appState } from './core/app-state.js';
import { contentManager } from './core/content-manager.js';
import { initAfterAjax } from './modules/auto-initializer.js';
import { initDOMElements } from './modules/dom-elements.js';
import { 
    convertUTCTimesToLocal, 
    initDropdownKeyboardNavigation, 
    initDropdownClickOutside, 
    initTeamLinksAccessibility,
    initPlayerSearchAccessibility,
    initSearchClickOutside
} from './modules/utils.js';

class App {
    constructor() {
        this.moduleLoader = new ModuleLoader();
        this.featureObserver = new FeatureObserver(this.moduleLoader);
        this.elements = null;
        this.appState = appState;
    }

    async init() {
        try {
            // Always load core modules and utilities
            this.elements = initDOMElements();
            convertUTCTimesToLocal();

            // Setup content manager with module registrations
            await this.setupContentManager();

            // Detect page type and load required modules
            const pageType = PageDetector.getPageType();
            this.appState.setState('currentPage', pageType);

            const requiredModules = PageDetector.getRequiredModules(pageType);

            // Load modules in parallel
            const modulePromises = requiredModules.map(moduleName => 
                this.loadAndInitModule(moduleName)
            );

            await Promise.allSettled(modulePromises);

            // Initialize intersection observer for lazy loading
            this.initLazyLoadObservers();

            // Page-specific initializations
            this.initPageSpecificFeatures(pageType);

            // Initialize global event listeners
            this.initGlobalEventListeners();

            // Initialize content manager
            await contentManager.initializeAll();

            // Run auto-initializer for initial page content
            initAfterAjax(document);

        } catch (error) {
            console.error('Error during app initialization:', error);
        }
    }

    async setupContentManager() {
        // Register common modules with content manager
        
        // Draft tables with JSDataTable
        contentManager.registerModule('draft-tables', {
            selector: '#draftRankings1, #draftRankings2, #draftRankings3, #draftRankings4',
            dependencies: ['jsdatatables'],
            initFunction: async () => {
                const tables = document.querySelectorAll('#draftRankings1, #draftRankings2, #draftRankings3, #draftRankings4');
                tables.forEach(table => {
                    if (!table.classList.contains('jsDataTable-table') && !table.dataset.jsdatatableInitialized) {
                        new jsdatatables.JSDataTable('#' + table.id, {
                            paging: true,
                            perPage: 50,
                            perPageSelect: [25, 50, 100],
                            searchable: true,
                        });
                        table.dataset.jsdatatableInitialized = '1';
                    }
                });
            },
            debounce: 200
        });

        // Standings tables
        contentManager.registerModule('standings-tables', {
            selector: '.conferenceTable, .divisionTable, #leagueTable',
            dependencies: ['jsdatatables'],
            initFunction: async () => {
                const tables = document.querySelectorAll('.conferenceTable, .divisionTable, #leagueTable');
                tables.forEach(table => {
                    if (!table.classList.contains('jsDataTable-table') && !table.dataset.jsdatatableInitialized) {
                        new jsdatatables.JSDataTable('#' + table.id || '.' + table.className.split(' ')[0], {
                            paging: false,
                            searchable: true,
                        });
                        table.dataset.jsdatatableInitialized = '1';
                    }
                });
            },
            debounce: 100
        });

        // Stat leaders tables
        contentManager.registerModule('stat-leaders-table', {
            selector: '#playerStatsTable',
            dependencies: ['jsdatatables'],
            initFunction: async () => {
                const table = document.getElementById('playerStatsTable');
                if (table && !table.classList.contains('jsDataTable-table') && !table.dataset.jsdatatableInitialized) {
                    new jsdatatables.JSDataTable('#playerStatsTable', {
                        paging: true,
                        searchable: true,
                        perPage: 25
                    });
                    table.dataset.jsdatatableInitialized = '1';
                }
            },
            debounce: 100
        });

        // Chart.js charts
        contentManager.registerModule('charts', {
            selector: '#playerStatsChart, .chart-container canvas',
            dependencies: ['Chart'],
            initFunction: async () => {},
            runOnLoad: false
        });

        // Pre-game handlers
        contentManager.registerModule('pre-game', {
            selector: '.pre-game-cont',
            initFunction: async () => {
                const { initPreGamePage } = await import('./modules/pre-game-handlers.js');
                initPreGamePage();
            },
            debounce: 150
        });

        // Team builder
        contentManager.registerModule('team-builder', {
            selector: '#team-builder-drop-area',
            initFunction: async () => {
                const { initTeamBuilder } = await import('./modules/teambuilder.js');
                initTeamBuilder();
            },
            debounce: 200
        });
    }

    async loadAndInitModule(moduleName) {
        try {
            const module = await this.moduleLoader.loadModule(moduleName);
            this.initializeModule(module, moduleName);
            this.appState.addLoadedModule(moduleName);
            return module;
        } catch (error) {
            console.warn(`Failed to initialize module: ${moduleName}`, error);
            return null;
        }
    }

    initializeModule(module, moduleName) {
        // Standard initialization patterns
        const initFunctions = {
            'live-games': () => module.initLiveGames(),
            'teambuilder': () => module.initTeamBuilder(),
            'draft-mode': () => module.initDraftMode(),
            'game-handlers': () => module.initGameHandlers(this.elements),
            'team-handlers': () => module.initTeamHandlers(this.elements),
            'player-handlers': () => module.initPlayerHandlers(this.elements),
            'standings-handlers': () => module.initStandingsHandlers(),
            'ui-settings': () => module.initUISettings(),
            'stat-leaders-handlers': () => module.initStatLeadersHandlers(),
            'trade-handlers': () => module.initTradeHandlers(),
            'signing-handlers': () => module.initSigningHandlers(),
            'trade-signing-toggle': () => module.initTradeSigningToggle(),
            'reddit-handlers': () => {
                if (module.initRedditPosts) module.initRedditPosts();
                if (module.initRedditGameThread) module.initRedditGameThread();
            },
            'menu-handlers': () => {
                if (module.initMenuHandlers) module.initMenuHandlers();
                if (module.checkRecentTrades) module.checkRecentTrades();
            },
            'route-handler': () => module.initRouteHandler(this.elements),
            'player-leaders': () => module.initializeSkaterLeaders(),
            'drafts': () => module.initDraftPage(),
            'reddit-thread-handler': () => module.initRedditThreadObservers(),
            'pre-game-handlers': () => module.initPreGamePage()
        };

        const initFn = initFunctions[moduleName];
        if (initFn) {
            try {
                initFn();
            } catch (error) {
                console.error(`Error initializing module ${moduleName}:`, error);
            }
        }
    }

    initLazyLoadObservers() {
        // Observe elements that should trigger lazy loading
        const lazyElements = document.querySelectorAll('[data-lazy-module]');
        
        lazyElements.forEach(element => {
            const moduleName = element.dataset.lazyModule;
            this.featureObserver.observeElement(element, moduleName);
        });
    }

    initPageSpecificFeatures(pageType) {
        // Add page-specific initialization logic
        if (pageType === 'pre-game' && document.querySelector('.pre-game-cont')) {
            this.moduleLoader.loadModule('pre-game-handlers').then(module => {
                if (module && module.initPreGamePage) {
                    module.initPreGamePage();
                }
            });
        }
    }

    initGlobalEventListeners() {
        // Global error handling
        window.addEventListener('error', (e) => {
            console.error('Global error:', e.error);
        });

        // Listen for route changes and re-initialize modules
        document.addEventListener('routeChanged', async () => {
            // Use requestAnimationFrame to ensure DOM is painted, then wait for next frame
            requestAnimationFrame(() => {
                requestAnimationFrame(async () => {
                    const newPageType = PageDetector.getPageType();
                    const currentPage = this.appState.getState('currentPage');
                    
                    // Notify content manager about route change
                    await contentManager.onRouteChange();
                    
                    // Only re-initialize if page type actually changed
                    if (newPageType !== currentPage) {
                        this.appState.setState('currentPage', newPageType);
                        
                        const requiredModules = PageDetector.getRequiredModules(newPageType);
                        
                        // Load and initialize new modules
                        const modulePromises = requiredModules.map(moduleName => 
                            this.loadAndInitModule(moduleName)
                        );
                        
                        await Promise.allSettled(modulePromises);
                        
                        // Re-initialize page-specific features
                        this.initPageSpecificFeatures(newPageType);
                    }
                });
            });
        });

        // Initialize accessibility features
        initDropdownKeyboardNavigation();
        initDropdownClickOutside();
        initTeamLinksAccessibility();
        initPlayerSearchAccessibility();
        initSearchClickOutside();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const app = new App();
    app.init();
});