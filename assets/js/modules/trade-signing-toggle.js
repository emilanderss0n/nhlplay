/**
 * Trade/Signing Toggle Module
 * Handles toggling between trades and signings view
 */

export function initTradeSigningToggle() {
    const toggleButtons = document.querySelectorAll('.component-header .btn-group .btn');
    const contentContainer = document.getElementById('content-container');
    
    if (toggleButtons.length === 0 || !contentContainer) {
        return; // Exit if elements not found
    }
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', (e) => handleToggleClick(e, contentContainer));
    });
}

async function handleToggleClick(event, contentContainer) {
    const clickedButton = event.currentTarget;
    const view = clickedButton.dataset.view;
    
    // Remove active class from all buttons
    const allButtons = document.querySelectorAll('.component-header .btn-group .btn');
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    clickedButton.classList.add('active');
    
    // Update title based on view
    const titleElement = document.querySelector('.component-header .title');
    if (titleElement) {
        titleElement.textContent = view === 'signings' ? 'Signing Tracker' : 'Trade Tracker';
    }
    
    // Show loading state
    contentContainer.innerHTML = '<div class="loading" style="text-align: center; padding: 2rem; color: #999;">Loading...</div>';
    
    // Fetch new content
    try {
        const response = await fetch(`ajax/trades.php?view=${view}`);
        if (response.ok) {
            const data = await response.text();
            contentContainer.innerHTML = data;
            
            // Update container class for styling
            if (view === 'signings') {
                contentContainer.className = 'signings';
                // Check if we're on frontpage and apply signing frontpage class
                if (document.querySelector('.trades-frontpage')) {
                    contentContainer.className = 'signings signings-frontpage';
                }
            } else {
                contentContainer.className = 'trades';
                // Check if we're on frontpage and apply trades frontpage class
                if (document.querySelector('.trades-frontpage')) {
                    contentContainer.className = 'trades trades-frontpage';
                }
            }
            
            // Re-initialize trade/signing handlers if needed
            if (view === 'trades' && window.initTradeHandlers) {
                window.initTradeHandlers();
            } else if (view === 'signings' && window.initSigningHandlers) {
                window.initSigningHandlers();
            }
        } else {
            throw new Error('Failed to fetch content');
        }
    } catch (error) {
        console.error('Error fetching content:', error);
        contentContainer.innerHTML = '<div class="error" style="text-align: center; padding: 2rem; color: #ff6b6b;">Error loading content. Please try again.</div>';
    }
}

// Auto-initialize if on trades page
if (document.querySelector('.component-header .btn-group .btn')) {
    document.addEventListener('DOMContentLoaded', initTradeSigningToggle);
}
