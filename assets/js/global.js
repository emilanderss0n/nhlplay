import { ModuleLoader } from './core/module-loader.js';
import { PageDetector } from './core/page-detector.js';
import { FeatureObserver } from './core/feature-observer.js';
import { appState } from './core/app-state.js';
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

        } catch (error) {
            console.error('Error during app initialization:', error);
        }
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
            'game-handlers': () => module.initGameHandlers(this.elements),
            'team-handlers': () => module.initTeamHandlers(this.elements),
            'player-handlers': () => module.initPlayerHandlers(this.elements),
            'standings-handlers': () => module.initStandingsHandlers(),
            'ui-settings': () => module.initUISettings(),
            'stat-leaders-handlers': () => module.initStatLeadersHandlers(),
            'trade-handlers': () => module.initTradeHandlers(),
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