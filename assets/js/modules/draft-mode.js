import { eventManager } from './utils.js';

// Draft mode state management
const DraftMode = {
    state: {
        isActive: false,
        currentRound: 1,
        totalRounds: 20, // 12 forwards + 6 defensemen + 2 goalies
        currentPosition: 'forwards',
        selectedPlayers: [],
        filters: [],
    autoCompleting: false,
        roundConfig: {
            forwards: 12,    // rounds 1-12
            defensemen: 6,   // rounds 13-18
            goalies: 2       // rounds 19-20
        }
    },
    preloadedData: null,     // Cache for preloaded data
    preloadComplete: false,  // Flag to track if preloading is done
    preloadedFilters: [],    // Filters used for preloading
    // Promise for an in-flight preload so callers can wait (bounded) for it
    preloadPromise: null,
    // Flag to avoid repeatedly starting early preload
    preloadTriggeredEarly: false,
    
    setState(newState) {
        Object.assign(this.state, newState);
        this.updateUI();
    },
    
    getCurrentPosition() {
        if (this.state.currentRound <= 12) return 'forwards';
        if (this.state.currentRound <= 18) return 'defensemen';
        return 'goalies';
    },
    
    updateUI() {
        const roundInfo = document.querySelector('.draft-round-info');
        const positionInfo = document.querySelector('.draft-position-info');
        
        if (roundInfo) {
            roundInfo.textContent = `Round ${this.state.currentRound} of ${this.state.totalRounds}`;
        }
        
        if (positionInfo) {
            const position = this.getCurrentPosition();
            const positionRound = this.getPositionRound();
            // Determine max picks for the current position from roundConfig
            const maxByPosition = (this.state.roundConfig && this.state.roundConfig[position])
                ? this.state.roundConfig[position]
                : (position === 'forwards' ? 12 : position === 'defensemen' ? 6 : 2);

            // Show label with uppercase position and PICK X/Y
            positionInfo.innerHTML = `${position.toUpperCase()} - PICK <div class="tag">${positionRound}/${maxByPosition}</div>`;
        }
    },
    
    getPositionRound() {
        if (this.state.currentRound <= 12) return this.state.currentRound;
        if (this.state.currentRound <= 18) return this.state.currentRound - 12;
        return this.state.currentRound - 18;
    }
};

// DOM elements cache
const DOM = {};

// Flag to prevent duplicate event listener registration
let eventListenersInitialized = false;

export function initDraftMode() {
    let retryCount = 0;
    const maxRetries = 5;
    
    // Always initialize event listeners first (uses delegation so works with dynamic content)
    if (!eventListenersInitialized) {
        initializeEventListeners();
        eventListenersInitialized = true;
    }
    
    // Wait for DOM to be ready
    const initWhenReady = () => {
        // Cache DOM elements
        updateDOMCache();
        
        // Only initialize if we have the essential elements
        if (!DOM.draftToggleBtn) {
            // Retry if we're on a team builder page
            const isTeamBuilderPage = document.querySelector('#team-builder-drop-area') ||
                                      document.querySelector('#team-builder-interface') ||
                                      window.location.pathname.includes('team-builder');
            
            if (isTeamBuilderPage && retryCount < maxRetries) {
                retryCount++;
                const delay = Math.min(100 * retryCount, 500); // Max 500ms delay
                setTimeout(initWhenReady, delay);
            }
            return;
        }
        
        // Reset retry count on successful initialization
        retryCount = 0;
        
        // Create draft interface
        createDraftInterface();
        
        // If the draft toggle is present, start an early preload so data is ready
        // by the time the user opens the interface. This is silent and bounded.
        if (DOM.draftToggleBtn && !DraftMode.preloadTriggeredEarly) {
            DraftMode.preloadTriggeredEarly = true;
            // Kick off preload but don't await it here
            DraftMode.preloadPromise = preloadDraftDataSilently();
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenReady);
    } else {
        setTimeout(initWhenReady, 10);
    }
}

function updateDOMCache() {
    Object.assign(DOM, {
        draftToggleBtn: document.getElementById('draft-mode-toggle'),
        draftInterface: document.getElementById('draft-mode-interface'),
        teamBuilderInterface: document.getElementById('team-builder-interface')
    });
}

function initializeDraftMode() {
    // Always initialize event listeners (using delegation)
    initializeEventListeners();
    
    // Create draft interface if we have the button
    if (DOM.draftToggleBtn) {
        createDraftInterface();
    }
}

function initializeEventListeners() {
    // Toggle draft mode - use event delegation to handle dynamically loaded content
    eventManager.addDelegatedEventListener(document, '#draft-mode-toggle', 'click', (e) => {
        e.preventDefault();
        toggleDraftMode();
    });
    
    // Filter toggles
    eventManager.addDelegatedEventListener(document, '.draft-filter-toggle', 'change', handleFilterToggle);
    
    // Player selection - click on player card instead of button
    eventManager.addDelegatedEventListener(document, '.draft-player.clickable', 'click', handlePlayerSelection);
    
    // Start draft button
    eventManager.addDelegatedEventListener(document, '.start-draft-btn', 'click', startDraft);
    
    // NHLPLAY Recommended Preset button
    eventManager.addDelegatedEventListener(document, '.nhlplay-preset-btn', 'click', applyNHLPLAYPreset);
    
    // Exit draft mode
    eventManager.addDelegatedEventListener(document, '.exit-draft-btn', 'click', exitDraftMode);
    
    // Auto-complete draft
    eventManager.addDelegatedEventListener(document, '.auto-complete-btn', 'click', autoCompleteDraft);
}

