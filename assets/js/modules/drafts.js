export function initDraftPage() {
    setupDraftTableHandlers();
    setupPreviousDraftHandler();
}

function setupDraftTableHandlers() {
    // Wait for elements to be available
    const waitForElements = () => {
        const draftFilter = document.querySelector('.draft-filter');
        const draftTable = document.getElementById('draftRankings1');
        
        if (!draftFilter || !draftTable) {
            // If elements aren't ready yet, try again in 50ms
            setTimeout(waitForElements, 50);
            return;
        }
        
        // Remove existing event listeners
        const oldButtons = document.querySelectorAll('.draft-filter .btn');
        oldButtons.forEach(button => {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
        });

        // Wait for JSDataTable library to be available using the same pattern as stat-leaders
        const initializeTable = () => {
            if (typeof jsdatatables !== 'undefined') {
                try {
                    // Check if table is already initialized
                    if (draftTable.classList.contains('jsDataTable-table') || draftTable.dataset.jsdatatableInitialized) {
                        console.log('JSDataTable already initialized for draftRankings1');
                        return;
                    }
                    
                    new jsdatatables.JSDataTable('#draftRankings1', {
                        paging: true,
                        perPage: 50,
                        perPageSelect: [25, 50, 100],
                        searchable: true,
                    });
                    draftTable.dataset.jsdatatableInitialized = '1';
                    console.log('JSDataTable initialized for draftRankings1');
                } catch (error) {
                    console.warn('JSDataTable initialization failed:', error);
                }
            } else {
                // Try to find existing script tag; follow same pattern as stat-leaders-handlers
                const existing = Array.from(document.scripts).find(s => s.src && s.src.indexOf('datatables.min.js') !== -1);
                if (existing) {
                    if (existing.hasAttribute('data-loaded')) {
                        initializeTable();
                    } else {
                        existing.addEventListener('load', () => {
                            existing.setAttribute('data-loaded', '1');
                            initializeTable();
                        });
                    }
                } else {
                    // No script found - dynamically load it (this handles manual refresh scenario)
                    console.log('JSDataTable script not found, loading dynamically...');
                    const s = document.createElement('script');
                    const baseUrl = window.location.pathname.includes('/nhl') ? '/nhl' : '';
                    s.src = `${baseUrl}/assets/js/datatables.min.js`;
                    s.async = false;
                    s.onload = () => { 
                        s.setAttribute('data-loaded', '1'); 
                        initializeTable(); 
                    };
                    s.onerror = (e) => {
                        console.warn('Failed to load datatables.min.js', e);
                        draftTable.classList.add('basic-table');
                    };
                    document.head.appendChild(s);
                }
            }
        };

        initializeTable();

        // Add click handlers
        document.querySelectorAll('.draft-filter .btn').forEach(button => {
            button.addEventListener('click', handleDraftTableSwitch);
        });
    };
    
    waitForElements();
}

function setupPreviousDraftHandler() {
    const waitForPrevButton = () => {
        const previousDraftBtn = document.getElementById('show-previous-draft');
        if (!previousDraftBtn) {
            // If button isn't ready yet, try again in 50ms
            setTimeout(waitForPrevButton, 50);
            return;
        }
        
        // Remove existing event listener if any
        const newBtn = previousDraftBtn.cloneNode(true);
        previousDraftBtn.parentNode.replaceChild(newBtn, previousDraftBtn);
        
        newBtn.addEventListener('click', function() {
            const previousDraft = document.querySelector('.previous-draft');
            
            // If already shown and has content, just toggle visibility
            if (previousDraft && previousDraft.innerHTML.trim()) {
                previousDraft.classList.toggle('show');
                return;
            }
            
            // Otherwise load content
            const draftYear = this.dataset.draftYear;
            const xhr = new XMLHttpRequest();
            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('GET', `${baseUrl}/ajax/draft-previous.php?draftYear=${draftYear}`);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onload = function() {
                if (previousDraft) {
                    previousDraft.innerHTML = this.responseText;
                    previousDraft.classList.toggle('show');
                }
            };
            
            xhr.send();
        });
    };
    
    waitForPrevButton();
}

function handleDraftTableSwitch(e) {
    e.preventDefault();
    const tableId = this.id;
    const container = document.getElementById('rankings-container');
    const spinner = document.querySelector('.loading-spinner');
    
    // Update active state
    document.querySelectorAll('.draft-filter .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    this.classList.add('active');

    // Show loading spinner
    container.style.opacity = '0.5';
    spinner.style.display = 'block';

    // Load new table
    const xhr = new XMLHttpRequest();
    const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
    xhr.open('GET', `${baseUrl}/includes/tables/draft-table-${tableId.split('-')[2]}.php?year=${document.querySelector('.lower-contrast').textContent.match(/\d+/)[0]}`);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        container.innerHTML = this.responseText;
        
        // Wait for JSDataTable library and reinitialize datatable
        const initializeNewTable = () => {
            const newTable = container.querySelector('table');
            if (!newTable || newTable.classList.contains('jsDataTable-table') || newTable.dataset.jsdatatableInitialized) {
                container.style.opacity = '1';
                spinner.style.display = 'none';
                return;
            }
            
            if (typeof jsdatatables !== 'undefined') {
                try {
                    new jsdatatables.JSDataTable('#' + newTable.id, {
                        paging: true,
                        perPage: 50,
                        perPageSelect: [25, 50, 100],
                        searchable: true,
                    });
                    newTable.dataset.jsdatatableInitialized = '1';
                    console.log(`JSDataTable initialized for ${newTable.id}`);
                } catch (error) {
                    console.warn('JSDataTable initialization failed:', error);
                }
            } else {
                // Try to find existing script tag; follow same pattern as stat-leaders-handlers
                const existing = Array.from(document.scripts).find(s => s.src && s.src.indexOf('datatables.min.js') !== -1);
                if (existing) {
                    if (existing.hasAttribute('data-loaded')) {
                        initializeNewTable();
                        return; // Don't hide spinner yet, let recursion handle it
                    } else {
                        existing.addEventListener('load', () => {
                            existing.setAttribute('data-loaded', '1');
                            initializeNewTable();
                        });
                        return; // Don't hide spinner yet
                    }
                } else {
                    // No script found - dynamically load it
                    console.log('JSDataTable script not found for new table, loading dynamically...');
                    const s = document.createElement('script');
                    const baseUrl = window.location.pathname.includes('/nhl') ? '/nhl' : '';
                    s.src = `${baseUrl}/assets/js/datatables.min.js`;
                    s.async = false;
                    s.onload = () => { 
                        s.setAttribute('data-loaded', '1'); 
                        initializeNewTable(); 
                    };
                    s.onerror = (e) => {
                        console.warn('Failed to load datatables.min.js for new table', e);
                        newTable.classList.add('basic-table');
                        container.style.opacity = '1';
                        spinner.style.display = 'none';
                    };
                    document.head.appendChild(s);
                    return; // Don't hide spinner yet
                }
            }
            
            container.style.opacity = '1';
            spinner.style.display = 'none';
        };
        
        initializeNewTable();
    };
    
    xhr.send();
}