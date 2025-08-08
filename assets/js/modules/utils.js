// Event tracker to manage event listeners
export class EventManager {
    constructor() {
        this.listeners = [];
    }

    // Add event listener with tracking
    addEventListener(element, eventType, handler, options) {
        if (!element) return null;

        element.addEventListener(eventType, handler, options);
        const listenerInfo = { element, eventType, handler, options };
        this.listeners.push(listenerInfo);

        return listenerInfo;
    }

    // Remove a specific event listener
    removeEventListener(listenerInfo) {
        if (!listenerInfo || !listenerInfo.element) return;

        listenerInfo.element.removeEventListener(
            listenerInfo.eventType,
            listenerInfo.handler,
            listenerInfo.options
        );

        this.listeners = this.listeners.filter(info => info !== listenerInfo);
    }

    // Remove all event listeners
    removeAllEventListeners() {
        this.listeners.forEach(info => {
            if (info.element) {
                info.element.removeEventListener(
                    info.eventType,
                    info.handler,
                    info.options
                );
            }
        });

        this.listeners = [];
    }

    // Remove event listeners by selector
    removeEventListenersBySelector(selector) {
        const listenersToRemove = this.listeners.filter(info =>
            info.element && info.element.matches && info.element.matches(selector)
        );

        listenersToRemove.forEach(info => {
            info.element.removeEventListener(
                info.eventType,
                info.handler,
                info.options
            );

            this.listeners = this.listeners.filter(item => item !== info);
        });
    }

    // Add delegated event listener (for dynamic content)
    addDelegatedEventListener(parentElement, selector, eventType, handler, options) {
        if (!parentElement) return null;

        const delegatedHandler = function (e) {
            const targetElement = e.target.closest(selector);
            if (targetElement && parentElement.contains(targetElement)) {
                handler.call(targetElement, e, targetElement);
            }
        };

        parentElement.addEventListener(eventType, delegatedHandler, options);
        const listenerInfo = {
            element: parentElement,
            eventType,
            handler: delegatedHandler,
            options,
            isDelegated: true,
            originalHandler: handler,
            selector
        };

        this.listeners.push(listenerInfo);
        return listenerInfo;
    }
}

// DOM manipulation utilities
export function fadeInElement(element) {
    if (!element) return;
    element.style.display = 'block';
    // Force a reflow to ensure the transition works
    element.offsetHeight;
    element.style.opacity = 1;
}

export function fadeOutElement(element) {
    if (!element) return;
    element.style.opacity = 0;
    return new Promise((resolve) => {
        setTimeout(() => {
            element.style.display = 'none';
            resolve();
        }, 500);
    });
}

export function toggleHeightTransition(triggerId, anchorId, contentSelector) {
    const trigger = document.getElementById(triggerId);
    const anchor = document.getElementById(anchorId);
    const content = document.querySelector(contentSelector);

    if (!trigger || !anchor || !content) return;

    const isOpen = anchor.classList.contains('show');

    if (isOpen) {
        // COLLAPSE
        content.style.height = content.scrollHeight + 'px';
        content.offsetHeight; // force reflow
        content.style.height = '0px';
        anchor.classList.remove('show');
    } else {
        // EXPAND
        anchor.classList.add('show');
        content.style.height = content.scrollHeight + 'px';

        content.addEventListener('transitionend', function handler() {
            content.style.height = 'auto';
            content.removeEventListener('transitionend', handler);
        });
    }
}

export function smoothScroll(targetElement, duration = 1000) {
    if (!targetElement) return;

    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;

    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const run = ease(timeElapsed, startPosition, distance, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(animation);
    }

    function ease(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    }

    requestAnimationFrame(animation);
}

