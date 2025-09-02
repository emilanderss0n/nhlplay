import { fixAjaxResponseUrls } from './ajax-handler.js';
import { debounce } from './utils.js';
import { initTabSliders, setActiveTab, updateTabSlider } from './tab-slider.js';

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
        return _inFlightRequests.get(key).promise;
    }

    // Use AbortController to allow timeouts and cancellation if needed
    const controller = new AbortController();
    const timeoutMs = params.timeout || 10000; // default 10s timeout
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    const fetchPromise = fetch('ajax/stat-leaders-demand-load.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: urlParams.toString(),
        signal: controller.signal
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
            holder.style.display = 'flex';
        }
        
        // Hide activity indicator
        if (activityContent) {
            activityContent.style.display = 'none';
        }
        return text;
    })
    .catch(error => {
        console.error('Error loading stat content:', error);
        // If the error was abort due to timeout, try one retry (unless already retried)
        if (!params._retryAttempted) {
            console.info('Retrying loadStatContent for', key);
            params._retryAttempted = true;
            // small backoff
            return new Promise((resolve) => setTimeout(resolve, 500)).then(() => {
                clearTimeout(timeoutId);
                _inFlightRequests.delete(key);
                return loadStatContent(holder, activityContent, params);
            });
        }

        holder.innerHTML = '<div class="error">Failed to load data</div>';
        if (activityContent) {
            activityContent.style.display = 'none';
        }
        throw error;
    })
    .finally(() => {
        clearTimeout(timeoutId);
        // cleanup stored entry
        const entry = _inFlightRequests.get(key);
        if (entry && entry.controller === controller) {
            _inFlightRequests.delete(key);
        }
    });

    // store and cleanup in-flight promise along with controller
    _inFlightRequests.set(key, { promise: fetchPromise, controller, timeoutId });
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
            
            // Replace contents of the current main element to preserve the element identity
            const mainEl = document.querySelector('main');
            if (mainEl) {
                mainEl.innerHTML = mainContent.innerHTML;
            }

            // Reinitialize any event handlers for this module
            // (don't rebind global handlers already attached to existing elements)
            try { initStatLeadersHandlers(); } catch (e) { console.warn(e); }

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
                // results is an array of Promises
                Promise.all(results).then(resolve).catch(reject);
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
            activeHolder.style.display = 'flex';
        }
    });
}