function createDraftInterface() {
    if (DOM.draftInterface) return; // Already exists
    
    const draftHTML = `
        <div id="draft-mode-interface" class="draft-mode-interface" style="display: none;">
                <div class="draft-header component-header">
                <h3 class="title">Draft Mode</h3>
                <div class="draft-header-actions">
                    <button class="btn auto-complete-btn" style="display:none;">Auto Draft</button>
                    <button class="btn exit-draft-btn">Exit Draft Mode</button>
                </div>
            </div>
            
            <div class="draft-filters">
                <h3>Challenge Filters</h3>
                <p>Enable filters to make the draft more challenging by hiding player information:</p>
                <div class="filters-grid">
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-headshot" name="filter-headshot" class="draft-filter-toggle" value="headshot">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Headshot</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-first-last-name" name="filter-first-last-name" class="draft-filter-toggle" value="first_last_name">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Full Name</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-first-name" name="filter-first-name" class="draft-filter-toggle" value="first_name">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide First Name</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-last-name" name="filter-last-name" class="draft-filter-toggle" value="last_name">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Last Name</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-birth-country" name="filter-birth-country" class="draft-filter-toggle" value="birth_country">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Birth Country</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-team-info" name="filter-team-info" class="draft-filter-toggle" value="team_info">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Team Color</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-team-logo" name="filter-team-logo" class="draft-filter-toggle" value="team_logo">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Team Logo</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-career-stats" name="filter-career-stats" class="draft-filter-toggle" value="career_stats">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Career Stats</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-handedness" name="filter-handedness" class="draft-filter-toggle" value="handedness">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Handedness</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-position" name="filter-position" class="draft-filter-toggle" value="position">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Position</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-age" name="filter-age" class="draft-filter-toggle" value="age">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Age</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-jersey-number" name="filter-jersey-number" class="draft-filter-toggle" value="jersey_number">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Jersey Number</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-height" name="filter-height" class="draft-filter-toggle" value="height">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Height</span>
                    </label>
                    <label class="checkbox-container">
                        <input type="checkbox" id="filter-weight" name="filter-weight" class="draft-filter-toggle" value="weight">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
                        </svg>
                        <span>Hide Weight</span>
                    </label>
                    <button class="btn sm nhlplay-preset-btn"><i class="bi bi-star-fill"></i> NHLPLAY Preset</button>
                </div>
                <div class="draft-buttons">
                    
                    <button class="fancy-button start-draft-btn"><span><i class="bi bi-stars"></i> Start Draft</span></button>
                </div>
            </div>
            
            <div class="draft-active" style="display: none;">
                <div class="draft-progress">
                    <div class="flex-default">
                        <div class="draft-round-info">Round 1 of 9</div>
                        <div class="draft-position-info">Forwards - Pick 1</div>
                    </div>
                    <div class="draft-progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="draft-players-container">
                    <div class="draft-players-grid">
                        <!-- Players will be loaded here -->
                    </div>
                </div>
                
                <div class="draft-selected-players">
                    <div class="depth-chart">
                        <h4>Depth Chart:</h4>
                        <div class="position-counts">
                            <div class="position-count" data-position="C">
                                <span class="position-label">Centers:</span>
                                <span class="count">0</span>
                            </div>
                            <div class="position-count" data-position="L">
                                <span class="position-label">Left Wings:</span>
                                <span class="count">0</span>
                            </div>
                            <div class="position-count" data-position="R">
                                <span class="position-label">Right Wings:</span>
                                <span class="count">0</span>
                            </div>
                            <div class="position-count" data-position="D">
                                <span class="position-label">Defensemen:</span>
                                <span class="count">0</span>
                            </div>
                            <div class="position-count" data-position="G">
                                <span class="position-label">Goalies:</span>
                                <span class="count">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="selected-players-section">
                        <div class="selected-players-list grid grid-300 grid-gap-lg grid-gap-row-lg">
                            <!-- Selected players will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Insert after team builder interface
    if (DOM.teamBuilderInterface) {
        DOM.teamBuilderInterface.insertAdjacentHTML('afterend', draftHTML);
        DOM.draftInterface = document.getElementById('draft-mode-interface');
    }
}

function toggleDraftMode() {
    if (DraftMode.state.isActive) {
        exitDraftMode();
    } else {
        // Check if there are existing players
        const hasExistingPlayers = checkForExistingPlayers();
        
        if (hasExistingPlayers) {
            // Show confirmation dialog only if there are existing players
            const confirmMessage = 'Starting a new draft will clear all players currently on your team. Are you sure you want to continue?';
            
            if (confirm(confirmMessage)) {
                clearAllPlayers();
                enterDraftMode();
            }
        } else {
            // No existing players, start draft immediately
            enterDraftMode();
        }
    }
}

function checkForExistingPlayers() {
    // Check if there are any players in the drop zones
    const playerSlots = document.querySelectorAll('.player-slot .player');
    return playerSlots.length > 0;
}

function clearAllPlayers() {
    // Remove all players from team builder slots using the same method as the clear button
    const playerSlots = document.querySelectorAll('.player-slot');
    const slotsWithPlayers = Array.from(playerSlots).filter(slot => 
        slot.querySelector('.player')
    );
    
    // Batch remove all players
    slotsWithPlayers.forEach(slot => {
        const player = slot.querySelector('.player');
        if (player) player.remove();
    });
    
    // Clear localStorage and update team builder states
    localStorage.removeItem('teamBuilderState');
    
    // Update pool player states if the function exists (from team builder module)
    if (typeof updatePoolPlayerStates === 'function') {
        updatePoolPlayerStates();
    }
    
    // Clear draft state
    DraftMode.setState({ 
        selectedPlayers: [],
        currentRound: 1
    });
    
    // Update depth chart if it exists
    if (typeof updateDepthChart === 'function') {
        updateDepthChart();
    }
}

function enterDraftMode() {
    // Update DOM cache in case elements were loaded via AJAX
    updateDOMCache();
    
    if (!DOM.draftInterface) {
        createDraftInterface();
    }
    
    if (DOM.draftInterface) {
        DOM.draftInterface.style.display = 'block';
    }
    
    if (DOM.teamBuilderInterface) {
        DOM.teamBuilderInterface.style.display = 'none';
    }
    
    if (DOM.draftToggleBtn) {
        DOM.draftToggleBtn.textContent = 'Exit Draft Mode';
    }
    
    DraftMode.setState({ isActive: true });
    
    // Reset draft state
    DraftMode.setState({
        currentRound: 1,
        selectedPlayers: [],
        filters: []
    });
    
    // Reset all filter checkboxes
    resetFilterCheckboxes();
    
    // Clear previously selected players from UI
    clearSelectedPlayersUI();
    
    // Silently preload draft data in the background
    preloadDraftDataSilently();
}

// Silently preload draft data in the background while user is setting up filters
async function preloadDraftDataSilently() {
    // If a preload is already running, return that promise
    if (DraftMode.preloadPromise) return DraftMode.preloadPromise;

    // Create an in-flight promise and store it so callers can await it
    DraftMode.preloadPromise = (async () => {
        try {
            const currentFilters = DraftMode.state.filters || [];
            const positions = ['forwards', 'defensemen', 'goalies'];
            const preloadPromises = positions.map(position => preloadPositionData(position, currentFilters));
            await Promise.all(preloadPromises);
            DraftMode.preloadedFilters = [...currentFilters];
            DraftMode.preloadComplete = true;
        } catch (error) {
            console.warn('Failed to preload draft data:', error);
            DraftMode.preloadComplete = false;
        } finally {
            // Clear the in-flight marker so future preloads can run.
            // Do NOT return the promise itself here (that would resolve a promise to itself).
            DraftMode.preloadPromise = null;
        }
    })();

    return DraftMode.preloadPromise;
}

// Preload data for a specific position
async function preloadPositionData(position, filters = []) {
    const formData = new FormData();
    formData.append('action', 'get_draft_players');
    formData.append('position', position);
    formData.append('round', '1');
    formData.append('filters', JSON.stringify(filters)); // Use current filters
    formData.append('excludePlayerIds', JSON.stringify([])); // No exclusions for preload
    formData.append('preload', 'true'); // Flag to indicate this is preloading
    
    const response = await fetch('ajax/draft-mode.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    
    if (response.ok) {
        const data = await response.json();
        if (!data.error) {
            // Store preloaded data
            if (!DraftMode.preloadedData) {
                DraftMode.preloadedData = {};
            }
            DraftMode.preloadedData[position] = data.players || [];
        }
    }
}

function exitDraftMode() {
    // Update DOM cache
    updateDOMCache();
    
    if (DOM.draftInterface) {
        DOM.draftInterface.style.display = 'none';
    }
    
    if (DOM.teamBuilderInterface) {
        DOM.teamBuilderInterface.style.display = 'block';
    }
    
    if (DOM.draftToggleBtn) {
        DOM.draftToggleBtn.innerHTML = '<i class="bi bi-stars"></i> Draft Mode';
    }
    
    DraftMode.setState({ isActive: false });
    
    // Hide draft interface
    const draftActive = document.querySelector('.draft-active');
    const draftFilters = document.querySelector('.draft-filters');
    
    if (draftActive) draftActive.style.display = 'none';
    if (draftFilters) draftFilters.style.display = 'block';

    // Hide auto-complete button when leaving draft mode
    const autoBtn = document.querySelector('.auto-complete-btn');
    if (autoBtn) autoBtn.style.display = 'none';
}

function handleFilterToggle(e) {
    const filterValue = e.target.value;
    const isChecked = e.target.checked;
    
    // Name filters are mutually exclusive
    const nameFilters = ['first_last_name', 'first_name', 'last_name'];
    
    if (isChecked) {
        // If this is a name filter, uncheck other name filters
        if (nameFilters.includes(filterValue)) {
            // Remove other name filters from state
            DraftMode.state.filters = DraftMode.state.filters.filter(f => !nameFilters.includes(f));
            
            // Uncheck other name filter checkboxes
            nameFilters.forEach(nameFilter => {
                if (nameFilter !== filterValue) {
                    const checkbox = document.querySelector(`.draft-filter-toggle[value="${nameFilter}"]`);
                    if (checkbox) checkbox.checked = false;
                }
            });
        }
        
        // Add the current filter
        if (!DraftMode.state.filters.includes(filterValue)) {
            DraftMode.state.filters.push(filterValue);
        }
    } else {
        // Remove the filter
        DraftMode.state.filters = DraftMode.state.filters.filter(f => f !== filterValue);
    }
    
    // Re-preload data with new filters (debounced to avoid too many requests)
    debouncePreload();
}

// Debounced preload to avoid too many requests when filters change rapidly
let preloadTimeout;
function debouncePreload() {
    clearTimeout(preloadTimeout);
    preloadTimeout = setTimeout(() => {
        // Only re-preload if we're in draft mode but draft hasn't been started (draft-active not visible)
        const draftActiveEl = document.querySelector('.draft-active');
        const draftActiveVisible = draftActiveEl ? (window.getComputedStyle(draftActiveEl).display !== 'none') : false;
        if (DraftMode.state.isActive && !draftActiveVisible) {
            // Reset preload flags and start a fresh preload, storing the promise so callers can await it
            DraftMode.preloadComplete = false;
            DraftMode.preloadedData = null;
            DraftMode.preloadPromise = preloadDraftDataSilently();
        }
    }, 500); // Wait 500ms after last filter change
}

function resetFilterCheckboxes() {
    // Uncheck all filter checkboxes when starting a new draft
    const filterCheckboxes = document.querySelectorAll('.draft-filter-toggle');
    filterCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

function applyNHLPLAYPreset() {
    // Define the NHLPLAY recommended filters
    const recommendedFilters = [
        'headshot',       // Hide Headshot
        'last_name',      // Hide Last Name  
        'career_stats',   // Hide Career Stats
        'jersey_number',  // Hide Jersey Number
        'team_info',      // Hide Team Info
        'team_logo'       // Hide Team Logo
    ];
    
    // Clear all current filters
    DraftMode.setState({ filters: [] });
    resetFilterCheckboxes();
    
    // Apply the recommended filters
    recommendedFilters.forEach(filterValue => {
        const checkbox = document.querySelector(`.draft-filter-toggle[value="${filterValue}"]`);
        if (checkbox) {
            checkbox.checked = true;
            
            // Add to filters state
            if (!DraftMode.state.filters.includes(filterValue)) {
                DraftMode.state.filters.push(filterValue);
            }
        }
    });
    
    // Trigger re-preload with new filters
    debouncePreload();
}

function clearSelectedPlayersUI() {
    // Clear the selected players list UI
    const selectedPlayersList = document.querySelector('.selected-players-list');
    if (selectedPlayersList) {
        selectedPlayersList.innerHTML = '';
    }
    
    // Clear the draft players grid (previous round's player cards)
    const draftPlayersGrid = document.querySelector('.draft-players-grid');
    if (draftPlayersGrid) {
    draftPlayersGrid.innerHTML = '';
    // Restore display in case it was hidden by auto-complete or completion overlay
    try { draftPlayersGrid.style.display = ''; } catch (e) { /* ignore */ }
    }
    
    // Reset progress bar
    const progressBar = document.querySelector('.progress-fill');
    if (progressBar) {
        progressBar.style.width = '0%';
    }
    
    // Reset depth chart counts to 0
    const positionCounts = document.querySelectorAll('.position-count .count');
    positionCounts.forEach(countElement => {
        countElement.textContent = '0';
    });
    
    // Clear preloaded data to force fresh loading for new draft
    DraftMode.preloadedData = null;
    DraftMode.preloadComplete = false;
    DraftMode.preloadedFilters = [];
}

async function startDraft() {
    // Hide filters, show draft interface
    document.querySelector('.draft-filters').style.display = 'none';
    document.querySelector('.draft-active').style.display = 'block';
    
    DraftMode.setState({ currentRound: 1 });
    
    // Check if current filters match preloaded data
    const currentFilters = JSON.stringify(DraftMode.state.filters);
    const preloadedFilters = JSON.stringify(DraftMode.preloadedFilters || []);
    
    // Fast-path: if a preload is in-flight, wait a short, bounded time for it to complete
    if (!DraftMode.preloadComplete && DraftMode.preloadPromise) {
        try {
            // Wait up to 800ms for preload to finish
            await promiseWithTimeout(DraftMode.preloadPromise, 800);
        } catch (err) {
            // Timed out or failed; continue without blocking
        }
    }

    // If data is preloaded AND filters match, use it for instant loading
    if (DraftMode.preloadComplete && DraftMode.preloadedData && currentFilters === preloadedFilters) {
        await loadRoundPlayersFromCache();
    } else {
        // If filters changed or no preload, do regular loading with current filters
        await loadRoundPlayers();
    }
    // Reveal auto-complete button now that draft is active
    showAutoCompleteButton();
}

// Show auto-complete button when draft starts
function showAutoCompleteButton() {
    const btn = document.querySelector('.auto-complete-btn');
    if (btn) btn.style.display = '';
}

// Load round players from preloaded cache for instant loading
async function loadRoundPlayersFromCache() {
    const position = DraftMode.getCurrentPosition();
    
    if (!DraftMode.preloadedData || !DraftMode.preloadedData[position]) {
        // Fallback to regular loading if cache is missing
        await loadRoundPlayers();
        return;
    }
    
    try {
        const selectedPlayerIds = DraftMode.state.selectedPlayers.map(selected => selected.player.id);
        const availablePlayers = DraftMode.preloadedData[position].filter(playerHtml => {
            // Extract player ID from HTML (simple approach)
            const idMatch = playerHtml.match(/data-player-data='[^']*"id"[^']*?([0-9]+)/);
            return idMatch ? !selectedPlayerIds.includes(idMatch[1]) : true;
        });
        
        // Randomly select 3 players
        const shuffled = availablePlayers.sort(() => 0.5 - Math.random());
        const roundPlayers = shuffled.slice(0, 3);
        
        displayRoundPlayers(roundPlayers);
        
    } catch (error) {
        console.warn('Error using cached data, falling back to server request:', error);
        await loadRoundPlayers();
    }
}

async function loadRoundPlayers() {
    const position = DraftMode.getCurrentPosition();
    let loadingIndicator = null;
    
    try {
        loadingIndicator = showLoadingIndicator('Loading players...');
        
        const formData = new FormData();
        formData.append('action', 'get_draft_players');
        formData.append('position', position);
        formData.append('round', DraftMode.state.currentRound);
        formData.append('filters', JSON.stringify(DraftMode.state.filters));
        
        // Send already selected player IDs to exclude them
        const selectedPlayerIds = DraftMode.state.selectedPlayers.map(selected => selected.player.id);
        formData.append('excludePlayerIds', JSON.stringify(selectedPlayerIds));
        
        const response = await fetch('ajax/draft-mode.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        displayRoundPlayers(data.players);
        
    } catch (error) {
        console.error('Error loading round players:', error);
        console.error('Error details:', error.message);
        alert('Error loading players: ' + error.message);
    } finally {
        if (loadingIndicator) {
            hideLoadingIndicator(loadingIndicator);
        }
    }
}

function displayRoundPlayers(playersHtml) {
    // During auto-complete we don't render per-pick cards to avoid flashing the UI
    if (DraftMode.state.autoCompleting) {
        // Still update progress bar so user sees overall progress if UI is visible
        const progressPercentSilent = (DraftMode.state.currentRound / DraftMode.state.totalRounds) * 100;
        const progressBarSilent = document.querySelector('.progress-fill');
        if (progressBarSilent) {
            progressBarSilent.style.transition = 'width 0.1s linear';
            progressBarSilent.style.width = progressPercentSilent + '%';
        }
        return;
    }

    const container = document.querySelector('.draft-players-grid');
    if (container) {
        // Clear container and add new content
        container.innerHTML = playersHtml.join('');
        
        // Get all the new player cards and initially hide them
        const playerCards = container.querySelectorAll('.draft-player');
        // Ensure each draft-player has the data-tilt attribute (no value) for tilt library/init
        playerCards.forEach(card => {
            try {
                // Prefer toggleAttribute to add attribute without a value in supporting browsers
                if (typeof card.toggleAttribute === 'function') {
                    card.toggleAttribute('data-tilt', true);
                } else {
                    // Fallback: set empty string (attribute presence)
                    card.setAttribute('data-tilt', '');
                }
            } catch (e) { /* ignore */ }
        });
        playerCards.forEach(card => {
            // Start hidden with opacity 0 and slightly scaled down
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9) translateY(20px)';
            card.style.transition = 'all 0.4s ease-out';
        });
        
        // Wait for all images in the cards to load before animating
        const allImages = container.querySelectorAll('img');
        const imagePromises = Array.from(allImages).map(img => {
            return new Promise((resolve) => {
                if (img.complete) {
                    resolve();
                } else {
                    img.onload = () => resolve();
                    img.onerror = () => resolve(); // Resolve even on error to prevent hanging
                }
            });
        });
        
        // Once all images are loaded, animate the cards in
        Promise.all(imagePromises).then(() => {
            playerCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1) translateY(0)';
                }, 100 + (index * 150)); // 100ms base delay + 150ms stagger per card
            });
            
            // Initialize tilt on the player cards if a tilt library is available.
            try {
                const cardsArray = Array.from(playerCards).filter(c => c.hasAttribute && c.hasAttribute('data-tilt'));
                if (!cardsArray.length) return;

                // Delay init until after the staggered animation completes to avoid race conditions
                const maxDelay = 100 + (playerCards.length * 150) + 50;
                setTimeout(() => {
                    // If VanillaTilt is available, destroy any existing instances then init with options
                    const vtOptions = {
                        max: 10,
                        speed: 500,
                        glare: true,
                        "max-glare": 0.8,
                        perspective: 1000,
                        scale: 1.03
                    };

                    const initWithVanillaTilt = (vt) => {
                        try {
                            // Initialize only the cards that don't already have a VanillaTilt instance.
                            const toInit = cardsArray.filter(el => !el.vanillaTilt);
                            if (toInit.length) {
                                vt.init(toInit, vtOptions);
                            }
                            // After initial init, ensure glare elements exist — re-init elements missing glare
                            ensureGlareExists(cardsArray, vt, vtOptions);
                        } catch (e) {
                            console.warn('VanillaTilt init error:', e);
                        }
                    };

                    // Ensure glare elements exist by re-initializing elements that lack them.
                    function ensureGlareExists(cards, vtLib, options, attempt = 0) {
                        try {
                            if (!cards || !cards.length) return;
                            // If vtLib isn't ready, try again later (for lazy-loaded script)
                            if (!vtLib && !window.VanillaTilt) {
                                if (attempt < 6) {
                                    setTimeout(() => ensureGlareExists(cards, window.VanillaTilt, options, attempt + 1), 200);
                                }
                                return;
                            }
                            const vt = vtLib || window.VanillaTilt;
                            cards.forEach(el => {
                                if (!document.contains(el)) return;
                                // Check for vanilla-tilt's glare element
                                const hasGlare = el.querySelector && el.querySelector('.js-tilt-glare');
                                if (hasGlare) return;
                                try {
                                    if (el.vanillaTilt && typeof el.vanillaTilt.destroy === 'function') {
                                        el.vanillaTilt.destroy();
                                    }
                                } catch (e) { /* ignore */ }
                                try {
                                    vt.init([el], options);
                                } catch (e) {
                                    // If init fails and we have attempts left, retry
                                    if (attempt < 6) {
                                        setTimeout(() => ensureGlareExists([el], vt, options, attempt + 1), 200);
                                    }
                                }
                            });
                        } catch (err) {
                            if (attempt < 6) {
                                setTimeout(() => ensureGlareExists(cards, vtLib, options, attempt + 1), 200);
                            }
                        }
                    }

                    if (window.VanillaTilt && typeof window.VanillaTilt.init === 'function') {
                        initWithVanillaTilt(window.VanillaTilt);
                    } else if (typeof VanillaTilt !== 'undefined' && typeof VanillaTilt.init === 'function') {
                        initWithVanillaTilt(VanillaTilt);
                    } else if (typeof window.initTilt === 'function') {
                        // Optional project-specific initializer
                        try { window.initTilt(cardsArray); } catch (e) { console.warn('window.initTilt failed:', e); }
                    } else {
                        // Lazy-load VanillaTilt from CDN if not present. This handles AJAX-inserted pages
                        try {
                            const CDN = 'https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js';
                            // Avoid adding the script multiple times
                            let existing = document.querySelector(`script[src="${CDN}"]`);
                            if (existing) {
                                // If already loaded, attempt init when available
                                if (window.VanillaTilt && typeof window.VanillaTilt.init === 'function') {
                                    initWithVanillaTilt(window.VanillaTilt);
                                } else {
                                    existing.addEventListener('load', () => {
                                        if (window.VanillaTilt && typeof window.VanillaTilt.init === 'function') {
                                            initWithVanillaTilt(window.VanillaTilt);
                                        }
                                    });
                                }
                            } else {
                                const s = document.createElement('script');
                                s.src = CDN;
                                s.async = true;
                                s.onload = () => {
                                    if (window.VanillaTilt && typeof window.VanillaTilt.init === 'function') {
                                        initWithVanillaTilt(window.VanillaTilt);
                                    }
                                };
                                s.onerror = () => {
                                    console.warn('Failed to load VanillaTilt from CDN:', CDN);
                                };
                                document.head.appendChild(s);
                            }
                        } catch (e) {
                            console.info('VanillaTilt not found and lazy-load failed:', e);
                        }
                    }
                }, maxDelay);
            } catch (err) {
                // Non-fatal: tilt is optional
                // eslint-disable-next-line no-console
                console.warn('Tilt init failed:', err);
            }
        });
    }
    
    // Update progress bar with animation
    const progressPercent = (DraftMode.state.currentRound / DraftMode.state.totalRounds) * 100;
    const progressBar = document.querySelector('.progress-fill');
    if (progressBar) {
        progressBar.style.transition = 'width 0.5s ease-out';
        progressBar.style.width = progressPercent + '%';
    }

    document.querySelectorAll(".draft-player").forEach(card => {
        card.addEventListener("tiltChange", e => {
            const { tiltX, tiltY } = e.detail;

            const x = 50 + tiltX * 2;
            const y = 50 + tiltY * 2;
            card.style.setProperty("--foil-shift", `${x}% ${y}%`);

            // rainbow hue rotation
            const hue = (tiltX + tiltY) * 12;
            card.style.setProperty("--foil-hue", `${hue}deg`);

            const intensity = Math.sqrt(tiltX * tiltX + tiltY * tiltY); 
            // map ~0–15deg to 0.05–0.3 opacity
            const opacity = 0 + Math.min(intensity / 15, 1) * (0.2 - 0);
            card.style.setProperty("--foil-opacity", opacity.toFixed(3));
        });
    });
}

async function handlePlayerSelection(e) {
    const playerCard = e.target.closest('.draft-player');
    if (!playerCard) return;
    
    const playerData = JSON.parse(playerCard.dataset.playerData);
    
    // Check if player is already selected
    const alreadySelected = DraftMode.state.selectedPlayers.some(selected => selected.player.id === playerData.id);
    if (alreadySelected) {
        // If auto-completing, silently skip duplicates. Otherwise show alert.
        if (!DraftMode.state.autoCompleting) {
            alert('This player has already been selected!');
        }
        return;
    }

    if (DraftMode.state.autoCompleting) {
        // Lightweight selection: don't animate or update per-pick UI to keep it fast and silent
        DraftMode.state.selectedPlayers.push({
            round: DraftMode.state.currentRound,
            position: DraftMode.getCurrentPosition(),
            player: playerData
        });
        // Update depth chart counts only (no DOM for selected list)
        updateDepthChart();
    } else {
        // Animate the selected card
        await animateCardSelection(playerCard);
        
        // Add to selected players
        DraftMode.state.selectedPlayers.push({
            round: DraftMode.state.currentRound,
            position: DraftMode.getCurrentPosition(),
            player: playerData
        });
        
        // Display selected player
        displaySelectedPlayer(playerData);
    }
    
    // Move to next round
    if (DraftMode.state.currentRound < DraftMode.state.totalRounds) {
        DraftMode.setState({ currentRound: DraftMode.state.currentRound + 1 });
        await loadRoundPlayers();
    } else {
        // Draft complete
        completeDraft();
    }
}

function animateCardSelection(playerCard) {
    return new Promise((resolve) => {
        // Prevent extra clicks while animating
        try {
            const container = playerCard.closest('.draft-players-grid') || playerCard.parentElement;

            // Mark the selected card with a class so CSS can style it
            playerCard.classList.add('selected');
            // Keep pointer events disabled on the selected card to avoid double clicks
            playerCard.style.pointerEvents = 'none';

            // Dim and scale down the other cards
            if (container) {
                const siblings = Array.from(container.querySelectorAll('.draft-player'))
                    .filter(c => c !== playerCard);

                siblings.forEach((card) => {
                    // Disable interactions
                    card.style.pointerEvents = 'none';
                    // Animate to dimmed, scaled state
                    card.style.transition = 'all 1s linear';
                    card.style.filter = 'blur(10px)';
                    card.style.opacity = '0';
                });
            }

            // Allow the selected visual to settle, then resolve.
            // Keep timings similar to previous behavior: brief emphasis, then settle
            setTimeout(() => {
                // After settle, keep selected class for external CSS control.
                // Resolve so caller can continue (load next round etc.)
                resolve();
            }, 500);
        } catch (err) {
            // In case of any error, resolve to avoid blocking the flow
            // eslint-disable-next-line no-console
            console.warn('animateCardSelection fallback:', err);
            resolve();
        }
    });
}

function displaySelectedPlayer(player) {
    const container = document.querySelector('.selected-players-list');
    if (!container) return;
    
    const playerElement = document.createElement('div');
    // convert first name to display only first letter and period
    const playerFirstName = player.firstName?.default ? player.firstName.default.charAt(0) + '.' : '';
    playerElement.className = 'selected-player-summary';
    playerElement.innerHTML = `
        <div class="player-summary">
            <img src="${player.headshot}" alt="${player.firstName?.default} ${player.lastName?.default}" width="40" height="40">
            <span>${playerFirstName} ${player.lastName?.default}</span>
            <span class="round-info">Round ${DraftMode.state.currentRound}</span>
        </div>
    `;
    
    container.appendChild(playerElement);
    
    // Update depth chart
    updateDepthChart();
}

function updateDepthChart() {
    // Count players by position
    const positionCounts = {
        'C': 0,   // Centers
        'L': 0,   // Left Wings
        'R': 0,   // Right Wings
        'D': 0,   // Defensemen
        'G': 0    // Goalies
    };
    
    // Count selected players by position
    DraftMode.state.selectedPlayers.forEach(selected => {
        const positionCode = selected.player.positionCode;
        if (positionCounts.hasOwnProperty(positionCode)) {
            positionCounts[positionCode]++;
        }
    });
    
    // Update display
    Object.keys(positionCounts).forEach(position => {
        const countElement = document.querySelector(`.position-count[data-position="${position}"] .count`);
        if (countElement) {
            countElement.textContent = positionCounts[position];
        }
    });
}

async function completeDraft() {
    // Show completion message with countdown inside draft-players-container
    // Don't hide draft-active yet - let the completion message handle the exit
    showDraftCompletionMessage();
    
    // Transfer players to team builder
    await transferPlayersToTeamBuilder();
}

function showDraftCompletionMessage() {
    const draftContainer = document.querySelector('.draft-players-container');
    if (!draftContainer) {
        // Fallback to alert if container not found
        alert('Draft completed! Your team has been built.');
        exitDraftMode();
        return;
    }
    
    // Make sure the container has relative positioning
    const currentPosition = window.getComputedStyle(draftContainer).position;
    if (currentPosition === 'static') {
        draftContainer.style.position = 'relative';
    }
    
    // Create completion message overlay
    const completionOverlay = document.createElement('div');
    completionOverlay.className = 'draft-completion-overlay';
    // Ensure overlay sits above other elements
    completionOverlay.style.zIndex = '999999';
    
    // Create success icon
    const successIcon = document.createElement('div');
    successIcon.className = 'success-icon';
    successIcon.innerHTML = '🏆';
    
    // Create completion message
    const completionMessage = document.createElement('div');
    completionMessage.className = 'completion-message';
    completionMessage.textContent = 'Draft Completed!';
    
    // Create subtitle
    const subtitle = document.createElement('div');
    subtitle.className = 'completion-subtitle';
    subtitle.textContent = 'Your team has been successfully built and transferred to the team builder.';
    
    // Create countdown container
    const countdownContainer = document.createElement('div');
    countdownContainer.className = 'countdown-container';
    
    const countdownText = document.createElement('span');
    countdownText.textContent = 'Redirecting to team builder in ';
    
    const countdownNumber = document.createElement('span');
    countdownNumber.className = 'countdown-number';
    
    countdownContainer.appendChild(countdownText);
    countdownContainer.appendChild(countdownNumber);
    countdownContainer.appendChild(document.createTextNode(' seconds...'));
    
    // Create skip button
    const skipButton = document.createElement('button');
    skipButton.className = 'btn skip-button';
    skipButton.textContent = 'View Team Now';
    skipButton.addEventListener('click', () => {
        clearInterval(countdownInterval);
        // Restore players grid display from saved value on the overlay
        try {
            const playersGrid = document.querySelector('.draft-players-grid');
            const prev = completionOverlay.dataset.prevPlayersGridDisplay;
            if (playersGrid) {
                if (typeof prev !== 'undefined' && prev !== null && prev !== '') {
                    playersGrid.style.display = prev;
                } else {
                    playersGrid.style.display = '';
                }
            }
        } catch (e) { /* ignore */ }
        completionOverlay.remove();
        exitDraftMode();
    });
    
    // Assemble the completion overlay
    completionOverlay.appendChild(successIcon);
    completionOverlay.appendChild(completionMessage);
    completionOverlay.appendChild(subtitle);
    completionOverlay.appendChild(countdownContainer);
    completionOverlay.appendChild(skipButton);
    
    // Add to container
    draftContainer.appendChild(completionOverlay);

    // Hide the players grid while the completion overlay is visible and
    // save the previous display value onto the overlay so we can restore it
    try {
        const playersGrid = document.querySelector('.draft-players-grid');
        const prevDisplay = playersGrid ? playersGrid.style.display : '';
        completionOverlay.dataset.prevPlayersGridDisplay = (typeof prevDisplay !== 'undefined' && prevDisplay !== null) ? prevDisplay : '';
        if (playersGrid) {
            playersGrid.style.display = 'none';
        }
    } catch (e) { /* ignore */ }
    
    // Start countdown
    let timeLeft = 5;
    countdownNumber.textContent = timeLeft;
    
    const countdownInterval = setInterval(() => {
        timeLeft--;
        countdownNumber.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            // Restore players grid display from saved value on the overlay
            try {
                const playersGrid = document.querySelector('.draft-players-grid');
                const prev = completionOverlay.dataset.prevPlayersGridDisplay;
                if (playersGrid) {
                    if (typeof prev !== 'undefined' && prev !== null && prev !== '') {
                        playersGrid.style.display = prev;
                    } else {
                        playersGrid.style.display = '';
                    }
                }
            } catch (e) { /* ignore */ }

            completionOverlay.remove();
            exitDraftMode();
        }
    }, 1000);
}

// Auto-complete the entire draft by selecting the first available player each round
async function autoCompleteDraft(e) {
    const btn = e ? e.target : document.querySelector('.auto-complete-btn');
    if (btn) btn.disabled = true;

    DraftMode.setState({ autoCompleting: true });
    // Show loading overlay inside the players container. Keep the draft-active
    // area visible so the overlay (which is appended into .draft-players-container)
    // is actually shown. Hide only the filters area. Also hide the players grid
    // so stale cards aren't visible while auto-drafting.
    const loading = showLoadingIndicator('Auto-completing draft...');
    const draftActive = document.querySelector('.draft-active');
    const draftFilters = document.querySelector('.draft-filters');
    if (draftActive) draftActive.style.display = 'block';
    if (draftFilters) draftFilters.style.display = 'none';
    // Ensure the players container is visible so the overlay has a parent to render into
    const playersContainer = document.querySelector('.draft-players-container');
    if (playersContainer) playersContainer.style.display = 'block';

    // Hide player cards grid during auto-complete to avoid showing stale cards
    const playersGrid = document.querySelector('.draft-players-grid');
    // Preserve previous inline display value so we can restore it
    const prevPlayersGridDisplay = playersGrid ? playersGrid.style.display : null;
    if (playersGrid) playersGrid.style.display = 'none';

    try {
        // Ensure draft is active
        if (!DraftMode.state.isActive) {
            await startDraft();
        }

        // Keep a set of already selected IDs to pass to API
        const selectedIds = new Set(DraftMode.state.selectedPlayers.map(s => s.player.id));

        while (DraftMode.state.currentRound <= DraftMode.state.totalRounds) {
            const position = DraftMode.getCurrentPosition();

            // Update loading message with progress
            if (loading && loading.children && loading.children[1]) {
                loading.children[1].textContent = `Auto-completing: Round ${DraftMode.state.currentRound} of ${DraftMode.state.totalRounds}`;
            }

            // Call API for this round, asking for random players
            const formData = new FormData();
            formData.append('action', 'get_draft_players');
            formData.append('position', position);
            formData.append('round', DraftMode.state.currentRound);
            formData.append('filters', JSON.stringify(DraftMode.state.filters || []));
            formData.append('excludePlayerIds', JSON.stringify(Array.from(selectedIds)));

            const resp = await fetch('ajax/draft-mode.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!resp.ok) {
                throw new Error(`HTTP ${resp.status}`);
            }

            const data = await resp.json();
            if (data.error) {
                throw new Error(data.error);
            }

            const playersHtml = data.players || [];
            if (!playersHtml.length) {
                // No players returned; try next round or break
                DraftMode.setState({ currentRound: DraftMode.state.currentRound + 1 });
                continue;
            }

            // Parse the first returned player HTML and extract data-player-data
            let picked = null;
            for (const html of playersHtml) {
                const tmp = document.createElement('div');
                tmp.innerHTML = html;
                const el = tmp.querySelector('[data-player-data]');
                if (!el) continue;
                const raw = el.getAttribute('data-player-data');
                try {
                    const playerObj = JSON.parse(raw);
                    if (!selectedIds.has(playerObj.id)) {
                        picked = playerObj;
                        break;
                    }
                } catch (err) {
                    // skip malformed
                    continue;
                }
            }

            if (!picked) {
                // nothing suitable, skip to next round
                DraftMode.setState({ currentRound: DraftMode.state.currentRound + 1 });
                continue;
            }

            // Add to state
            DraftMode.state.selectedPlayers.push({
                round: DraftMode.state.currentRound,
                position: position,
                player: picked
            });
            selectedIds.add(picked.id);

            // Live-update selected players section and depth chart while auto-drafting
            try {
                // Display the selected player in the UI (keeps same contract as manual selection)
                displaySelectedPlayer(picked);
            } catch (e) { /* ignore UI errors */ }

            try {
                updateDepthChart();
            } catch (e) { /* ignore */ }

            // Advance round
            DraftMode.setState({ currentRound: DraftMode.state.currentRound + 1 });

            // Update progress bar so user sees auto-draft progress
            try {
                const progressBar = document.querySelector('.progress-fill');
                if (progressBar) {
                    const progressPercent = (DraftMode.state.currentRound / DraftMode.state.totalRounds) * 100;
                    progressBar.style.transition = 'width 0.25s linear';
                    progressBar.style.width = progressPercent + '%';
                }
            } catch (e) { /* ignore */ }

            // Small delay to avoid hammering the server
            await new Promise(res => setTimeout(res, 120));
        }

        // Completed selection loop
        await completeDraft();

    } catch (err) {
        console.error('Auto-complete error:', err);
        alert('Auto-complete failed: ' + (err.message || err));
    } finally {
        DraftMode.setState({ autoCompleting: false });
        // Restore players grid visibility
        try {
            // If the completion overlay is present, let it handle restoring the grid
            const completionOverlay = document.querySelector('.draft-completion-overlay');
            if (!completionOverlay) {
                const playersGridRestore = document.querySelector('.draft-players-grid');
                if (playersGridRestore) {
                    // If we saved a previous display value, restore it; otherwise clear the inline style
                    if (typeof prevPlayersGridDisplay !== 'undefined' && prevPlayersGridDisplay !== null) {
                        playersGridRestore.style.display = prevPlayersGridDisplay;
                    } else {
                        playersGridRestore.style.display = '';
                    }
                }
            }
        } catch (e) { /* ignore */ }

        if (loading) hideLoadingIndicator(loading);
        if (btn) btn.disabled = false;
    }
}

async function transferPlayersToTeamBuilder() {
    // Clear existing slots
    document.querySelectorAll('.player-slot').forEach(slot => {
        slot.innerHTML = '';
    });
    
    // Group players by position
    const forwards = DraftMode.state.selectedPlayers.filter(p => p.position === 'forwards');
    const defensemen = DraftMode.state.selectedPlayers.filter(p => p.position === 'defensemen');
    const goalies = DraftMode.state.selectedPlayers.filter(p => p.position === 'goalies');
    
    // Place players in slots
    placePlayersInSlots('forward', forwards);
    placePlayersInSlots('defenseman', defensemen);
    placePlayersInSlots('goalie', goalies);

    // Persist state to localStorage in the same shape as TeamBuilder.getSerializableState()
    try {
        const buildPositionState = (position) => {
            const slots = document.querySelectorAll(`.player-slot[data-position="${position}"]`);
            const state = [];
            slots.forEach((slot, index) => {
                const playerEl = slot.querySelector('.player');
                if (playerEl) {
                    state[index] = {
                        name: playerEl.querySelector('.name') ? playerEl.querySelector('.name').textContent : '',
                        playerId: playerEl.dataset.playerId,
                        teamId: playerEl.dataset.teamId,
                        slotIndex: index
                    };
                }
            });
            return state.filter(Boolean);
        };

        const serialized = {
            activeTeam: window.activeTeam || null,
            forward: buildPositionState('forward'),
            defenseman: buildPositionState('defenseman'),
            goalie: buildPositionState('goalie')
        };

        localStorage.setItem('teamBuilderState', JSON.stringify(serialized));

        // Update pool player states if function exists (refresh UI to mark used players)
        if (typeof updatePoolPlayerStates === 'function') {
            updatePoolPlayerStates();
        }
    } catch (err) {
        console.warn('Failed to persist drafted team builder state:', err);
    }
}

function placePlayersInSlots(position, selectedPlayers) {
    const slots = document.querySelectorAll(`.player-slot[data-position="${position}"]`);
    
    selectedPlayers.forEach((selectedPlayer, index) => {
        if (index < slots.length) {
            const slot = slots[index];
            const player = selectedPlayer.player;
            
            // Create player element similar to team builder format
            const playerElement = document.createElement('div');
            playerElement.className = `player ${position}`;
            playerElement.dataset.teamId = player.teamId;
            playerElement.dataset.playerId = player.id;
            playerElement.style.cursor = 'grab';
            
            const teamColor = getTeamColor(player.teamId);
            const teamLogo = getTeamLogo(player.teamId);
            const fullName = `${player.firstName?.default || ''} ${player.lastName?.default || ''}`;
            const positionName = getPositionName(player.positionCode);
            
            playerElement.innerHTML = `
                <div class="jersey"><span>#</span>${player.sweaterNumber}</div>
                <div class="info">
                    <div class="headshot">
                        <img class="head" loading="lazy" height="400" width="400" src="${player.headshot}" alt="${fullName}">
                        <img class="team-img" loading="lazy" height="600" width="600" src="${teamLogo}" alt="Team logo">
                    </div>
                    <div class="text">
                        <div class="position">${positionName}</div>
                        <div class="name">${fullName}</div>
                    </div>
                </div>
            `;
            
            playerElement.style.backgroundImage = `linear-gradient(142deg, ${teamColor} -100%, rgba(255,255,255,0) 70%)`;
            
            slot.appendChild(playerElement);
        }
    });
}

// Utility functions - these should use global functions if available
function getTeamColor(teamId) {
    // Use global teamToColor function if available, otherwise fallback
    return window.teamToColor ? window.teamToColor(teamId) : '#041e42';
}

function getTeamLogo(teamId) {
    // Use global getTeamLogo function if available, otherwise fallback
    return window.getTeamLogo ? window.getTeamLogo(teamId) : `assets/img/teams/${teamId}.svg`;
}

function getPositionName(positionCode) {
    // Use existing position conversion functions or fallback
    if (window.positionCodeToName) {
        return window.positionCodeToName(positionCode);
    }
    
    const positions = { 
        'C': 'Center', 
        'L': 'Left Wing', 
        'R': 'Right Wing', 
        'D': 'Defense', 
        'G': 'Goalie' 
    };
    return positions[positionCode] || positionCode;
}

// Helper: await a promise but reject if it doesn't finish within ms
function promiseWithTimeout(promise, ms) {
    return new Promise((resolve, reject) => {
        let settled = false;
        const timer = setTimeout(() => {
            if (!settled) {
                settled = true;
                reject(new Error('timeout'));
            }
        }, ms);

        promise.then((v) => {
            if (!settled) {
                settled = true;
                clearTimeout(timer);
                resolve(v);
            }
        }).catch((err) => {
            if (!settled) {
                settled = true;
                clearTimeout(timer);
                reject(err);
            }
        });
    });
}

function showLoadingIndicator(message) {
    // Use team builder's loading indicator if available
    if (window.showLoadingIndicator) {
        return window.showLoadingIndicator(message);
    }
    
    // Create loading indicator inside .draft-players-container
    const draftContainer = document.querySelector('.draft-players-container');
    if (!draftContainer) {
        // Fallback to fixed position if container not found
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            background: var(--semi-frost-bg-2);
            color: var(--heading-color);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 10px;
            z-index: 99999;
        `;
        
        const indicator = document.createElement('div');
        indicator.id = 'activity';
        indicator.style.cssText = `display: block; width: 56px; height: 56px;`;
        
        const loader = document.createElement('div');
        loader.className = 'loader';
        loader.style.cssText = `border: 6px solid var(--heading-color); border-bottom-color: var(--secondary-link-color);`;
        
        const messageElement = document.createElement('div');
        messageElement.textContent = message;
        messageElement.style.fontSize = '0.9rem';
        
        indicator.appendChild(loader);
        overlay.appendChild(indicator);
        overlay.appendChild(messageElement);
        document.body.appendChild(overlay);
        
        return overlay;
    }
    
    // Create loading overlay that fits inside the draft-players-container
    const overlay = document.createElement('div');
    overlay.className = 'draft-loading-overlay';
    
    // Make sure the container has relative positioning for the absolute overlay
    const currentPosition = window.getComputedStyle(draftContainer).position;
    if (currentPosition === 'static') {
        draftContainer.style.position = 'relative';
    }
    
    // Create #activity element
    const indicator = document.createElement('div');
    indicator.id = 'activity';
    
    // Create loader div
    const loader = document.createElement('div');
    loader.className = 'loader';
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.textContent = message;
    
    indicator.appendChild(loader);
    overlay.appendChild(indicator);
    overlay.appendChild(messageElement);
    draftContainer.appendChild(overlay);
    
    return overlay;
}

function hideLoadingIndicator(indicator) {
    // Use team builder's hide function if available
    if (window.hideLoadingIndicator) {
        return window.hideLoadingIndicator(indicator);
    }
    
    // Remove the indicator element
    if (indicator && indicator.parentNode) {
        indicator.parentNode.removeChild(indicator);
    }
}

export { DraftMode };
