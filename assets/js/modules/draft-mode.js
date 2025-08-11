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
        roundConfig: {
            forwards: 12,    // rounds 1-12
            defensemen: 6,   // rounds 13-18
            goalies: 2       // rounds 19-20
        }
    },
    
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
            positionInfo.textContent = `${position.charAt(0).toUpperCase() + position.slice(1)} - Pick ${positionRound}`;
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

export function initDraftMode() {
    // Wait for DOM to be ready
    const initWhenReady = () => {
        // Cache DOM elements
        updateDOMCache();
        
        // Only initialize if we have the essential elements
        if (!DOM.draftToggleBtn) {
            // Retry if we're on a team builder page
            const isTeamBuilderPage = document.querySelector('#team-builder-drop-area') ||
                                      window.location.pathname.includes('team-builder');
            
            if (isTeamBuilderPage) {
                setTimeout(initWhenReady, 100);
            }
            return;
        }
        
        // Initialize event listeners and interface
        initializeEventListeners();
        createDraftInterface();
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
    
    // Exit draft mode
    eventManager.addDelegatedEventListener(document, '.exit-draft-btn', 'click', exitDraftMode);
}

function createDraftInterface() {
    if (DOM.draftInterface) return; // Already exists
    
    const draftHTML = `
        <div id="draft-mode-interface" class="draft-mode-interface" style="display: none;">
            <div class="draft-header component-header">
                <h3 class="title">Draft Mode</h3>
                <button class="btn exit-draft-btn">Exit Draft Mode</button>
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
                        <span>Hide Team Info</span>
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
                </div>
                <button class="btn start-draft-btn">Start Draft</button>
            </div>
            
            <div class="draft-active" style="display: none;">
                <div class="draft-progress">
                    <div class="draft-round-info">Round 1 of 9</div>
                    <div class="draft-position-info">Forwards - Pick 1</div>
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
                        <div class="selected-players-list">
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
        DOM.draftToggleBtn.textContent = 'Draft Mode';
    }
    
    DraftMode.setState({ isActive: false });
    
    // Hide draft interface
    const draftActive = document.querySelector('.draft-active');
    const draftFilters = document.querySelector('.draft-filters');
    
    if (draftActive) draftActive.style.display = 'none';
    if (draftFilters) draftFilters.style.display = 'block';
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
}

function resetFilterCheckboxes() {
    // Uncheck all filter checkboxes when starting a new draft
    const filterCheckboxes = document.querySelectorAll('.draft-filter-toggle');
    filterCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
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
}

async function startDraft() {
    // Hide filters, show draft interface
    document.querySelector('.draft-filters').style.display = 'none';
    document.querySelector('.draft-active').style.display = 'block';
    
    DraftMode.setState({ currentRound: 1 });
    
    // Load first round players
    await loadRoundPlayers();
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
    const container = document.querySelector('.draft-players-grid');
    if (container) {
        // Clear container and add new content
        container.innerHTML = playersHtml.join('');
        
        // Get all the new player cards and initially hide them
        const playerCards = container.querySelectorAll('.draft-player');
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
        });
    }
    
    // Update progress bar with animation
    const progressPercent = (DraftMode.state.currentRound / DraftMode.state.totalRounds) * 100;
    const progressBar = document.querySelector('.progress-fill');
    if (progressBar) {
        progressBar.style.transition = 'width 0.5s ease-out';
        progressBar.style.width = progressPercent + '%';
    }
}

async function handlePlayerSelection(e) {
    const playerCard = e.target.closest('.draft-player');
    if (!playerCard) return;
    
    const playerData = JSON.parse(playerCard.dataset.playerData);
    
    // Check if player is already selected
    const alreadySelected = DraftMode.state.selectedPlayers.some(selected => 
        selected.player.id === playerData.id
    );
    
    if (alreadySelected) {
        alert('This player has already been selected!');
        return;
    }
    
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
        // Disable pointer events to prevent multiple clicks
        playerCard.style.pointerEvents = 'none';
        
        // Add selection animation - pulse and highlight effect
        playerCard.style.transition = 'all 0.3s ease-out';
        playerCard.style.transform = 'scale(1.05)';
        playerCard.style.boxShadow = '0 0 20px rgba(255, 255, 255, 0.5)';
        playerCard.style.zIndex = '10';
        
        // After a short delay, animate out
        setTimeout(() => {
            playerCard.style.transition = 'all 0.4s ease-in';
            playerCard.style.transform = 'scale(0.9)';
            playerCard.style.opacity = '0.3';
            
            // Complete the animation
            setTimeout(() => {
                resolve();
            }, 400);
        }, 300);
    });
}

function displaySelectedPlayer(player) {
    const container = document.querySelector('.selected-players-list');
    if (!container) return;
    
    const playerElement = document.createElement('div');
    playerElement.className = 'selected-player-summary';
    playerElement.innerHTML = `
        <div class="player-summary">
            <img src="${player.headshot}" alt="${player.firstName?.default} ${player.lastName?.default}" width="40" height="40">
            <span>${player.firstName?.default} ${player.lastName?.default}</span>
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
    // Hide draft interface
    document.querySelector('.draft-active').style.display = 'none';
    
    // Show completion message
    alert('Draft completed! Your team has been built.');
    
    // Transfer players to team builder
    await transferPlayersToTeamBuilder();
    
    // Exit draft mode
    exitDraftMode();
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

function showLoadingIndicator(message) {
    // Use team builder's loading indicator if available
    if (window.showLoadingIndicator) {
        return window.showLoadingIndicator(message);
    }
    
    // Create parent fixed div for overlay
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
    
    // Create #activity element with no custom styling
    const indicator = document.createElement('div');
    indicator.id = 'activity';
    indicator.style.cssText = `
        display: block;
        width: 56px;
        height: 56px;
    `;
    
    // Create loader div with base.css loader class only
    const loader = document.createElement('div');
    loader.className = 'loader';
    loader.style.cssText = `
        border: 6px solid var(--heading-color);
        border-bottom-color: var(--secondary-link-color);
    `;
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.textContent = message;
    messageElement.style.fontSize = '0.9rem';
    
    indicator.appendChild(loader);
    overlay.appendChild(indicator);
    overlay.appendChild(messageElement);
    document.body.appendChild(overlay);
    
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
