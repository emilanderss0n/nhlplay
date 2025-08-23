import { fixAjaxResponseUrls } from './ajax-handler.js';
import { debounce } from './utils.js';

// Guard to ensure handlers/observers are only attached once
let _statLeadersHandlersAttached = false;
// Mutation observer instance (kept so we can re-observe new `main` after content replacement)
let _statLeadersObserver = null;
// In-flight request dedupe map
const _inFlightRequests = new Map();

/**
 * Show or hide the global season selector.
 * @param {boolean} visible
 */
function setSeasonSelectVisible(visible) {
    document.querySelectorAll('.season-select').forEach(el => {
        // Use empty string to revert to stylesheet default when visible
        el.style.display = visible ? '' : 'none';
    });
}

/**
 * Load stat content via fetch API
 * @param {HTMLElement} holder Target element for content
 * @param {HTMLElement} activityContent Loading indicator element
 * @param {Object} params Request parameters
 * @returns {Promise} Fetch promise
 */
function loadStatContent(holder, activityContent, params) {
    if (!holder) return Promise.reject(new Error('Invalid holder element'));
    
    const urlParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        urlParams.append(key, value);
    });
    
    const key = `${params.type}|${params.category}|${params.season}|${params.playoffs}`;
    if (_inFlightRequests.has(key)) {
        return _inFlightRequests.get(key);
    }

    const fetchPromise = fetch('ajax/stat-leaders-demand-load.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: urlParams.toString()
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        // Fix any image paths in the response
        holder.innerHTML = fixAjaxResponseUrls(text);
        // Only show the holder immediately if caller requested it (default true)
        if (params.showOnLoad !== false) {
            holder.style.display = 'block';
        }
        
        // Hide activity indicator
        if (activityContent) {
            activityContent.style.display = 'none';
        }
        return text;
    })
    .catch(error => {
        console.error('Error loading stat content:', error);
        holder.innerHTML = '<div class="error">Failed to load data</div>';
        if (activityContent) {
            activityContent.style.display = 'none';
        }
        throw error;
    });

    // store and cleanup in-flight promise
    _inFlightRequests.set(key, fetchPromise);
    fetchPromise.finally(() => _inFlightRequests.delete(key));
    return fetchPromise;
}

/**
 * Reload the main content via fetch API when switching seasons
 * @param {string} playoffs Whether to show playoffs stats
 * @param {string} season Season ID
 * @returns {Promise} Fetch promise
 */
