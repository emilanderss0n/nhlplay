/**
 * Trade Handlers Module
 * Handles expandable trade functionality for trades-frontpage
 */

export function initTradeHandlers() {
    // Check if we have the new layout (buttons + expanded container)
    const tradeButtons = document.querySelector('.trades-frontpage .trade-buttons');
    const expandedContainer = document.querySelector('.trades-frontpage .trade-expanded-container');
    
    if (tradeButtons && expandedContainer) {
        initButtonBasedLayout();
    } else {
        // Fallback to old expandable layout
        initExpandableLayout();
    }
}

function initButtonBasedLayout() {
    const tradeButtons = document.querySelectorAll('.trades-frontpage .trade-buttons .trade');
    const expandedContainer = document.querySelector('.trades-frontpage .trade-expanded-container');
    
    tradeButtons.forEach(button => {
        button.addEventListener('click', (e) => handleButtonClick(e, expandedContainer));
        button.addEventListener('keydown', (e) => handleButtonKeydown(e, expandedContainer));
    });
}

async function handleButtonClick(event, expandedContainer) {
    const clickedButton = event.currentTarget;
    const tradeIndex = parseInt(clickedButton.dataset.tradeIndex);
    
    // Remove active class from all buttons
    const allButtons = document.querySelectorAll('.trades-frontpage .trade-buttons .trade');
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    clickedButton.classList.add('active');
    
    // Add fadeInTop animation class and active class to expanded container
    expandedContainer.classList.add('fadeInTop', 'active');
    
    // Fetch the expanded trade content
    try {
        const response = await fetch(`ajax/trades.php?expanded=true&index=${tradeIndex}`);
        if (response.ok) {
            const data = await response.text();
            
            // Extract just the trade content
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const tradeContent = doc.querySelector('.trade.alt-layout.expanded');
            
            if (tradeContent) {
                expandedContainer.innerHTML = tradeContent.outerHTML;
            }
        } else {
            // Fallback: try to create expanded content from button data
            await createExpandedFromButton(clickedButton, expandedContainer, tradeIndex);
        }
    } catch (error) {
        console.error('Error fetching trade details:', error);
        // Fallback: try to create expanded content from button data
        await createExpandedFromButton(clickedButton, expandedContainer, tradeIndex);
    }
    
    // Remove fadeInTop class after animation completes (typically 0.5s)
    setTimeout(() => {
        expandedContainer.classList.remove('fadeInTop');
    }, 500);
    
    // Remove active class after 2 seconds
    setTimeout(() => {
        expandedContainer.classList.remove('active');
    }, 2000);
}

function handleButtonKeydown(event, expandedContainer) {
    // Handle Enter and Space key presses for accessibility
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handleButtonClick(event, expandedContainer);
    }
}

async function createExpandedFromButton(button, expandedContainer, tradeIndex) {
    // Simple fallback - just show a loading message for now
    // In a real implementation, you might want to fetch trade data separately
    const buttonStyle = button.style.background;
    const buttonDate = button.querySelector('.date').textContent;
    const buttonSummary = button.querySelector('.trade-summary').textContent;
    
    expandedContainer.innerHTML = `
        <div class="trade alt-layout expanded" style="background: ${buttonStyle};">
            <div class="date">${buttonDate}</div>
            <div class="teams">
                <div style="text-align: center; padding: 2rem; color: var(--paragraph-color);">
                    <h3>${buttonSummary}</h3>
                    <p>Loading trade details...</p>
                </div>
            </div>
        </div>
    `;
    
    // Try to fetch actual trade details
    try {
        const response = await fetch(`ajax/trades.php`);
        if (response.ok) {
            const text = await response.text();
            // This would need more sophisticated parsing in a real implementation
            console.log('Full trade data available for parsing');
        }
    } catch (error) {
        console.error('Could not fetch trade details:', error);
    }
}

function initExpandableLayout() {
    // Original expandable layout logic
    const tradeCards = document.querySelectorAll('.trades-frontpage .trade.alt-layout:not(.expanded)');
    
    tradeCards.forEach(card => {
        card.addEventListener('click', handleTradeClick);
        card.addEventListener('keydown', handleTradeKeydown);
    });
}

function handleTradeClick(event) {
    const tradeCard = event.currentTarget;
    
    // Add expanded class to show full details
    tradeCard.classList.add('expanded');
    
    // Remove click listener since it's now expanded
    tradeCard.removeEventListener('click', handleTradeClick);
    tradeCard.removeEventListener('keydown', handleTradeKeydown);
}

function handleTradeKeydown(event) {
    // Handle Enter and Space key presses for accessibility
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handleTradeClick(event);
    }
}

// Auto-initialize if trades-frontpage exists
if (document.querySelector('.trades-frontpage')) {
    document.addEventListener('DOMContentLoaded', initTradeHandlers);
}