// Function to get team abbreviation from ID
export function getTeamAbbr(teamId) {
    const teamMapping = {
        '24': 'ANA',
        '6': 'BOS',
        '7': 'BUF',
        '20': 'CGY',
        '12': 'CAR',
        '16': 'CHI',
        '21': 'COL',
        '29': 'CBJ',
        '25': 'DAL',
        '17': 'DET',
        '22': 'EDM',
        '13': 'FLA',
        '26': 'LAK',
        '30': 'MIN',
        '8': 'MTL',
        '18': 'NSH',
        '1': 'NJD',
        '2': 'NYI',
        '3': 'NYR',
        '9': 'OTT',
        '4': 'PHI',
        '5': 'PIT',
        '28': 'SJS',
        '55': 'SEA',
        '19': 'STL',
        '14': 'TBL',
        '10': 'TOR',
        '59': 'UTA',
        '23': 'VAN',
        '54': 'VGK',
        '15': 'WPG',
        '52': 'WSH'
    };

    return teamMapping[teamId] || '';
}

// Function to get team ID from abbreviation
export function getTeamId(abbr) {
    const teamMapping = {
        'ANA': '24',
        'BOS': '6',
        'BUF': '7',
        'CGY': '20',
        'CAR': '12',
        'CHI': '16',
        'COL': '21',
        'CBJ': '29',
        'DAL': '25',
        'DET': '17',
        'EDM': '22',
        'FLA': '13',
        'LAK': '26',
        'MIN': '30',
        'MTL': '8',
        'NSH': '18',
        'NJD': '1',
        'NYI': '2',
        'NYR': '3',
        'OTT': '9',
        'PHI': '4',
        'PIT': '5',
        'SJS': '28',
        'SEA': '55',
        'STL': '19',
        'TBL': '14',
        'TOR': '10',
        'UTA': '59',
        'VAN': '23',
        'VGK': '54',
        'WPG': '15',
        'WSH': '52'
    };

    return teamMapping[abbr] || '';
}

// Create a singleton instance of the event manager
export const eventManager = new EventManager();

/**
 * Converts UTC time strings to local time across the document
 * @param {boolean} observeChanges - Whether to set up mutation observer for dynamic content
 */
export function convertUTCTimesToLocal(observeChanges = true) {
    // For .theTime elements (with date and time)
    document.querySelectorAll('.theTime').forEach(function (element) {
        const utcTimeString = element.textContent.trim();
        if (utcTimeString) {
            try {
                // Parse the date string properly
                const utcTime = new Date(utcTimeString);

                // Check if the date is valid
                if (!isNaN(utcTime.getTime())) {
                    // Format the date according to the user's locale
                    const options = {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false // Use 24-hour format for international users
                    };

                    // Use browser's locale for formatting
                    const localTime = utcTime.toLocaleString(navigator.language || 'en-US', options);
                    element.textContent = localTime;

                    // Store original UTC time as data attribute for debugging
                    element.setAttribute('data-utc-time', utcTimeString);
                }
            } catch (e) {
                console.error('Error converting time:', e, utcTimeString);
            }
        }
    });

    // For .theTimeSimple elements (time only)
    document.querySelectorAll('.theTimeSimple').forEach(function (element) {
        const utcTimeString = element.textContent.trim();
        if (utcTimeString) {
            try {
                // Parse the date string properly
                const utcTime = new Date(utcTimeString);

                // Check if the date is valid
                if (!isNaN(utcTime.getTime())) {
                    // Format the time according to the user's locale
                    const options = {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false // Use 24-hour format for international users
                    };

                    // Use browser's locale for formatting
                    const localTime = utcTime.toLocaleString(navigator.language || 'en-US', options);
                    element.textContent = localTime;

                    // Store original UTC time as data attribute for debugging
                    element.setAttribute('data-utc-time', utcTimeString);
                }
            } catch (e) {
                console.error('Error converting time:', e, utcTimeString);
            }
        }
    });

    // Set up mutation observer to handle dynamically added time elements
    if (observeChanges) {
        setupTimeObserver();
    }
}

/**
 * Sets up mutation observer to watch for new time elements 
 */
