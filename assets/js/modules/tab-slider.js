/**
 * Tab Slider Module
 * Provides reusable functionality for animated tab sliders
 * 
 * Usage:
 * import { initTabSliders } from './tab-slider.js';
 * initTabSliders(); // Initialize all .tabs containers on the page
 * 
 * Or for specific containers:
 * initTabSliders('.specific-tabs-container');
 */

/**
 * Initialize tab slider functionality for .tabs containers
 * @param {string|HTMLElement|NodeList} selector - Optional selector to target specific containers
 */
export function initTabSliders(selector = '.tabs') {
    const containers = typeof selector === 'string' 
        ? document.querySelectorAll(selector)
        : selector instanceof NodeList || Array.isArray(selector)
        ? selector
        : [selector];

    containers.forEach(container => {
        if (!container || !container.classList.contains('tabs')) return;

        initSingleTabSlider(container);
    });
}

/**
 * Initialize slider for a single tabs container
 * @param {HTMLElement} container - The .tabs container element
 */
function initSingleTabSlider(container) {
    const options = container.querySelectorAll('.option');
    const slider = container.querySelector('.slider');
    
    if (!slider || options.length === 0) return;

    // Set initial slider position based on active option
    // Use setTimeout to ensure DOM is fully rendered
    setTimeout(() => {
        updateSliderPosition(container);
    }, 10);

    // Debounce the slider updates to prevent flickering
    let updateTimeout;
    const debouncedUpdate = (targetOption) => {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(() => {
            updateSliderPosition(container, targetOption);
        }, 5);
    };

    // Use container-level event delegation to avoid gap issues
    container.addEventListener('mousemove', (e) => {
        // Find which option the mouse is currently over by checking coordinates
        const hoveredOption = getOptionUnderMouse(container, e);
        if (hoveredOption) {
            debouncedUpdate(hoveredOption);
        }
    });

    container.addEventListener('mouseleave', () => {
        clearTimeout(updateTimeout);
        // Return to active option position when completely leaving the container
        updateSliderPosition(container);
    });
}

/**
 * Find which option element is under the mouse cursor
 * This works even when pointer-events are disabled on active options
 * @param {HTMLElement} container - The .tabs container
 * @param {MouseEvent} event - The mouse event
 * @returns {HTMLElement|null} The option element under the mouse
 */
function getOptionUnderMouse(container, event) {
    const options = container.querySelectorAll('.option');
    const containerRect = container.getBoundingClientRect();
    const relativeX = event.clientX - containerRect.left;
    
    for (const option of options) {
        const optionRect = option.getBoundingClientRect();
        const optionLeft = optionRect.left - containerRect.left;
        const optionRight = optionLeft + optionRect.width;
        
        if (relativeX >= optionLeft && relativeX <= optionRight) {
            return option;
        }
    }
    
    return null;
}

/**
 * Update slider position and width
 * @param {HTMLElement} container - The .tabs container
 * @param {HTMLElement|null} targetOption - Option to move slider to (null = use active option)
 */
function updateSliderPosition(container, targetOption = null) {
    const slider = container.querySelector('.slider');
    if (!slider) return;

    // Find target option (provided option or current active)
    const activeOption = container.querySelector('.option.active');
    const option = targetOption || activeOption;
    if (!option) return;

    // Calculate position relative to container
    const containerRect = container.getBoundingClientRect();
    const optionRect = option.getBoundingClientRect();
    
    const left = optionRect.left - containerRect.left;
    const width = optionRect.width;

    // Update CSS custom properties
    container.style.setProperty('--slider-left', `${left}px`);
    container.style.setProperty('--slider-width', `${width}px`);

    // Update slider color based on whether we're hovering or showing active
    slider.classList.remove('slider-active', 'slider-hover');
    if (targetOption && targetOption !== activeOption) {
        // Hovering over non-active option
        slider.classList.add('slider-hover');
    } else {
        // On active option or no target (showing active)
        slider.classList.add('slider-active');
    }
}

/**
 * Manually trigger slider update (useful after tab content changes)
 * @param {HTMLElement|string} container - Container element or selector
 */
export function updateTabSlider(container) {
    const element = typeof container === 'string' 
        ? document.querySelector(container)
        : container;
    
    if (element) {
        updateSliderPosition(element);
    }
}

/**
 * Set active tab and update slider position
 * @param {HTMLElement} container - The .tabs container
 * @param {HTMLElement} newActiveOption - The option to make active
 */
export function setActiveTab(container, newActiveOption) {
    // Remove active class from all options in this container
    container.querySelectorAll('.option').forEach(opt => {
        opt.classList.remove('active');
    });
    
    // Add active class to new option
    newActiveOption.classList.add('active');
    
    // Update slider position
    updateSliderPosition(container);
}

/**
 * Initialize on window resize to recalculate positions
 */
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        document.querySelectorAll('.tabs').forEach(container => {
            updateSliderPosition(container);
        });
    }, 100);
});
