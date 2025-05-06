import { fadeInElement, fadeOutElement, eventManager } from './utils.js';
import { fixAjaxResponseUrls } from './ajax-handler.js';

// Function to dynamically show the loader in the target container
export function showLoaderInContainer(container) {
    const loader = document.createElement('div');
    loader.id = 'activity-player';
    loader.innerHTML = '<span class="loader"></span>';
    loader.style.position = 'absolute';
    loader.style.top = '50%';
    loader.style.left = '50%';
    loader.style.margin = '0';
    loader.style.transform = 'translate(-50%, -50%)';
    loader.style.zIndex = '10000';
    container.style.position = 'relative'; // Ensure the container has relative positioning
    container.appendChild(loader);
}

// Function to remove the loader from the target container
export function removeLoaderFromContainer(container) {
    const loader = container.querySelector('#activity-player');
    if (loader) {
        container.removeChild(loader);
    }
}

// Add the game log click handler
function setupGameLogHandlers() {
    eventManager.addDelegatedEventListener(document, '.player-game-log', 'click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const playerId = this.dataset.player;
        const seasonSelection = this.dataset.seasonSelection;
        const seasonType = this.dataset.seasonType;
        const playerType = this.dataset.skaterGoalie;
        const playerModal = document.getElementById('playerModalExtra');

        if (playerModal) {
            // Show loading state
            const playerContent = playerModal.querySelector('#playerContent');
            if (playerContent) {
                showLoaderInContainer(playerContent);
            }

            // Fetch game log data
            const xhr = new XMLHttpRequest();
            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.onload = function() {
                if (playerContent) {
                    playerContent.innerHTML = fixAjaxResponseUrls(this.responseText);
                }
                playerModal.showModal();
            };

            xhr.open('POST', baseUrl + '/ajax/player-view-gamelog.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('player=' + playerId + '&season-selection=' + seasonSelection + '&season-type=' + seasonType + '&player-type=' + playerType);
        }
    });
}