function setupTimeObserver() {
    // Only set up the observer once
    if (window.timeObserver) return;

    window.timeObserver = new MutationObserver(function (mutations) {
        let hasTimeElements = false;

        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length) {
                // Check if any added nodes contain time elements
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (node.classList.contains('theTime') || node.classList.contains('theTimeSimple'))) {
                            hasTimeElements = true;
                        } else if (node.querySelectorAll) {
                            const timeElements = node.querySelectorAll('.theTime, .theTimeSimple');
                            if (timeElements.length > 0) {
                                hasTimeElements = true;
                            }
                        }
                    }
                });
            }
        });

        if (hasTimeElements) {
            // Wait a brief moment to ensure all DOM updates are complete
            setTimeout(() => convertUTCTimesToLocal(false), 50);
        }
    });

    // Observe changes to the entire document to catch all time elements
    window.timeObserver.observe(document.body, { childList: true, subtree: true });
}

export function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Accessibility Features
export function initDropdownKeyboardNavigation() {
    // Handle keyboard events for dropdown labels
    const dropdownLabels = document.querySelectorAll('.menu-links .for-dropdown, .menu-teams .for-dropdown');
    
    dropdownLabels.forEach(label => {
        label.addEventListener('keydown', (e) => {
            // Toggle dropdown with Enter or Space
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const checkbox = label.previousElementSibling;
                if (checkbox && checkbox.type === 'checkbox') {
                    checkbox.checked = !checkbox.checked;
                    
                    // Update aria-expanded attribute
                    label.setAttribute('aria-expanded', checkbox.checked.toString());
                    
                    // Update tabindex for dropdown links
                    updateDropdownLinksTabIndex(label.nextElementSibling, checkbox.checked);
                    
                    // If dropdown is now open, focus first link
                    if (checkbox.checked) {
                        setTimeout(() => {
                            const firstLink = label.nextElementSibling?.querySelector('a');
                            if (firstLink) {
                                firstLink.focus();
                            }
                        }, 100);
                    }
                }
            }
            // Close dropdown with Escape
            else if (e.key === 'Escape') {
                const checkbox = label.previousElementSibling;
                if (checkbox && checkbox.type === 'checkbox' && checkbox.checked) {
                    checkbox.checked = false;
                    label.setAttribute('aria-expanded', 'false');
                    // Update tabindex for dropdown links
                    updateDropdownLinksTabIndex(label.nextElementSibling, false);
                    label.focus();
                }
            }
        });

        // Also handle click events to update aria-expanded and tabindex
        label.addEventListener('click', () => {
            setTimeout(() => {
                const checkbox = label.previousElementSibling;
                if (checkbox && checkbox.type === 'checkbox') {
                    label.setAttribute('aria-expanded', checkbox.checked.toString());
                    // Update tabindex for dropdown links
                    updateDropdownLinksTabIndex(label.nextElementSibling, checkbox.checked);
                }
            }, 0);
        });
    });

    // Handle keyboard navigation within dropdowns
    const dropdownContainers = document.querySelectorAll('.menu-links .section-dropdown, .menu-teams .section-dropdown');
    
    dropdownContainers.forEach(container => {
        container.addEventListener('keydown', (e) => {
            const links = Array.from(container.querySelectorAll('a'));
            const currentIndex = links.indexOf(document.activeElement);
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = currentIndex < links.length - 1 ? currentIndex + 1 : 0;
                    links[nextIndex]?.focus();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : links.length - 1;
                    links[prevIndex]?.focus();
                    break;
                    
                case 'Escape':
                    e.preventDefault();
                    // Close dropdown and focus back to label
                    const menuContainer = container.closest('.menu-links, .menu-teams');
                    const checkbox = menuContainer?.querySelector('input[type="checkbox"]');
                    const label = menuContainer?.querySelector('.for-dropdown');
                    
                    if (checkbox && label) {
                        checkbox.checked = false;
                        label.setAttribute('aria-expanded', 'false');
                        // Update tabindex for dropdown links
                        updateDropdownLinksTabIndex(container, false);
                        label.focus();
                    }
                    break;
                    
                case 'Tab':
                    // Allow normal tab behavior, but close dropdown if tabbing out
                    setTimeout(() => {
                        if (!container.contains(document.activeElement)) {
                            const menuContainer = container.closest('.menu-links, .menu-teams');
                            const checkbox = menuContainer?.querySelector('input[type="checkbox"]');
                            const label = menuContainer?.querySelector('.for-dropdown');
                            if (checkbox && label) {
                                checkbox.checked = false;
                                label.setAttribute('aria-expanded', 'false');
                                // Update tabindex for dropdown links
                                updateDropdownLinksTabIndex(container, false);
                            }
                        }
                    }, 0);
                    break;
            }
        });
    });

    // Initialize all dropdown links as non-focusable by default
    initializeDropdownTabIndex();
}

