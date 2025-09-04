/**
 * Signing Handlers Module
 * Handles expandable signing functionality for signings view
 */

export function initSigningHandlers() {
    // Check if we have the new layout (buttons + expanded container)
    const signingButtons = document.querySelector('.signings .signing-buttons');
    const expandedContainer = document.querySelector('.signings .signing-expanded-container');
    
    if (signingButtons && expandedContainer) {
        initButtonBasedLayout();
    } else {
        // Fallback to standard layout - no special handlers needed for now
    }
}

function initButtonBasedLayout() {
    const signingButtons = document.querySelectorAll('.signings .signing-buttons .signing');
    const expandedContainer = document.querySelector('.signings .signing-expanded-container');
    
    signingButtons.forEach(button => {
        button.addEventListener('click', (e) => handleButtonClick(e, expandedContainer));
        button.addEventListener('keydown', (e) => handleButtonKeydown(e, expandedContainer));
    });
}

async function handleButtonClick(event, expandedContainer) {
    const clickedButton = event.currentTarget;
    const signingIndex = parseInt(clickedButton.dataset.signingIndex);
    
    // Remove active class from all buttons
    const allButtons = document.querySelectorAll('.signings .signing-buttons .signing');
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    clickedButton.classList.add('active');
    
    // Add fadeInTop animation class and active class to expanded container
    expandedContainer.classList.add('fadeInTop', 'active');
    
    // Fetch the expanded signing content
    try {
        const response = await fetch(`ajax/trades.php?signing-expanded=true&index=${signingIndex}`);
        if (response.ok) {
            const data = await response.text();
            
            // Extract just the signing content
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const signingContent = doc.querySelector('.signing.alt-layout.expanded');
            
            if (signingContent) {
                expandedContainer.innerHTML = signingContent.outerHTML;
            }
        } else {
            // Fallback: try to create expanded content from button data
            await createExpandedFromButton(clickedButton, expandedContainer, signingIndex);
        }
    } catch (error) {
        console.error('Error fetching signing details:', error);
        // Fallback: try to create expanded content from button data
        await createExpandedFromButton(clickedButton, expandedContainer, signingIndex);
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

async function createExpandedFromButton(button, expandedContainer, signingIndex) {
    // Simple fallback - just show a loading message for now
    const buttonStyle = button.style.background;
    const buttonDate = button.querySelector('.date').textContent;
    const buttonSummary = button.querySelector('.signing-summary').textContent;
    
    expandedContainer.innerHTML = `
        <div class="signing alt-layout expanded" style="background: ${buttonStyle};">
            <div class="date">${buttonDate}</div>
            <div class="signing-content">
                <div style="text-align: center; padding: 2rem; color: var(--paragraph-color);">
                    <h3>${buttonSummary}</h3>
                    <p>Loading signing details...</p>
                </div>
            </div>
        </div>
    `;
    
    // Try to fetch actual signing details
    try {
        const response = await fetch(`ajax/trades.php?view=signings`);
        if (response.ok) {
            const text = await response.text();
            // This would need more sophisticated parsing in a real implementation
            console.log('Full signing data available for parsing');
        }
    } catch (error) {
        console.error('Could not fetch signing details:', error);
    }
}

// Make initSigningHandlers available globally for the toggle module
window.initSigningHandlers = initSigningHandlers;
