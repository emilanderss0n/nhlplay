/**
 * Universal Content Manager
 * Handles both initial page loads and dynamic content loading
 * Solves the recurring issue of JavaScript modules not working consistently
 */

class ContentManager {
    constructor() {
        this.observers = new Map();
        this.dependencies = new Map();
        this.initQueue = new Map();
        this.isReady = false;
        
        // Track loaded external libraries
        this.loadedLibraries = new Set();
        
        this.setupMutationObserver();
        this.setupDependencyTracking();
    }

    /**
     * Register a module that needs to be initialized when content changes
     */
    registerModule(moduleName, config) {
        const defaultConfig = {
            // Function to call when initializing
            initFunction: null,
            // CSS selector to watch for
            selector: null,
            // External dependencies (e.g., 'jsdatatables', 'Chart')
            dependencies: [],
            // Should this run on initial load?
            runOnLoad: true,
            // Should this run on dynamic content?
            runOnDynamic: true,
            // Debounce delay in ms
            debounce: 100,
            // Only run if elements exist
            requireElements: true
        };
        
        this.observers.set(moduleName, { ...defaultConfig, ...config });
    }

    /**
     * Register external library dependency
     */
    registerLibrary(libraryName, config) {
        const defaultConfig = {
            // Global variable name to check
            globalVar: libraryName,
            // Script src to load if missing  
            scriptSrc: null,
            // Detection function
            isLoaded: () => typeof window[libraryName] !== 'undefined',
            // Loading function
            load: () => this.loadScript(config.scriptSrc)
        };
        
        this.dependencies.set(libraryName, { ...defaultConfig, ...config });
    }

    /**
     * Load external script dynamically
     */
    async loadScript(src) {
        return new Promise((resolve, reject) => {
            // Check if already exists
            const existing = Array.from(document.scripts).find(s => 
                s.src && s.src.includes(src.split('/').pop())
            );
            
            if (existing) {
                if (existing.hasAttribute('data-loaded')) {
                    resolve();
                } else {
                    existing.addEventListener('load', () => {
                        existing.setAttribute('data-loaded', '1');
                        resolve();
                    });
                    existing.addEventListener('error', reject);
                }
                return;
            }

            // Create new script
            const script = document.createElement('script');
            const baseUrl = window.location.pathname.includes('/nhl') ? '/nhl' : '';
            script.src = src.startsWith('http') ? src : `${baseUrl}/${src}`;
            script.async = false;
            
            script.onload = () => {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = reject;
            
            document.head.appendChild(script);
        });
    }

    /**
     * Setup mutation observer to watch for dynamic content
     */
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            const addedNodes = [];
            
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        addedNodes.push(node);
                    }
                });
            });

            if (addedNodes.length > 0) {
                this.handleContentChange(addedNodes);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Setup built-in dependency tracking
     */
    setupDependencyTracking() {
        // Register common libraries
        this.registerLibrary('jsdatatables', {
            scriptSrc: 'assets/js/datatables.min.js',
            isLoaded: () => typeof window.jsdatatables !== 'undefined'
        });

        this.registerLibrary('Chart', {
            scriptSrc: 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js',
            isLoaded: () => typeof window.Chart !== 'undefined'
        });

        this.registerLibrary('Swiper', {
            scriptSrc: 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            isLoaded: () => typeof window.Swiper !== 'undefined'
        });
    }

    /**
     * Handle content changes (both initial and dynamic)
     */
    async handleContentChange(addedNodes = []) {
        const promises = [];
        
        for (const [moduleName, config] of this.observers) {
            if (!config.runOnDynamic && addedNodes.length > 0) continue;
            if (!config.runOnLoad && addedNodes.length === 0) continue;
            
            promises.push(this.processModule(moduleName, config, addedNodes));
        }

        await Promise.allSettled(promises);
    }

    /**
     * Process individual module
     */
    async processModule(moduleName, config, addedNodes) {
        try {
            // Check if elements exist (if required)
            if (config.requireElements && config.selector) {
                const elements = addedNodes.length > 0 
                    ? addedNodes.some(node => node.querySelector && node.querySelector(config.selector))
                    : document.querySelector(config.selector);
                    
                if (!elements) return;
            }

            // Load dependencies
            await this.loadDependencies(config.dependencies);

            // Debounce if specified
            if (config.debounce > 0) {
                const queueKey = `${moduleName}_${Date.now()}`;
                
                if (this.initQueue.has(moduleName)) {
                    clearTimeout(this.initQueue.get(moduleName));
                }
                
                this.initQueue.set(moduleName, setTimeout(() => {
                    this.executeModuleInit(moduleName, config);
                    this.initQueue.delete(moduleName);
                }, config.debounce));
            } else {
                await this.executeModuleInit(moduleName, config);
            }
            
        } catch (error) {
            console.warn(`ContentManager: Failed to process module ${moduleName}:`, error);
        }
    }

    /**
     * Load required dependencies
     */
    async loadDependencies(dependencies) {
        const promises = dependencies.map(async (depName) => {
            const dep = this.dependencies.get(depName);
            if (!dep) {
                console.warn(`ContentManager: Unknown dependency ${depName}`);
                return;
            }

            if (this.loadedLibraries.has(depName) || dep.isLoaded()) {
                this.loadedLibraries.add(depName);
                return;
            }

            await dep.load();
            this.loadedLibraries.add(depName);
        });

        await Promise.allSettled(promises);
    }

    /**
     * Execute module initialization
     */
    async executeModuleInit(moduleName, config) {
        if (typeof config.initFunction === 'function') {
            await config.initFunction();
        } else if (typeof config.initFunction === 'string') {
            // Support for function names as strings
            const fn = window[config.initFunction];
            if (typeof fn === 'function') {
                await fn();
            }
        }
    }

    /**
     * Initialize all modules (called on initial page load)
     */
    async initializeAll() {
        this.isReady = true;
        await this.handleContentChange([]);
    }

    /**
     * Force reinitialize specific module
     */
    async reinitialize(moduleName) {
        const config = this.observers.get(moduleName);
        if (config) {
            await this.processModule(moduleName, config, []);
        }
    }

    /**
     * Notify about route changes
     */
    async onRouteChange() {
        // Small delay to ensure DOM is updated
        setTimeout(() => this.handleContentChange([]), 50);
    }
}

// Create global instance
const contentManager = new ContentManager();

// Export for use in modules
export { contentManager };

// Also attach to window for global access
window.contentManager = contentManager;