function updateDropdownLinksTabIndex(dropdownContainer, isOpen) {
    if (!dropdownContainer) return;
    
    const links = dropdownContainer.querySelectorAll('a');
    links.forEach(link => {
        link.setAttribute('tabindex', isOpen ? '0' : '-1');
    });
}

function initializeDropdownTabIndex() {
    // Set all dropdown links to tabindex="-1" initially since dropdowns start closed
    const allDropdownContainers = document.querySelectorAll('.menu-links .section-dropdown, .menu-teams .section-dropdown');
    allDropdownContainers.forEach(container => {
        updateDropdownLinksTabIndex(container, false);
    });
}

export function initDropdownClickOutside() {
    document.addEventListener('click', (e) => {
        const dropdownContainers = document.querySelectorAll('.menu-links, .menu-teams');
        
        dropdownContainers.forEach(container => {
            const checkbox = container.querySelector('input[type="checkbox"]');
            const label = container.querySelector('.for-dropdown');
            const dropdownContent = container.querySelector('.section-dropdown');
            
            // If click is outside the dropdown container and dropdown is open
            if (checkbox && checkbox.checked && !container.contains(e.target)) {
                checkbox.checked = false;
                if (label) {
                    label.setAttribute('aria-expanded', 'false');
                }
                // Update tabindex for dropdown links
                updateDropdownLinksTabIndex(dropdownContent, false);
            }
        });
    });
}

export function initTeamLinksAccessibility() {
    // Add role="menuitem" to all team selection links
    const teamLinks = document.querySelectorAll('.menu-teams .section-dropdown a');
    teamLinks.forEach(link => {
        link.setAttribute('role', 'menuitem');
    });
}

// Player Search Accessibility
export function initPlayerSearchAccessibility() {
    const searchInputs = document.querySelectorAll('#player-search, #player-search-mobile');
    
    searchInputs.forEach(searchInput => {
        // Add ARIA attributes to search input
        searchInput.setAttribute('role', 'combobox');
        searchInput.setAttribute('aria-autocomplete', 'list');
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.setAttribute('aria-haspopup', 'listbox');
        
        const suggestionBox = searchInput.closest('.suggestion-input').querySelector('.suggestion-box');
        if (suggestionBox) {
            suggestionBox.setAttribute('role', 'listbox');
            suggestionBox.setAttribute('aria-label', 'Player suggestions');
        }
        
        // Handle keyboard navigation for search
        searchInput.addEventListener('keydown', handleSearchKeyNavigation);
        
        // Monitor for suggestion box content changes
        observeSuggestionChanges(searchInput, suggestionBox);
    });
}

