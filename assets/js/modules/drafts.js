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

        // Content manager handles JSDataTable initialization automatically
        // No manual initialization needed!

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
        
        // Auto-initializer handles everything - JSDataTable, dependencies, etc.
        window.initAfterAjax(container);
        
        container.style.opacity = '1';
        spinner.style.display = 'none';
    };
    
    xhr.send();
}