export function initDraftPage() {
    setupDraftTableHandlers();
    
    // Listen for route changes
    document.addEventListener('routeChanged', () => {
        if (window.location.pathname.includes('/draft')) {
            setupDraftTableHandlers();
        }
    });
}

function setupDraftTableHandlers() {
    // Remove existing event listeners
    const oldButtons = document.querySelectorAll('.draft-filter .btn');
    oldButtons.forEach(button => {
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });

    // Initialize first table
    const draftTable = document.getElementById('draftRankings1');
    if (draftTable) {
        new jsdatatables.JSDataTable('#draftRankings1', {
            paging: true,
            perPage: 50,
            perPageSelect: [25, 50, 100],
            searchable: true,
        });
    }

    // Add click handlers
    document.querySelectorAll('.draft-filter .btn').forEach(button => {
        button.addEventListener('click', handleDraftTableSwitch);
    });
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
        // Reinitialize datatable
        const newTable = container.querySelector('table');
        if (newTable && !newTable.classList.contains('jsDataTable-table')) {
            new jsdatatables.JSDataTable('#' + newTable.id, {
                paging: true,
                perPage: 50,
                perPageSelect: [25, 50, 100],
                searchable: true,
            });
        }
        container.style.opacity = '1';
        spinner.style.display = 'none';
    };
    
    xhr.send();
}