function handleSearchKeyNavigation(e) {
    const searchInput = e.target;
    const suggestionBox = searchInput.closest('.suggestion-input').querySelector('.suggestion-box');
    
    if (!suggestionBox || suggestionBox.style.display === 'none') return;
    
    const suggestions = Array.from(suggestionBox.querySelectorAll('a[data-link]'));
    const currentFocused = suggestionBox.querySelector('a:focus');
    const currentIndex = currentFocused ? suggestions.indexOf(currentFocused) : -1;
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (suggestions.length > 0) {
                const nextIndex = currentIndex < suggestions.length - 1 ? currentIndex + 1 : 0;
                suggestions[nextIndex]?.focus();
                searchInput.setAttribute('aria-activedescendant', suggestions[nextIndex]?.getAttribute('id') || '');
            }
            break;
            
        case 'ArrowUp':
            e.preventDefault();
            if (suggestions.length > 0) {
                if (currentIndex > 0) {
                    suggestions[currentIndex - 1]?.focus();
                    searchInput.setAttribute('aria-activedescendant', suggestions[currentIndex - 1]?.getAttribute('id') || '');
                } else {
                    // Return focus to search input
                    searchInput.focus();
                    searchInput.removeAttribute('aria-activedescendant');
                }
            }
            break;
            
        case 'Escape':
            e.preventDefault();
            closeSuggestionBox(searchInput, suggestionBox);
            searchInput.focus();
            break;
            
        case 'Enter':
            if (currentFocused) {
                e.preventDefault();
                currentFocused.click();
            }
            break;
            
        case 'Tab':
            // Close suggestions when tabbing away
            setTimeout(() => {
                if (!suggestionBox.contains(document.activeElement) && 
                    document.activeElement !== searchInput) {
                    closeSuggestionBox(searchInput, suggestionBox);
                }
            }, 0);
            break;
    }
}

function observeSuggestionChanges(searchInput, suggestionBox) {
    if (!suggestionBox) return;
    
    const observer = new MutationObserver(() => {
        const suggestions = suggestionBox.querySelectorAll('a[data-link]');
        const isVisible = suggestionBox.style.display !== 'none' && suggestions.length > 0;
        
        // Update aria-expanded based on visibility
        searchInput.setAttribute('aria-expanded', isVisible.toString());
        
        if (isVisible) {
            // Add accessibility attributes to suggestion items
            suggestions.forEach((suggestion, index) => {
                suggestion.setAttribute('role', 'option');
                suggestion.setAttribute('tabindex', '-1');
                if (!suggestion.id) {
                    suggestion.id = `player-suggestion-${index}`;
                }
            });
            
            // Handle keyboard navigation within suggestions
            suggestions.forEach(suggestion => {
                suggestion.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        closeSuggestionBox(searchInput, suggestionBox);
                        searchInput.focus();
                    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        // Let the search input handle arrow navigation
                        searchInput.dispatchEvent(new KeyboardEvent('keydown', {
                            key: e.key,
                            bubbles: true,
                            cancelable: true
                        }));
                        e.preventDefault();
                    }
                });
            });
        }
    });
    
    observer.observe(suggestionBox, { 
        childList: true, 
        subtree: true,
        attributes: true,
        attributeFilter: ['style']
    });
    
    // Store observer for cleanup if needed
    if (!searchInput._suggestionObserver) {
        searchInput._suggestionObserver = observer;
    }
}

function closeSuggestionBox(searchInput, suggestionBox) {
    suggestionBox.style.display = 'none';
    searchInput.value = '';
    searchInput.setAttribute('aria-expanded', 'false');
    searchInput.removeAttribute('aria-activedescendant');
}

// Handle clicks outside search to close suggestions
export function initSearchClickOutside() {
    document.addEventListener('click', (e) => {
        const searchContainers = document.querySelectorAll('.suggestion-input');
        
        searchContainers.forEach(container => {
            const searchInput = container.querySelector('input[type="text"]');
            const suggestionBox = container.querySelector('.suggestion-box');
            
            if (searchInput && suggestionBox && 
                !container.contains(e.target) && 
                suggestionBox.style.display !== 'none') {
                closeSuggestionBox(searchInput, suggestionBox);
            }
        });
    });
}