import { fixAjaxResponseUrls } from './ajax-handler.js';
import { debounce } from './utils.js';

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
    
    return fetch('ajax/stat-leaders-demand-load.php', {
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
        holder.style.display = 'block';
        
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
    const categories = ['goals', 'assists'];
    const promises = [];
    
    categories.forEach(category => {
        const holder = document.querySelector(`.stat-${category}.${type}`);
        const activityContent = document.querySelector(`.activity-content.${type}`);
        
        if (holder) {
            promises.push(
                loadStatContent(holder, activityContent, {
                    type: type,
                    category: category,
                    season: season,
                    loadOnDemand: true,
                    playoffs: playoffs
                })
            );
        }
    });
    
    return Promise.all(promises);
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
    // Call this immediately to fix initial state
    initializeStatHoldersVisibility();
    
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

            // Get season from window or default to current season
            const season = window.season || '20242025';
            
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
            
            const playerTypes = ['skaters', 'defense', 'goalies', 'rookies'];
            playerTypes.forEach(type => {
                // Preload goals and assists data in the background
                batchLoadStatCategories(type, playoffs, season);
            });
        });
    }, 200); // 200ms debounce
    
    document.removeEventListener('click', handleSeasonToggle);
    document.addEventListener('click', handleSeasonToggle);
    
    // Setup a mutation observer to watch for content changes from AJAX
    const mainElement = document.querySelector('main');
    if (mainElement) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // When content is added to main, ensure proper visibility
                    initializeStatHoldersVisibility();
                }
            });
        });
        
        // Start observing main element for DOM changes
        observer.observe(mainElement, { childList: true, subtree: true });
    }
}

export function initStatLeadersTableHandler() {
    const handleSeasonSelect = debounce(function(e) {
        const link = e.target.closest('.season-select-link');
        if (!link) return;
        
        e.preventDefault();
        const table = document.getElementById('playerStatsTable');
        if (!table) return;
        
        const season = link.getAttribute('data-season');
        
        fetch('ajax/stat-leaders-table.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'season=' + encodeURIComponent(season)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text();
        })
        .then(html => {
            const tableContainer = table.parentNode;
            tableContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading table data:', error);
            table.parentNode.innerHTML = '<div class="error">Failed to load data</div>';
        });
    }, 150); // 150ms debounce
    
    document.removeEventListener('click', handleSeasonSelect);
    document.addEventListener('click', handleSeasonSelect);
}