// Function to initialize player handlers
export function initPlayerHandlers(elements) {
    setupGameLogHandlers();
    
    // Track the element that had focus before modal opens
    let lastFocusedElement = null;
    
    // Use event delegation for handling player links
    eventManager.addDelegatedEventListener(document, '#player-link:not(.compare-player-item), [id^="player-link"]:not(.compare-player-item)', 'click', function (e) {
        e.preventDefault();
        
        // Store the element that had focus before opening the modal
        lastFocusedElement = document.activeElement;

        fadeInElement(elements.playerActivityElement);
        const player = this.dataset.link;

        // Reset modal content and clear previous event listeners
        elements.playerModal.innerHTML = '';
        eventManager.removeEventListenersBySelector('#career-link, #season-link, #close, .overlay, #graph-toggle');

        // Store original content for toggling
        let seasonViewContent = '';
        let careerViewContent = '';
        let isCareerView = false;
        let isLoading = false;
        let clickCount = 0;
        let isModalOpen = true;
        let chartInitialized = false;
        let advancedStatsLoaded = false;

        function initPlayerModal() {
            const overlay = document.querySelector('.overlay');
            fadeInElement(overlay);

            // Initialize modal content
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                // Insert the HTML content
                elements.playerModal.innerHTML = fixAjaxResponseUrls(this.responseText);
                seasonViewContent = fixAjaxResponseUrls(this.responseText);
                setupModalEventListeners();

                // Extract and execute any script tags from the response
                const scriptContent = this.responseText.match(/<script[^>]*>([\s\S]*?)<\/script>/gi);
                if (scriptContent) {
                    scriptContent.forEach(function (script) {
                        const cleanScript = script.replace(/<\/?script[^>]*>/g, '');
                        eval(cleanScript);
                    });
                }

                // Hide player graph by default
                const playerGraph = elements.playerModal.querySelector('.player-graph');
                const playerGraphInner = elements.playerModal.querySelector('#playerStatsChart');
                if (playerGraph) {
                    playerGraph.style.display = 'none';
                    
                    const isOpen = playerGraph.classList.contains('show');
                    if (isOpen) {
                        // COLLAPSE
                        playerGraphInner.style.height = playerGraphInner.scrollHeight + 'px';
                        playerGraphInner.offsetHeight; // force reflow
                        playerGraphInner.style.height = '0px';
                        playerGraph.classList.remove('show');
                    } else {
                        // EXPAND
                        playerGraph.classList.add('show');
                        playerGraphInner.style.height = playerGraphInner.scrollHeight + 'px';
                
                        playerGraph.addEventListener('transitionend', function handler() {
                            playerGraphInner.style.height = 'auto';
                            playerGraph.removeEventListener('transitionend', handler);
                        });
                    }
                }
                
                // Set focus on the close button after modal content is loaded
                setTimeout(() => {
                    const closeButton = elements.playerModal.querySelector('#close');
                    if (closeButton) {
                        closeButton.focus();
                    }
                    setupFocusTrap(elements.playerModal);
                }, 100);
            };

            xhr.onloadend = function () {
                overlay.classList.add('open');
                document.body.classList.add('no-scroll');
                fadeOutElement(elements.playerActivityElement);
            };

            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/player-view.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('player=' + player);
        }

        initPlayerModal();

        // Function to load advanced stats if they haven't been loaded yet
        function loadAdvancedStats(playerId, callback) {
            if (advancedStatsLoaded) {
                if (callback) callback();
                return;
            }

            // Get the stats container to show the loader in
            const statsContainer = elements.playerModal.querySelector('.stats-player');
            if (statsContainer) {
                showLoaderInContainer(statsContainer);
            }

            const xhr = new XMLHttpRequest();
            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';

            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        // Update the advanced stats in the UI
                        if (response.advancedStats) {
                            const satValue = document.getElementById('sat-value');
                            const usatValue = document.getElementById('usat-value');
                            const evgdValue = document.getElementById('evgd-value');

                            if (satValue) satValue.textContent = response.advancedStats.formattedSAT || 'N/A';
                            if (usatValue) usatValue.textContent = response.advancedStats.formattedUSAT || 'N/A';
                            if (evgdValue) evgdValue.textContent = response.advancedStats.evenStrengthGoalDiff || '0';
                        }

                        advancedStatsLoaded = true;
                        if (statsContainer) {
                            removeLoaderFromContainer(statsContainer);
                        }

                        if (callback) callback();
                    } catch (error) {
                        console.error('Error parsing advanced stats:', error);
                        if (statsContainer) {
                            removeLoaderFromContainer(statsContainer);
                        }
                    }
                } else {
                    console.error('Error loading advanced stats. Status:', xhr.status);
                    if (statsContainer) {
                        removeLoaderFromContainer(statsContainer);
                    }
                }
            };

            xhr.onerror = function () {
                console.error('Network error loading advanced stats');
                if (statsContainer) {
                    removeLoaderFromContainer(statsContainer);
                }
            };

            // Get player type from toggle button's data-needs-stats attribute
            const graphToggle = elements.playerModal.querySelector('#graph-toggle');
            const isSkater = graphToggle ? graphToggle.dataset.needsStats !== 'true' : false;

            xhr.open('POST', baseUrl + '/ajax/player-advanced-stats.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('player=' + playerId + '&isSkater=' + isSkater);
        }

        function setupModalEventListeners() {
            // Clean up any previous handlers to avoid duplicates
            eventManager.removeEventListenersBySelector('#career-link, #season-link, #graph-toggle');

            // Close button handler
            const closeButton = elements.playerModal.querySelector('#close');
            if (closeButton) {
                eventManager.addEventListener(closeButton, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling

                    isModalOpen = false; // Mark modal as closed

                    const overlay = document.querySelector('.overlay');
                    overlay.classList.remove('open');

                    setTimeout(function () {
                        overlay.style.display = 'none';
                        elements.playerModal.innerHTML = '';
                        document.body.classList.remove('no-scroll');
                        eventManager.removeEventListenersBySelector('#career-link, #season-link, #close, .overlay, #graph-toggle');
                        
                        // Return focus to the element that had focus before opening the modal
                        if (lastFocusedElement) {
                            lastFocusedElement.focus();
                        }
                    }, 300);
                });
            }

            // Overlay click handler
            const overlay = document.querySelector('.overlay');
            eventManager.addEventListener(overlay, 'click', function (e) {
                if (!e.target.closest('#player-modal')) {
                    isModalOpen = false; // Mark modal as closed

                    overlay.classList.remove('open');

                    setTimeout(function () {
                        overlay.style.display = 'none';
                        elements.playerModal.innerHTML = '';
                        document.body.classList.remove('no-scroll');
                        eventManager.removeEventListenersBySelector('#career-link, #season-link, #close, .overlay, #graph-toggle');
                        
                        // Return focus to the element that had focus before opening the modal
                        if (lastFocusedElement) {
                            lastFocusedElement.focus();
                        }
                    }, 300);
                }
            });
            
            // Add keyboard event handler for ESC key to close modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isModalOpen) {
                    const closeButton = elements.playerModal.querySelector('#close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            });
        }
    });
    
    // Function to set up focus trap inside the modal
    function setupFocusTrap(modalElement) {
        if (!modalElement) return;
        
        // Get all focusable elements in the modal
        const focusableElements = modalElement.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        // Add keydown event to trap focus inside the modal
        modalElement.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                // Shift + Tab: If focus is on first element, move to last element
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
                // Tab: If focus is on last element, move to first element
                else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
}