export function initStatLeadersHandlers() {
    // Ensure initial visibility is always set
    initializeStatHoldersVisibility();

    // Initialize tab sliders for all .tabs containers
    try {
        initTabSliders('.stat-select.tabs');
    } catch (e) {
        console.warn('Failed to initialize tab sliders:', e);
    }

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

    // No heavy custom select initialization here; header-style dropdown links are handled by handleHeaderSeasonClick
    
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

        // Update active tab using slider-aware function
        const tabContainer = option.closest('.stat-select.tabs');
        if (tabContainer) {
            setActiveTab(tabContainer, option);
        } else {
            // Fallback for non-.tabs containers
            document.querySelectorAll(`.stat-select .option.${list}`).forEach(el => {
                el.classList.remove('active');
            });
            option.classList.add('active');
        }

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
                el.style.display = 'flex';
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
                    // Execute inline scripts in the injected HTML
                    try { executeInlineScripts(sectionStats); } catch (e) {}
                } else {
                    const tableContainer = table.parentNode;
                    if (tableContainer) {
                        tableContainer.innerHTML = html;
                        try { executeInlineScripts(tableContainer); } catch (e) {}
                    }
                }

                // Use auto-initializer for JSDataTable initialization
                if (window.initAfterAjax) {
                    window.initAfterAjax(sectionStats || table.parentNode);
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

    // Minimal handler for header-style season dropdown links (keeps JS minimal)
    document.removeEventListener('click', handleHeaderSeasonClick);
    document.addEventListener('click', handleHeaderSeasonClick);

    // Implementation of the header season click handler
    function handleHeaderSeasonClick(e) {
        const link = e.target.closest('.season-select-link');
        if (!link) return;
        e.preventDefault();

        const season = link.dataset.value || link.getAttribute('data-value');
        if (!season) return;

        // Update hidden native select if present
        const select = document.getElementById('seasonStatLeadersSelect');
        if (select) {
            select.value = season;
            const ev = new Event('change', { bubbles: true });
            select.dispatchEvent(ev);
        }

        // Close the header dropdown checkbox if present
        const checkbox = document.getElementById('seasonDropdown');
        if (checkbox) checkbox.checked = false;

        // Update the visible label if present
        const labelSpan = document.querySelector('.season-select-dropdown .season-current');
        if (labelSpan) labelSpan.textContent = season;
    }
    
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

    // Handle hover on stat leader list items to update the holder
    const handleStatLeaderHover = function(e) {
        // Ensure e.target is an element before calling closest
        if (!e.target || typeof e.target.closest !== 'function') return;
        
        const item = e.target.closest('.stat-leader-list-item');
        if (!item) return;
        
        // Remove active class from all items in the same list
        const list = item.closest('.stat-leader-list');
        if (list) {
            list.querySelectorAll('.stat-leader-list-item').forEach(el => {
                el.classList.remove('active');
            });
        }
        
        // Add active class to the hovered item
        item.classList.add('active');
        
        const holder = item.closest('.stat-holder').querySelector('.stat-leader-holder');
        if (!holder) return;
        
        // Get player data from the item's data attributes
        const playerId = item.dataset.playerId;
        const teamId = item.dataset.teamId;
        const triCode = item.dataset.tricode;
        const playerName = item.dataset.name;
        const position = item.dataset.position;
        const logo = item.dataset.logo;
        const bgColor = item.dataset.bgColor;
        const stat = item.dataset.stat;
        const headshot = item.dataset.headshot;
        const playerNumber = item.dataset.jersey;
        const rank = item.dataset.rank;
        const category = item.closest('.stat-holder').classList.contains('stat-points') ? 'Points' : 
                        item.closest('.stat-holder').classList.contains('stat-goals') ? 'Goals' : 
                        item.closest('.stat-holder').classList.contains('stat-assists') ? 'Assists' : 
                        item.closest('.stat-holder').classList.contains('stat-svp') ? 'Save %' : 
                        item.closest('.stat-holder').classList.contains('stat-gaa') ? 'GAA' : 'Stat';

        // generate random number for mask IDs to avoid duplicates
        const randId = Math.floor(Math.random() * 10000);
        
        // Create the player card HTML
        const playerCardHtml = `
            <div class="player-card" data-player-id="${playerId}" data-team-id="${teamId}" data-tricode="${triCode}" data-name="${playerName}" data-position="${position}" data-logo="${logo}" data-bg-color="${bgColor}" data-stat="${stat}" data-headshot="${headshot}">
                <a class="headshot" href="#" id="player-link" data-link="${playerId}">
                    <svg class="headshot_wrap" width="128" height="128">
                        <mask id="circleMask:r${randId}:">
                            <svg>
                                <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                            </svg>
                        </mask>
                        <image mask="url(#circleMask:r${randId}:)" fill="#000000" id="canTop" height="128" href="${headshot}"></image>
                    </svg>
                    <svg class="team-fill" width="128" height="128">
                        <circle cx="64" cy="72" r="56" fill="${bgColor}"></circle>
                        <defs>
                            <linearGradient id="gradient:r${randId}:" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                                <stop offset="65%" stop-opacity="0.35" stop-color="#000000"></stop>
                            </linearGradient>
                        </defs>
                        <circle cx="64" cy="72" r="56" fill="url(#gradient:r${randId}:)"></circle>
                    </svg>
                </a>
                <div class="player-info">
                    <div class="mob-flex">
                        <div class="player-name">${playerName}</div>
                        <div class="player-meta">
                            <img src="${logo}" alt="${triCode}" class="team-logo">
                            <span class="team-code">${triCode}</span>
                            <span class="player-number">#${playerNumber}</span>
                            <div class="player-position">${position}</div>
                        </div>
                    </div>
                    <div class="player-stat"><h2>${stat}</h2><span>${category}</span></div>
                </div>
            </div>
        `;
        
        // Update the holder content
        holder.innerHTML = playerCardHtml;
    };
    
    // Add hover event listeners to stat leader list items
    document.addEventListener('mouseenter', handleStatLeaderHover, true);
    
    // Optional: Add mouseleave to revert to first player when not hovering
    // This handler used to revert to the first item; change to no-op so the last hovered item remains active
    const handleStatLeaderLeave = function(e) {
        // Keep a safety check to avoid errors when e.target is not an Element
        if (!e || !e.target || typeof e.target.closest !== 'function') return;
        // Intentionally do nothing here so the active class persists on the last hovered item
        return;
    };

    document.addEventListener('mouseleave', handleStatLeaderLeave, true);

    // Initialize active class on first stat leader list item
    document.querySelectorAll('.stat-leader-list').forEach(list => {
        const firstItem = list.querySelector('.stat-leader-list-item:first-child');
        if (firstItem) {
            firstItem.classList.add('active');
        }
    });
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
                try { executeInlineScripts(sectionStats); } catch (e) {}
            } else {
                // Fallback: replace the table's parent if we have one
                const tableContainer = table ? table.parentNode : null;
                if (tableContainer) {
                    tableContainer.innerHTML = html;
                    try { executeInlineScripts(tableContainer); } catch (e) {}
                }
            }
            // Use auto-initializer for JSDataTable initialization
            if (window.initAfterAjax) {
                window.initAfterAjax(sectionStats || tableContainer);
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
                // Prefer inserting table HTML into the section-stats container
                sectionStats.innerHTML = tableEl.outerHTML || html;
                // Hide global season selector when in table view
                try { setSeasonSelectVisible(false); } catch (e) {}
                try { executeInlineScripts(sectionStats); } catch (e) {}
            } else {
                // Fallback: replace contents of main rather than the node itself
                const mainEl = document.querySelector('main');
                if (mainEl) {
                    mainEl.innerHTML = tableEl.outerHTML || html;
                    try { executeInlineScripts(mainEl); } catch (e) {}
                }
            }
            // Use auto-initializer for JSDataTable initialization
            if (window.initAfterAjax) {
                window.initAfterAjax(sectionStats || mainEl);
            }

            // Update toggle text to allow returning to card view
            toggle.textContent = 'Cards';
        })
        .catch(error => {
            console.error('Error loading table view:', error);
        });
    }, 150);

    document.removeEventListener('click', handleToggleTable);
    document.addEventListener('click', handleToggleTable);
}

// Execute inline <script> tags inside a container element (used after injecting HTML via innerHTML)
function executeInlineScripts(container) {
    if (!container) return;
    // Query all script tags inside the container
    const scripts = Array.from(container.querySelectorAll('script'));
    scripts.forEach(oldScript => {
        try {
            const newScript = document.createElement('script');
            // Copy type if present
            if (oldScript.type) newScript.type = oldScript.type;
            if (oldScript.src) {
                // External script: add to head to load/execute
                newScript.src = oldScript.src;
                newScript.async = false;
                document.head.appendChild(newScript);
            } else {
                // Inline script: execute by setting textContent
                newScript.text = oldScript.textContent;
                document.head.appendChild(newScript);
                // Remove immediately after execution to keep DOM clean
                document.head.removeChild(newScript);
            }
        } catch (e) {
            console.warn('Failed to execute injected script', e);
        }
    });
}