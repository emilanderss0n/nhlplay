/**
 * Universal Auto-Initializer
 * Simple utility to automatically initialize common components after AJAX content loads
 * Can be called from any XHR onload handler to ensure everything works
 */

/**
 * Universal Auto-Initializer
 * Simple utility to automatically initialize common components after AJAX content loads
 * Can be called from any XHR onload handler to ensure everything works
 */

async function ensureJSDataTable() {
    if (typeof jsdatatables !== 'undefined') {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        // Check if script exists in DOM
        const existing = Array.from(document.scripts).find(s => s.src && s.src.indexOf('datatables.min.js') !== -1);
        
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
        } else {
            // Load dynamically
            const script = document.createElement('script');
            const baseUrl = window.location.pathname.includes('/nhl') ? '/nhl' : '';
            script.src = `${baseUrl}/assets/js/datatables.min.js`;
            script.async = false;
            script.onload = () => {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        }
    });
}

export async function autoInitialize(container = document) {
    const initPromises = [];
    
    // JSDataTable initialization with dependency loading
    const tables = container.querySelectorAll('table[id*="Rankings"], #playerStatsTable, .conferenceTable, .divisionTable, #leagueTable');
    
    if (tables.length > 0) {
        try {
            // Ensure JSDataTable is loaded first
            await ensureJSDataTable();
            
            tables.forEach(table => {
                if (!table.classList.contains('jsDataTable-table') && !table.dataset.jsdatatableInitialized) {
                    try {
                        let config = {
                            paging: true,
                            searchable: true,
                            perPage: 25
                        };
                        
                        // Draft tables get different config
                        if (table.id.includes('Rankings')) {
                            config.perPage = 50;
                            config.perPageSelect = [25, 50, 100];
                        }
                        
                        // Standings tables don't need paging
                        if (table.classList.contains('conferenceTable') || 
                            table.classList.contains('divisionTable') || 
                            table.id === 'leagueTable') {
                            config.paging = false;
                        }
                        
                        new jsdatatables.JSDataTable('#' + table.id, config);
                        table.dataset.jsdatatableInitialized = '1';
                    } catch (error) {
                        console.warn(`Failed to auto-initialize table ${table.id}:`, error);
                    }
                }
            });
        } catch (error) {
            console.warn('Failed to load JSDataTable library:', error);
        }
    }
    
    // Pre-game components
    if (container.querySelector('.pre-game-cont')) {
        initPromises.push(
            import('./pre-game-handlers.js').then(module => {
                module.initPreGamePage();
            }).catch(e => console.warn('Failed to auto-init pre-game:', e))
        );
    }
    
    // Team builder
    if (container.querySelector('#team-builder-drop-area')) {
        initPromises.push(
            import('./teambuilder.js').then(module => {
                module.initTeamBuilder();
            }).catch(e => console.warn('Failed to auto-init team builder:', e))
        );
    }
    
    // Draft mode
    if (container.querySelector('[data-draft-mode]') || 
        container.querySelector('.draft-mode') || 
        container.querySelector('#draft-mode-toggle')) {
        initPromises.push(
            import('./draft-mode.js').then(module => {
                module.initDraftMode();
            }).catch(e => console.warn('Failed to auto-init draft mode:', e))
        );
    }
    
    // Stat leaders
    if (container.querySelector('.stat-holder') || container.querySelector('.stat-select')) {
        initPromises.push(
            import('./stat-leaders-handlers.js').then(module => {
                module.initStatLeadersHandlers();
            }).catch(e => console.warn('Failed to auto-init stat leaders:', e))
        );
    }
    
    // Player leaders (homepage and other leader displays)
    if (container.querySelector('.skater-leaders') || 
        container.querySelector('.player-leaders') || 
        container.querySelector('.home-leaders .leaders-box')) {
        initPromises.push(
            import('./player-leaders.js').then(module => {
                module.initializeSkaterLeaders();
            }).catch(e => console.warn('Failed to auto-init player leaders:', e))
        );
    }
    
    await Promise.allSettled(initPromises);
}

/**
 * Call this in XHR onload handlers
 */
export function initAfterAjax(container = document) {
    // Use requestAnimationFrame to ensure DOM is ready
    requestAnimationFrame(() => {
        autoInitialize(container);
    });
}

// Make available globally for easy use in any XHR handler
window.autoInitialize = autoInitialize;
window.initAfterAjax = initAfterAjax;
