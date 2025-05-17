// This file defines the Reddit game thread handler functionality
// It exports a function that can be imported in other modules
// and also attaches the function to the window object for global access

// Create a function to check for Reddit game threads
export function checkRedditGameThread(gameId, forceCheck = false) {
    const threadTitle = document.getElementById('reddit-thread-title');
    const threadLink = document.getElementById('reddit-thread-link');
    const notFoundContainer = document.querySelector('.reddit-thread-not-found');
    
    if (!threadTitle || !threadLink) {
        return; // Exit if elements not found
    }
    
    // Show loading state
    threadTitle.textContent = 'Searching for game thread...';
    if (notFoundContainer) notFoundContainer.style.display = 'none';
    
    // Get the base URL based on environment
    const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
    
    // Fetch game thread for current game
    fetch(`${baseUrl}/ajax/find-game-thread.php?gameId=${gameId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.found && data.id) {            // We found a game thread
            threadTitle.textContent = data.title || 'Game Thread';
            threadLink.href = `https://www.reddit.com/r/hockey/comments/${data.id}/`;
            threadLink.style.display = 'flex';
            
            // Hide "not found" container if it was previously shown
            if (notFoundContainer) notFoundContainer.style.display = 'none';
        } else {
            // No game thread found - show not found message
            threadTitle.textContent = 'Game Thread Not Found';
            if (notFoundContainer) notFoundContainer.style.display = 'block';
        }
    })
    .catch(error => {
        threadTitle.textContent = 'Error Finding Game Thread';
        if (notFoundContainer) notFoundContainer.style.display = 'block';
    });
}

// Attach the function to the window object to make it globally available
window.checkRedditGameThread = function(forceCheck = false) {
    // Get the game ID from the URL or data attribute
    let gameId = new URLSearchParams(window.location.search).get('gameId');
    
    // If not in URL, try to get from data attribute
    if (!gameId) {
        const gameContainer = document.querySelector('.reddit-game-thread[data-game-id]');
        if (gameContainer) {
            gameId = gameContainer.dataset.gameId;
        }
    }
    
    if (!gameId) {
        return;
    }
    
    // Call the actual function with the game ID
    checkRedditGameThread(gameId, forceCheck);
};

// Helper function to initialize observers for Reddit thread container
export function initRedditThreadObservers() {
    // Create a MutationObserver to detect when the reddit thread container exists
    const redditThreadObserver = new MutationObserver(function(mutations) {
        for(let mutation of mutations) {
            if(mutation.type === 'childList' && mutation.addedNodes.length) {
                // Look for the reddit thread container
                const redditContainer = document.querySelector('.reddit-game-thread');
                if(redditContainer) {
                    window.checkRedditGameThread();
                    redditThreadObserver.disconnect(); // Stop observing once found
                    break;
                }
            }
        }
    });
    
    // Start observing the document with the configured parameters
    redditThreadObserver.observe(document.body, { childList: true, subtree: true });
    
    // Also call directly in case the container already exists
    if(document.querySelector('.reddit-game-thread')) {
        window.checkRedditGameThread();
    } else {
        // Set a fallback timeout just in case
        setTimeout(() => {
            if (document.querySelector('.reddit-game-thread')) {
                window.checkRedditGameThread();
            }
        }, 500);
    }
}