function reloadStatLeadersContent(playoffs, season) {
    return fetch(`ajax/stat-leaders.php?playoffs=${playoffs}&season=${season}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const mainContent = doc.querySelector('main');
            
            // Replace the current main content
            document.querySelector('main').outerHTML = mainContent.outerHTML;

            // Reinitialize any event handlers
            initStatLeadersHandlers();

            // Ensure season selector is visible when returning to the card view
            try {
                setSeasonSelectVisible(true);
            } catch (e) {
                // ignore if helper isn't available for some reason
            }

            return html;
        })
        .catch(error => {
            console.error('Error reloading content:', error);
        });
}

/**
 * Batch load multiple stat categories at once
 * @param {string} type Player type (skaters, defense, etc.)
 * @param {string} playoffs Whether to show playoffs stats
 * @param {string} season Season ID
 * @returns {Promise} Promise that resolves when all content is loaded
 */
function batchLoadStatCategories(type, playoffs, season) {
    // Backwards-compatible: if called directly, load goals+assists for non-goalies and gaa for goalies
    let categories = [];
    if (type === 'goalies') {
        categories = ['gaa'];
    } else {
        categories = ['goals', 'assists'];
    }

    const tasks = [];
    categories.forEach(category => {
        const holder = document.querySelector(`.stat-${category}.${type}`);
        const activityContent = document.querySelector(`.activity-content.${type}`);
        if (holder) {
            tasks.push(() => loadStatContent(holder, activityContent, {
                type: type,
                category: category,
                season: season,
                loadOnDemand: true,
                showOnLoad: false,
                playoffs: playoffs
            }));
        }
    });

    // Run with small concurrency to avoid resource exhaustion
    return runWithConcurrency(tasks, 3);
}

/**
 * Run promise-returning tasks with a concurrency limit
 * @param {Array<Function>} tasks Functions that return a Promise when called
 * @param {number} limit Maximum concurrent tasks
 * @returns {Promise}
 */
function runWithConcurrency(tasks, limit = 3) {
    const results = [];
    let index = 0;

    return new Promise((resolve, reject) => {
        let active = 0;

        function next() {
            if (index === tasks.length && active === 0) {
                resolve(Promise.all(results));
                return;
            }
            while (active < limit && index < tasks.length) {
                const taskIndex = index++;
                active++;
                const p = tasks[taskIndex]();
                results[taskIndex] = p;
                p.then(() => {
                    active--;
                    next();
                }).catch(err => {
                    active--;
                    // don't reject immediately; continue other tasks but keep error visible
                    console.warn('Preload task failed', err);
                    next();
                });
            }
        }

        next();
    });
}

/**
 * Preload only the minimal default stats for each player type when switching seasons.
 * Skaters/defense/rookies => 'points'
 * Goalies => 'gaa'
 */
function preloadDefaultsForSeason(playoffs, season) {
    const tasks = [];
    const defaults = {
        skaters: 'points',
        defense: 'points',
        rookies: 'points',
        goalies: 'gaa'
    };

    Object.entries(defaults).forEach(([type, category]) => {
        const holder = document.querySelector(`.stat-${category}.${type}`);
        const activityContent = document.querySelector(`.activity-content.${type}`);
        if (holder) {
            tasks.push(() => loadStatContent(holder, activityContent, {
                type: type,
                category: category,
                season: season,
                loadOnDemand: true,
                showOnLoad: false,
                playoffs: playoffs
            }));
        }
    });

    return runWithConcurrency(tasks, 3);
}

/**
 * Initialize the visibility of stat holders - ensure only active ones are shown
 * This should be called on load and after content updates
 */
function initializeStatHoldersVisibility() {
    // Get all stat categories
    const categories = ['skaters', 'defense', 'goalies', 'rookies'];
    
    // For each category, make sure only the active option's content is visible
    categories.forEach(category => {
        // Find active option for this category
        const activeOption = document.querySelector(`.stat-select .${category}.option.active`);
        if (!activeOption) return;
        
        const activeType = activeOption.dataset.type;
        
        // Hide all stat holders for this category
        document.querySelectorAll(`.stat-holder.${category}`).forEach(holder => {
            holder.style.display = 'none';
        });
        
        // Show only the active stat holder
        const activeHolder = document.querySelector(`.stat-${activeType}.stat-holder.${category}`);
        if (activeHolder) {
            activeHolder.style.display = 'block';
        }
    });
}

export function initStatLeadersHandlers() {
    // Ensure initial visibility is always set
    initializeStatHoldersVisibility();

    // If handlers already attached, avoid re-attaching them (prevents duplicate listeners/requests)
    if (_statLeadersHandlersAttached) {
        // Also ensure season selector visibility matches current view
        try {
            const inTableMode = document.querySelector('main')?.querySelector('#playerStatsTable');
            setSeasonSelectVisible(!inTableMode);
        } catch (e) {}
        return;
    }
    _statLeadersHandlersAttached = true;

    // Ensure season selector visibility matches current view (hide for table view)
    try {
        const inTableMode = document.querySelector('main')?.querySelector('#playerStatsTable');
        setSeasonSelectVisible(!inTableMode);
    } catch (e) {
        // ignore
    }
    
    // Handle stat-select option clicks with debounce
    const handleStatOptionClick = debounce(function(e) {
        if (!e.target.closest('.stat-select .option')) return;
        
        e.preventDefault();
        const option = e.target.closest('.stat-select .option');
        const type = option.dataset.type;
        const list = option.dataset.list;
        const load = option.dataset.load;
        const playoffs = option.dataset.playoffs || 
            (document.querySelector('.btn-group .btn.active[data-playoffs]')?.dataset.playoffs === 'true');

        // Update active tab
        document.querySelectorAll(`.stat-select .option.${list}`).forEach(el => {
            el.classList.remove('active');
        });
        option.classList.add('active');

        // Hide all stat holders for this category
        document.querySelectorAll(`.stat-holder.${list}`).forEach(el => {
            el.style.display = 'none';
        });

        // Show activity loader
        const activityContent = document.querySelector(`.activity-content.${list}`);
        if (activityContent) {
            activityContent.style.display = 'block';
        }

        if (load) {
            // Load content on demand
            const holder = document.querySelector(`.stat-${type}.${list}`);
            if (!holder) return;

            // Get season from select or fallback to window/default
            const seasonSelect = document.getElementById('seasonStatLeadersSelect');
            const season = seasonSelect ? seasonSelect.value : (window.season || '20242025');
            
            loadStatContent(holder, activityContent, {
                type: list,
                category: type,
                season: season,
                loadOnDemand: load,
                playoffs: playoffs
            });
        } else {
            // Content already loaded, just show it
            document.querySelectorAll(`.stat-${type}.${list}`).forEach(el => {
                el.style.display = 'block';
            });

            if (activityContent) {
                activityContent.style.display = 'none';
            }
        }
    }, 100); // 100ms debounce
    
    document.removeEventListener('click', handleStatOptionClick);
    document.addEventListener('click', handleStatOptionClick);

    // Handle playoffs/regular season toggle in the btn-group with debounce
    const handleSeasonToggle = debounce(function(e) {
        const seasonBtn = e.target.closest('.season-select.btn-group .btn');
        if (!seasonBtn) return;
        
        e.preventDefault();
        
        // Update active button
        document.querySelectorAll('.season-select.btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        seasonBtn.classList.add('active');
        
        // Show loading indicator
        document.querySelectorAll('.activity-content').forEach(el => {
            el.style.display = 'block';
        });
        
        // Hide all stat holders
        document.querySelectorAll('.stat-holder').forEach(el => {
            el.style.display = 'none';
        });
        
        // Load the page with the new playoffs parameter
        const season = seasonBtn.dataset.season;
        const playoffs = seasonBtn.dataset.playoffs;
        
        // Preload common stat categories after the initial content loads
        reloadStatLeadersContent(playoffs, season).then(() => {
            // Call this to ensure correct initial visibility
            initializeStatHoldersVisibility();
            
            // Preload only default minimal stats (points for skaters/defense/rookies, gaa for goalies)
            preloadDefaultsForSeason(playoffs, season);
        });
    }, 200); // 200ms debounce
    
    document.removeEventListener('click', handleSeasonToggle);
    document.addEventListener('click', handleSeasonToggle);

    // Handle season selection dropdown change
    const handleSeasonSelectChange = debounce(function(e) {
        if (e.target.id !== 'seasonStatLeadersSelect') return;
        
        const selectedSeason = e.target.value;
        const currentPlayoffs = document.querySelector('.season-select.btn-group .btn.active')?.dataset.playoffs || 'false';
        
        // Show loading indicator
        document.querySelectorAll('.activity-content').forEach(el => {
            el.style.display = 'block';
        });
        
        // Hide all stat holders
        document.querySelectorAll('.stat-holder').forEach(el => {
            el.style.display = 'none';
        });
        
        // If we're currently showing the full table view, update the table via AJAX
        const table = document.getElementById('playerStatsTable');
        if (table) {
            // Update table fragment keeping the table view active
            const body = new URLSearchParams();
            body.append('season', selectedSeason);
            body.append('selectedPlayoffs', currentPlayoffs);

            fetch('ajax/stat-leaders-table.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString()
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                return res.text();
            })
            .then(html => {
                const sectionStats = document.querySelector('.section-stats');
                if (sectionStats) {
                    sectionStats.innerHTML = html;
                    // Ensure season selector stays hidden when table view is active
                    try { setSeasonSelectVisible(false); } catch (e) {}
                } else {
                    const tableContainer = table.parentNode;
                    if (tableContainer) tableContainer.innerHTML = html;
                }

                // Re-initialize DataTable if available
                try {
                    if (typeof jsdatatables !== 'undefined' && !/Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent)) {
                        const tbl = document.getElementById('playerStatsTable');
                        if (tbl) new jsdatatables.JSDataTable('#playerStatsTable', { paging: true, searchable: true, perPage: 25 });
                    }
                } catch (e) {
                    console.warn('Failed to init DataTable after season select on table view:', e);
                }
            })
            .catch(error => {
                console.error('Error loading table data:', error);
            });

            return;
        }

        // Not in table mode: reload the standard card view for the new season
        reloadStatLeadersContent(currentPlayoffs, selectedSeason).then(() => {
            // Call this to ensure correct initial visibility
            initializeStatHoldersVisibility();
            
            // Preload only defaults for this season to prime cache without showing extra holders
            preloadDefaultsForSeason(currentPlayoffs, selectedSeason);
        });
    }, 200); // 200ms debounce
    
    document.removeEventListener('change', handleSeasonSelectChange);
    document.addEventListener('change', handleSeasonSelectChange);
    
    // Setup/re-attach a mutation observer to watch for content changes from AJAX
    function attachMainObserver() {
        if (_statLeadersObserver) {
            try { _statLeadersObserver.disconnect(); } catch (e) {}
            _statLeadersObserver = null;
        }
        const mainElement = document.querySelector('main');
        if (!mainElement) return;
        _statLeadersObserver = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // When content is added to main, ensure proper visibility
                    initializeStatHoldersVisibility();
                }
            });
        });
        _statLeadersObserver.observe(mainElement, { childList: true, subtree: true });
    }
    attachMainObserver();

    // Ensure the table handler is also initialized when this module loads
    if (typeof initStatLeadersTableHandler === 'function') {
        try {
            initStatLeadersTableHandler();
        } catch (e) {
            console.warn('Failed to initialize table handler:', e);
        }
    }
}

export function initStatLeadersTableHandler() {
    const handleSeasonSelect = debounce(function(e) {
        const link = e.target.closest('.season-select-link');
        if (!link) return;
        
        e.preventDefault();
        const table = document.getElementById('playerStatsTable');
        if (!table) return;
        
        const playoffs = link.getAttribute('data-playoffs');
        
        fetch('ajax/stat-leaders-table.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'selectedPlayoffs=' + playoffs
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text();
        })
        .then(html => {
            // Prefer replacing the .section-stats area so component-header remains intact
            const sectionStats = document.querySelector('.section-stats');
            if (sectionStats) {
                // Server returns the table fragment; insert it into the stats section
                sectionStats.innerHTML = html;
                // Hide global season selector when we're showing the full table view
                try { setSeasonSelectVisible(false); } catch (e) {}
            } else {
                // Fallback: replace the table's parent if we have one
                const tableContainer = table ? table.parentNode : null;
                if (tableContainer) tableContainer.innerHTML = html;
            }
            // Initialize DataTable client-side because AJAX returns only the table fragment
            try {
                if (typeof jsdatatables !== 'undefined' && !/Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent)) {
                    new jsdatatables.JSDataTable('#playerStatsTable', { paging: true, searchable: true });
                }
            } catch (e) {
                console.warn('Failed to init DataTable after season select:', e);
            }
        })
        .catch(error => {
            console.error('Error loading table data:', error);
            table.parentNode.innerHTML = '<div class="error">Failed to load data</div>';
        });
    }, 150); // 150ms debounce
    
    document.removeEventListener('click', handleSeasonSelect);
    document.addEventListener('click', handleSeasonSelect);

    // Handle toggle between card view and table view
    const handleToggleTable = debounce(function(e) {
        const toggle = e.target.closest('#stat-leaders-toggle-table');
        if (!toggle) return;

        e.preventDefault();

        // If table is already visible (we're in table mode), reload the original stat-leaders.php
        const mainEl = document.querySelector('main');
        const isTableMode = mainEl && mainEl.querySelector('#playerStatsTable');
        // Get season from select or toggle data or fallback
        const seasonSelect = document.getElementById('seasonStatLeadersSelect');
        const season = seasonSelect ? seasonSelect.value : (toggle.dataset.season || window.season || '20242025');
        const playoffs = toggle.dataset.playoffs || 'false';

        if (isTableMode) {
            // Load the standard card view
            reloadStatLeadersContent(playoffs, season);
            // Update toggle text
            toggle.textContent = 'Table';
            // Show season selector when returning to cards
            try { setSeasonSelectVisible(true); } catch (e) {}
            return;
        }

        // Fetch the table partial via POST and mark request as AJAX
        const body = new URLSearchParams();
        body.append('season', season);

        fetch('ajax/stat-leaders-table.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.text();
        })
        .then(html => {
            // Replace main content with the returned table fragment
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const tableEl = doc.querySelector('table') || doc.querySelector('main');
            if (!tableEl) {
                throw new Error('No table content returned');
            }

            // Insert the returned table into the existing .section-stats so header stays
            const sectionStats = document.querySelector('.section-stats');
            if (sectionStats) {
                // If tableEl is an element, use its outerHTML, otherwise insert the raw HTML
                sectionStats.innerHTML = tableEl.outerHTML || html;
                // Hide global season selector when in table view
                try { setSeasonSelectVisible(false); } catch (e) {}
            } else {
                // Fallback: replace the whole main
                const newMain = document.createElement('main');
                const wrap = document.createElement('div');
                wrap.className = 'wrap';
                wrap.appendChild(tableEl);
                newMain.appendChild(wrap);
                document.querySelector('main').outerHTML = newMain.outerHTML;
            }

            // Update toggle text to allow returning to card view
            toggle.textContent = 'Cards';

            // Initialize DataTable client-side since AJAX returned only the table
            try {
                if (typeof jsdatatables !== 'undefined' && !/Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent)) {
                    const tbl = document.getElementById('playerStatsTable');
                    if (tbl) {
                        new jsdatatables.JSDataTable('#playerStatsTable', { paging: false, searchable: true });
                    }
                }
            } catch (e) {
                console.warn('Failed to init DataTable after toggle:', e);
            }
        })
        .catch(error => {
            console.error('Error loading table view:', error);
        });
    }, 150);

    document.removeEventListener('click', handleToggleTable);
    document.addEventListener('click', handleToggleTable);
}