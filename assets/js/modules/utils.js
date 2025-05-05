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