import { eventManager, debounce } from './utils.js';

// Cache DOM elements and performance-critical selectors
const DOM = {
    dropArea: null,
    playerPools: null,
    teamSelect: null,
    selectPlayersBtn: null,
    teamDropdownLabel: null,
    clearBtn: null,
    dropdownCheckbox: null,
    playerPoolPopover: null,
    // Cache frequently accessed elements
    allSlots: null,
    poolPlayers: null,
    activePoolButtons: null
};

// Shopify Draggable instances
let draggableInstances = {
    poolToDrop: null,
    slotToSlot: null
};

// Performance caches
const positionCache = new WeakMap();
const positionSlotCache = new Map();
const poolPlayerCache = new WeakMap();
const slotStateCache = new Map();

// Performance optimizations
const BATCH_SIZE = 50;
const DEBOUNCE_DELAY = 16; // ~60fps

function getPositionSlots(position) {
    if (positionSlotCache.has(position)) {
        return positionSlotCache.get(position);
    }
    const slots = DOM.allSlots ? 
        Array.from(DOM.allSlots).filter(slot => slot.dataset.position === position) :
        document.querySelectorAll(`.player-slot[data-position="${position}"]`);
    positionSlotCache.set(position, slots);
    return slots;
}

// Optimized cleanup with better memory management
function cleanupTeamBuilder() {
    // Clear all caches
    positionCache.clear();
    positionSlotCache.clear();
    poolPlayerCache.clear();
    slotStateCache.clear();
    
    // Destroy Draggable instances
    if (draggableInstances.poolToDrop) {
        draggableInstances.poolToDrop.destroy();
        draggableInstances.poolToDrop = null;
    }
    if (draggableInstances.slotToSlot) {
        draggableInstances.slotToSlot.destroy();
        draggableInstances.slotToSlot = null;
    }
    
    // Destroy swipers efficiently
    if (window.teamBuilderSwipers) {
        Object.values(window.teamBuilderSwipers).forEach(swiper => {
            if (swiper && swiper.destroy) {
                swiper.destroy(true, true);
            }
        });
        window.teamBuilderSwipers = {};
    }
    
    // Clear DOM cache
    Object.keys(DOM).forEach(key => {
        DOM[key] = null;
    });
}

// Improved state management
const TeamBuilder = {
    state: {
        activeTeam: null,
        draggedItem: null,
        lastSavedState: null
    },
    
    setState(newState) {
        Object.assign(this.state, newState);
        this.notifyStateChange();
    },
    
    notifyStateChange() {
        const currentState = this.getSerializableState();
        if (JSON.stringify(this.state.lastSavedState) !== JSON.stringify(currentState)) {
            this.saveState();
            this.state.lastSavedState = currentState;
        }
    },
    
    getSerializableState() {
        return {
            activeTeam: this.state.activeTeam,
            forward: this.getPositionState('forward'),
            defenseman: this.getPositionState('defenseman'),
            goalie: this.getPositionState('goalie')
        };
    },

    getPositionState(position) {
        const state = [];
        getPositionSlots(position).forEach((slot, index) => {
            const player = slot.querySelector('.player');
            if (player) {
                state[index] = {
                    name: player.querySelector('.name').textContent,
                    playerId: player.dataset.playerId,
                    teamId: player.dataset.teamId,
                    slotIndex: index
                };
            }
        });
        return state.filter(Boolean);
    },

    saveState() {
        localStorage.setItem('teamBuilderState', JSON.stringify(this.getSerializableState()));
    }
};

// Optimized pool player states with batch DOM updates and caching
const updatePoolPlayerStates = debounce(() => {
    if (!DOM.dropArea || !DOM.poolPlayers) return;

    // Use cached slotted players map
    const slottedPlayers = new Map();
    const slottedPlayerElements = DOM.dropArea.querySelectorAll('.player-slot .player .name');
    
    // Batch collect slotted players
    for (let i = 0; i < slottedPlayerElements.length; i++) {
        const nameEl = slottedPlayerElements[i];
        slottedPlayers.set(nameEl.textContent, true);
    }
    
    // Batch update pool player states using DocumentFragment for efficiency
    const updatesToMake = [];
    
    DOM.poolPlayers.forEach(poolPlayer => {
        const nameEl = poolPlayer.querySelector('.name');
        if (!nameEl) return;
        
        const name = nameEl.textContent;
        const shouldBeInSlot = slottedPlayers.has(name);
        const isCurrentlyInSlot = poolPlayer.classList.contains('in-slot');
        
        if (shouldBeInSlot !== isCurrentlyInSlot) {
            updatesToMake.push({ player: poolPlayer, inSlot: shouldBeInSlot });
        }
    });
    
    // Apply all updates in one batch to minimize reflows
    updatesToMake.forEach(({ player, inSlot }) => {
        player.classList.toggle('in-slot', inSlot);
    });
}, DEBOUNCE_DELAY);

// Event delegation optimization with passive listeners where appropriate
function initializeEventDelegation() {
    if (!DOM.dropArea) return;
    
    // Use passive listeners for better scroll performance
    DOM.dropArea.addEventListener('click', (e) => {
        const player = e.target.closest('.player');
        const slot = e.target.closest('.player-slot');
        
        if (player) {
            handlePlayerClick(player, e);
        } else if (slot && !slot.querySelector('.player')) {
            handleEmptySlotClick(slot);
        }
    }, { passive: false }); // Need false for preventDefault in some cases
}

function handlePlayerClick(player, e) {
    e.stopPropagation();
    const overlay = initializePlayerOverlay();
    overlay.style.display = 'block';
    player.appendChild(overlay);
    overlay.targetPlayer = player;
}

function handleEmptySlotClick(slot) {
    if (DOM.playerPoolPopover) {
        DOM.playerPoolPopover.showPopover();
        const targetTab = getPoolTarget(slot.dataset.position);
        document.querySelector(`.tb-selection-header .btn[data-target="${targetTab}"]`)?.click();
    }
}

// Memoized position checkers
function getPlayerPosition(player) {
    if (positionCache.has(player)) {
        return positionCache.get(player);
    }
    const position = player.classList.contains('forward') ? 'forward' : 
                     player.classList.contains('defenseman') ? 'defenseman' : 'goalie';
    positionCache.set(player, position);
    return position;
}

const poolTargets = {
    forward: 'forwards',
    defenseman: 'defensemen',
    goalie: 'goalies'
};
function getPoolTarget(position) {
    return poolTargets[position] || position;
}

// Optimized swiper updates with better throttling
const debouncedSwiperUpdate = debounce((swiper) => {
    if (!swiper || !swiper.update) return;
    
    requestAnimationFrame(() => {
        // Batch all swiper updates together
        try {
            swiper.update();
            swiper.updateSize();
            swiper.updateSlides();
            swiper.updateProgress();
            swiper.updateSlidesClasses();
        } catch (error) {
            console.warn('Swiper update error:', error);
        }
    });
}, DEBOUNCE_DELAY);

// More efficient swiper update with error handling
function updateSwiper(swiper) {
    if (!swiper) return;
    debouncedSwiperUpdate(swiper);
}

// Optimized slot state management with caching
function disableSlots(disabled = true) {
    if (!DOM.allSlots) return;
    
    const cacheKey = disabled ? 'disabled' : 'enabled';
    if (slotStateCache.get('currentState') === cacheKey) return;
    
    const style = disabled ? 
        { pointerEvents: 'none', opacity: '0.5' } : 
        { pointerEvents: '', opacity: '' };
    
    // Batch style updates
    DOM.allSlots.forEach(slot => {
        Object.assign(slot.style, style);
    });
    
    slotStateCache.set('currentState', cacheKey);
}

function updateSlotsState() {
    disableSlots(!TeamBuilder.state.activeTeam);
}

// Function to dynamically load Shopify Draggable library
function loadShopifyDraggable() {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (window.Draggable && window.Draggable.Droppable) {
            resolve();
            return;
        }

        // Check if script is already being loaded
        if (document.querySelector('script[src*="shopify/draggable"]')) {
            // Wait for it to load
            const checkInterval = setInterval(() => {
                if (window.Draggable && window.Draggable.Droppable) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
            
            // Timeout after 10 seconds
            setTimeout(() => {
                clearInterval(checkInterval);
                reject(new Error('Shopify Draggable failed to load after timeout'));
            }, 10000);
            return;
        }

        // Load the script dynamically
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/@shopify/draggable@1.0.0-beta.11/lib/draggable.bundle.js';
        script.onload = () => {
            // Wait a bit for the library to initialize
            setTimeout(() => {
                if (window.Draggable && window.Draggable.Droppable) {
                    resolve();
                } else {
                    reject(new Error('Shopify Draggable library did not initialize properly'));
                }
            }, 100);
        };
        script.onerror = () => {
            reject(new Error('Failed to load Shopify Draggable script'));
        };
        
        document.head.appendChild(script);
    });
}
// Initialize Shopify Draggable for player drag and drop
function initializeDragAndDrop() {
    if (!DOM.dropArea || !DOM.playerPools) return;
    
    // Check if Shopify Draggable is available
    if (!window.Draggable || !window.Draggable.Droppable) {
        console.warn('Shopify Draggable library not found. Please ensure the script is loaded.');
        return;
    }

    // Destroy existing instances
    if (draggableInstances.poolToDrop) {
        draggableInstances.poolToDrop.destroy();
    }
    if (draggableInstances.slotToSlot) {
        draggableInstances.slotToSlot.destroy();
    }

    // Get all containers that can have draggable items
    const containers = [DOM.playerPools, DOM.dropArea];

    try {
        // Use Draggable with cursor offset to center the mirror
        draggableInstances.poolToDrop = new window.Draggable.Draggable(containers, {
            draggable: '.player',
            mirror: {
                appendTo: 'body',
                constrainDimensions: false,
                cursorOffsetX: 130,
                cursorOffsetY: 53
            },
            delay: 100,
            distance: 0,
            classes: {
                'source:dragging': 'draggable-source--is-dragging',
                'body:dragging': 'draggable--is-dragging',
                'container:dragging': 'draggable-container--is-dragging',
                'mirror': 'draggable-mirror'
            }
        });

        // Handle mirror creation with minimal styling
        draggableInstances.poolToDrop.on('mirror:create', (e) => {
            if (e.mirror) {
                e.mirror.style.zIndex = '999999';
                e.mirror.style.pointerEvents = 'none';
                // Remove any conflicting transforms
                e.mirror.style.transformOrigin = '';
            }
        });

        // Clean up mirror reference
        draggableInstances.poolToDrop.on('mirror:destroy', (e) => {
            // No cleanup needed for simple approach
        });

        // Handle drag start
        draggableInstances.poolToDrop.on('drag:start', (e) => {
            if (!TeamBuilder.state.activeTeam) {
                e.cancel();
                return;
            }
            
            TeamBuilder.setState({ draggedItem: e.source });
            e.source.classList.add('dragging');
            document.body.style.cursor = 'grabbing';
            
            // Hide popover during drag to avoid z-index issues
            const popover = document.querySelector('#team-builder-player-pool');
            if (popover && popover.matches(':popover-open')) {
                popover.style.display = 'none';
                window._popoverHiddenForDrag = true;
            }
            
            // Highlight compatible slots
            const draggedPosition = getPlayerPosition(e.source);
            document.querySelectorAll('.team-builder .player-slot').forEach(slot => {
                if (slot.dataset.position === draggedPosition) {
                    slot.style.border = '2px dashed var(--main-link-color)';
                    slot.classList.add('compatible-slot');
                }
            });
        });

        // Handle successful drop
        draggableInstances.poolToDrop.on('drag:stop', (e) => {
            e.source.classList.remove('dragging');
            document.body.style.cursor = '';
            
            // Restore popover visibility if it was hidden during drag
            if (window._popoverHiddenForDrag) {
                const popover = document.querySelector('#team-builder-player-pool');
                if (popover) {
                    popover.style.display = '';
                    window._popoverHiddenForDrag = false;
                }
            }
            
            // Remove all visual feedback including compatible slot highlighting
            document.querySelectorAll('.player-slot').forEach(slot => {
                slot.style.backgroundColor = '';
                slot.style.border = '';
                slot.classList.remove('compatible-slot');
            });
            
            // Check if we're over a valid drop zone
            let target = null;
            
            // Try to get coordinates from sensor event
            if (e.sensorEvent && e.sensorEvent.clientX !== undefined && e.sensorEvent.clientY !== undefined) {
                target = document.elementFromPoint(e.sensorEvent.clientX, e.sensorEvent.clientY);
            } else if (e.sensorEvent && e.sensorEvent.originalEvent) {
                // Try to get from original event
                const originalEvent = e.sensorEvent.originalEvent;
                const clientX = originalEvent.clientX || originalEvent.touches?.[0]?.clientX;
                const clientY = originalEvent.clientY || originalEvent.touches?.[0]?.clientY;
                if (clientX !== undefined && clientY !== undefined) {
                    target = document.elementFromPoint(clientX, clientY);
                }
            }
            
            // If we still don't have target, check if mouse is over any slot
            if (!target) {
                const mouseOverElements = document.querySelectorAll(':hover');
                target = Array.from(mouseOverElements).find(el => el.classList.contains('player-slot'));
            }
            
            const dropSlot = target?.closest('.player-slot');
            
            if (dropSlot) {
                const draggedPosition = getPlayerPosition(e.source);
                const dropZonePosition = dropSlot.dataset.position;
                
                if (dropZonePosition === draggedPosition) {
                    handlePlayerDrop(e.source, dropSlot);
                }
            }
            
            TeamBuilder.setState({ draggedItem: null });
        });
        
    } catch (error) {
        console.error('Error initializing Droppable:', error);
    }
}

// Handle player drop logic
function handlePlayerDrop(draggedPlayer, targetSlot) {
    const existingPlayer = targetSlot.querySelector('.player');
    const isFromSlot = draggedPlayer.parentElement.classList.contains('player-slot');
    const draggedPlayerName = draggedPlayer.querySelector('.name').textContent;

    // Check for duplicates when dragging from pool
    if (!isFromSlot) {
        const isAlreadyInSlot = Array.from(DOM.allSlots).some(slot => {
            const player = slot.querySelector('.player');
            return player && player.querySelector('.name').textContent === draggedPlayerName;
        });
        if (isAlreadyInSlot) return;
    }

    if (existingPlayer && isFromSlot) {
        // Swap players between slots
        draggedPlayer.parentElement.appendChild(existingPlayer);
        targetSlot.appendChild(draggedPlayer);
    } else if (!isFromSlot) {
        // Clone from pool to slot
        const clone = draggedPlayer.cloneNode(true);
        clone.style.opacity = '1';
        clone.style.cursor = 'grab';
        clone.style.transform = ''; // Remove any transforms
        clone.dataset.teamId = draggedPlayer.dataset.teamId;
        clone.dataset.playerId = draggedPlayer.dataset.playerId;
        
        // Remove any draggable attributes, links, and dragging classes from clone
        clone.querySelectorAll('*').forEach(el => {
            el.removeAttribute('draggable');
            el.removeAttribute('href');
        });
        
        // Remove all dragging-related classes
        clone.classList.remove('dragging', 'draggable-source--is-dragging', 'draggable--over');
        clone.querySelectorAll('*').forEach(el => {
            el.classList.remove('dragging', 'draggable-source--is-dragging', 'draggable--over');
        });
        
        if (existingPlayer) existingPlayer.remove();
        targetSlot.appendChild(clone);
    } else {
        // Move between slots
        if (existingPlayer) existingPlayer.remove();
        targetSlot.appendChild(draggedPlayer);
    }

    // Update states
    requestAnimationFrame(() => {
        updatePoolPlayerStates();
        TeamBuilder.notifyStateChange();
        
        // Reinitialize draggable to include new cloned players
        loadShopifyDraggable()
            .then(() => {
                initializeDragAndDrop();
            })
            .catch(error => {
                console.warn('Could not reinitialize drag and drop:', error);
            });
    });
}

// Player action overlay functionality
function initializePlayerOverlay() {
    // Create overlay element if it doesn't exist
    let overlay = document.getElementById('player-action-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'player-action-overlay';
        overlay.className = 'player-action-overlay';
        overlay.innerHTML = `
            <div class="overlay-actions">
                <button class="btn sm remove-player"><i class="bi bi-trash"></i> Remove</button>
                <button class="btn sm view-stats"><i class="bi bi-bar-chart"></i> Stats</button>
            </div>
        `;
        document.body.appendChild(overlay);

        // Close overlay when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#player-action-overlay') && 
                !e.target.closest('#team-builder-drop-area .player-slot .player')) {
                overlay.style.display = 'none';
            }
        });

        // Handle overlay actions
        overlay.querySelector('.remove-player').addEventListener('click', (e) => {
            const player = overlay.targetPlayer;
            if (player) {
                player.remove();
                overlay.style.display = 'none';
                updatePoolPlayerStates();
                TeamBuilder.notifyStateChange();
            }
        });

        overlay.querySelector('.view-stats').addEventListener('click', (e) => {
            const player = overlay.targetPlayer;
            if (player) {
                const playerId = player.dataset.playerId;
                const playerLink = document.createElement('a');
                playerLink.id = 'player-link';
                playerLink.setAttribute('data-link', playerId);
                playerLink.style.display = 'none';
                document.body.appendChild(playerLink);
                playerLink.click();
                playerLink.remove();
                overlay.style.display = 'none';
            }
        });
    }
    return overlay;
}

// Add click handler for dropped players
function initializeDroppedPlayerActions() {
    const overlay = initializePlayerOverlay();
    
    eventManager.addDelegatedEventListener(DOM.dropArea, '.player-slot .player', 'click', function(e) {
        e.stopPropagation();
        overlay.style.display = 'block';
        // Insert overlay directly into the clicked player element
        this.appendChild(overlay);
        overlay.targetPlayer = this;
    });
}

// Cache for loaded team rosters to avoid repeated requests
const teamRosterCache = new Map();

async function loadTeamState() {
    const savedState = localStorage.getItem('teamBuilderState');
    if (!savedState) return;

    try {
        const teamState = JSON.parse(savedState);
        
        // Collect all unique team IDs from saved state
        const teamIds = new Set();
        ['forward', 'defenseman', 'goalie'].forEach(position => {
            const players = teamState[position] || [];
            players.forEach(player => {
                if (player && player.teamId) teamIds.add(player.teamId);
            });
        });

        if (teamIds.size === 0) return;



        // Load all team rosters in a single bulk request
        const teamRosters = await loadTeamRostersBulk(Array.from(teamIds));
        if (!teamRosters) return;

        // Create a map of all players for quick lookup
        const allPlayersMap = new Map();
        
        Object.values(teamRosters).forEach(teamData => {
            if (!teamData.html) return;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = teamData.html;
            
            // Index all players by their ID for fast lookup
            const players = tempDiv.querySelectorAll('.player[data-player-id]');
            players.forEach(player => {
                const playerId = player.dataset.playerId;
                if (playerId) {
                    allPlayersMap.set(playerId, player.cloneNode(true));
                }
            });
        });

        // Restore players to their exact slots using the indexed map
        ['forward', 'defenseman', 'goalie'].forEach(position => {
            const players = teamState[position] || [];
            const slots = document.querySelectorAll(`.player-slot[data-position="${position}"]`);
            
            players.forEach(savedPlayer => {
                if (!savedPlayer || savedPlayer.slotIndex >= slots.length) return;
                
                const slot = slots[savedPlayer.slotIndex];
                if (!slot || slot.querySelector('.player')) return;

                // Get player from the indexed map
                const poolPlayer = allPlayersMap.get(savedPlayer.playerId);
                if (!poolPlayer) return;

                // Create and place the player in the exact slot
                const clone = poolPlayer.cloneNode(true);
                clone.style.cursor = 'grab';
                clone.style.opacity = '1';
                clone.querySelectorAll('*').forEach(el => {
                    el.removeAttribute('draggable');
                    el.removeAttribute('href');
                });
                slot.appendChild(clone);
            });
        });

        // After restoring all players, update pool player states
        updatePoolPlayerStates();
        
        // Reinitialize drag and drop to include restored players
        loadShopifyDraggable()
            .then(() => {
                initializeDragAndDrop();
            })
            .catch(error => {
                console.warn('Could not reinitialize drag and drop:', error);
            });
    } catch (error) {
        console.error('Error loading team state:', error);
    }
}

// Optimized bulk team roster loading
async function loadTeamRostersBulk(teamIds) {
    if (!teamIds || teamIds.length === 0) return null;
    
    // Check cache first
    const cacheKey = teamIds.sort().join(',');
    if (teamRosterCache.has(cacheKey)) {
        return teamRosterCache.get(cacheKey);
    }
    
    // Show loading indicator for better UX
    const loadingIndicator = showLoadingIndicator('Loading team rosters...');
    
    try {
        const formData = new FormData();
        teamIds.forEach(teamId => {
            formData.append('team_ids[]', teamId);
        });
        
        const response = await fetch('ajax/team-builder-bulk.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const resp = await response.json();
        // Normalize possible envelope { success: true, teams: {...} }
        let teamRosters = resp;
        if (resp && resp.success && resp.teams) teamRosters = resp.teams;

        if (teamRosters.error) {
            throw new Error(teamRosters.error);
        }

        // Cache the result
        teamRosterCache.set(cacheKey, teamRosters);

        return teamRosters;
    } catch (error) {
        console.error('Error loading team rosters in bulk:', error);
        return null;
    } finally {
        // Hide loading indicator
        hideLoadingIndicator(loadingIndicator);
    }
}

// Simple loading indicator functions
function showLoadingIndicator(message = 'Loading...') {
    const indicator = document.createElement('div');
    indicator.className = 'loading-indicator';
    indicator.innerHTML = `
        <div class="loading-spinner"></div>
        <div class="loading-message">${message}</div>
    `;
    document.body.appendChild(indicator);
    return indicator;
}

function hideLoadingIndicator(indicator) {
    if (indicator && indicator.parentNode) {
        indicator.parentNode.removeChild(indicator);
    }
}

// Optimized team selection with better batch processing
async function handleTeamSelection(teamElement, skipStateRestore = false) {
    const teamId = teamElement.dataset.value;
    const teamName = teamElement.textContent.trim();
    
    // Batch DOM updates
    if (DOM.dropdownCheckbox) DOM.dropdownCheckbox.checked = false;
    if (DOM.teamDropdownLabel) {
        DOM.teamDropdownLabel.innerHTML = `${teamName} <i class="bi bi-arrow-down-short"></i>`;
    }
    
    try {
        const previousTeamId = TeamBuilder.state.activeTeam;
        TeamBuilder.setState({ activeTeam: teamId });
        window.activeTeam = teamId;

        // Batch UI updates
        if (DOM.selectPlayersBtn) DOM.selectPlayersBtn.classList.remove('disabled');
        updateSlotsState();

        const formData = new FormData();
        formData.append('active_team', teamId);
        
        // Use fetch with better error handling and timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout
        
        const response = await fetch('ajax/team-builder.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const html = await response.text();
        
        // Use DocumentFragment for better performance
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Batch update player pools with error handling
        const poolUpdates = ['forwards', 'defensemen', 'goalies'].map((type, index) => {
            const poolNumber = index + 1;
            const newPool = tempDiv.querySelector(`#tb-pool-${poolNumber}`);
            const currentPool = document.querySelector(`#tb-pool-${poolNumber}`);
            
            return { newPool, currentPool, poolNumber };
        });
        
        // Apply all pool updates in batch
        poolUpdates.forEach(({ newPool, currentPool, poolNumber }) => {
            if (newPool && currentPool) {
                // Clear existing content efficiently
                currentPool.replaceChildren();
                
                // Use DocumentFragment for batch DOM operations
                const fragment = document.createDocumentFragment();
                Array.from(newPool.children).forEach(child => {
                    const clonedChild = child.cloneNode(true);
                    fragment.appendChild(clonedChild);
                });
                
                currentPool.appendChild(fragment);
            }
        });
        
        // Cache pool players for better performance
        DOM.poolPlayers = document.querySelectorAll('.tb-pool .player');
        
        // Reinitialize drag and drop with new players
        loadShopifyDraggable()
            .then(() => {
                initializeDragAndDrop();
            })
            .catch(error => {
                console.warn('Could not reinitialize drag and drop:', error);
            });
        
        // Batch update Swipers
        requestAnimationFrame(() => {
            Object.values(window.teamBuilderSwipers || {}).forEach(swiper => {
                if (swiper && swiper.update) {
                    updateSwiper(swiper);
                }
            });
        });

        // Restore state only if requested or if saved state's activeTeam matches this team
        if (!skipStateRestore) {
            try {
                const savedStateRaw = localStorage.getItem('teamBuilderState');
                if (savedStateRaw) {
                    const savedState = JSON.parse(savedStateRaw);
                    if (savedState && savedState.activeTeam && savedState.activeTeam === teamId) {
                        await loadTeamState();
                    }
                }
            } catch (err) {
                console.warn('Error checking saved team state before restore:', err);
            }
        }

        // Final state update
        updatePoolPlayerStates();
        
    } catch (error) {
        console.error('Error loading team roster:', error);
        
        // Rollback state on error
        TeamBuilder.setState({ activeTeam: previousTeamId });
        window.activeTeam = previousTeamId;
        if (DOM.selectPlayersBtn) DOM.selectPlayersBtn.classList.add('disabled');
        updateSlotsState();
    }
}

export function initTeamBuilder() {
    // Wait for DOM to be fully ready, especially for direct page loads
    const initWhenReady = () => {
        // Cache all DOM elements upfront for better performance
        Object.assign(DOM, {
            dropArea: document.querySelector('#team-builder-drop-area'),
            playerPools: document.querySelector('.tb-selection-players'),
            teamSelect: document.querySelector('#team-selection-custom'),
            selectPlayersBtn: document.querySelector('[popovertarget="team-builder-player-pool"]'),
            teamDropdownLabel: document.querySelector('.custom-select .for-dropdown'),
            clearBtn: document.getElementById('btn-clear-tb'),
            dropdownCheckbox: document.getElementById('dropdownBuilder'),
            playerPoolPopover: document.getElementById('team-builder-player-pool'),
            // Cache frequently accessed elements for better performance
            allSlots: document.querySelectorAll('.player-slot'),
            poolPlayers: document.querySelectorAll('.tb-pool .player'),
            activePoolButtons: document.querySelectorAll('.tb-selection-header .btn:not([popovertarget])')
        });

        // Early return if essential elements are missing (content may not be loaded yet)
        if (!DOM.dropArea || !DOM.playerPools || !DOM.teamSelect || 
            !DOM.selectPlayersBtn || !DOM.teamDropdownLabel) {
            // Only retry if we're actually on a team builder page
            const isTeamBuilderPage = window.location.pathname.includes('team-builder') || 
                                      window.location.search.includes('team-builder') ||
                                      document.querySelector('#team-builder-drop-area');
            
            if (isTeamBuilderPage) {
                // Retry after a short delay for direct page loads
                setTimeout(initWhenReady, 100);
            }
            return;
        }

        // Initialize the team builder
        initializeTeamBuilder();
    };

    // For direct page loads, add a small delay to ensure DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenReady);
    } else {
        // DOM is already ready, but add a small timeout for direct page loads
        // where content might still be loading
        setTimeout(initWhenReady, 10);
    }
}

function initializeTeamBuilder() {
    // Initialize event delegation with better performance
    initializeEventDelegation();

    // Initialize state management
    window.activeTeam = TeamBuilder.state.activeTeam;
    window.draggedItem = TeamBuilder.state.draggedItem;

    // Add clear button functionality with confirmation
    if (DOM.clearBtn) {
        eventManager.addEventListener(DOM.clearBtn, 'click', function() {
            if (confirm('Are you sure you want to clear all players?')) {
                // Use more efficient clearing method
                const slotsWithPlayers = Array.from(DOM.allSlots).filter(slot => 
                    slot.querySelector('.player')
                );
                
                // Batch remove all players
                slotsWithPlayers.forEach(slot => {
                    const player = slot.querySelector('.player');
                    if (player) player.remove();
                });
                
                // Clear localStorage and update states
                localStorage.removeItem('teamBuilderState');
                updatePoolPlayerStates();
            }
        });
    }

    // Initialize Swiper with performance optimizations
    const pools = ['forwards', 'defensemen', 'goalies'];
    window.teamBuilderSwipers = {};

    pools.forEach((poolType, index) => {
        const poolNumber = index + 1;
        const swiperContainer = document.getElementById(`swiper-pool-${poolNumber}`);
        const poolElement = document.getElementById(`tb-pool-${poolNumber}`);
        
        if (!swiperContainer || !poolElement) return;

        // Batch set initial visibility
        const isFirst = index === 0;
        swiperContainer.style.display = isFirst ? 'block' : 'none';
        poolElement.style.display = isFirst ? 'flex' : 'none';

        // Initialize Swiper with performance settings
        try {
            window.teamBuilderSwipers[poolNumber] = new Swiper(swiperContainer, {
                slidesPerView: 5,
                spaceBetween: 10,
                enabled: true,
                allowTouchMove: false,
                preventInteractionOnTransition: true,
                noSwiping: true,
                noSwipingClass: 'player',
                touchStartPreventDefault: false,
                simulateTouch: false,
                watchOverflow: true, // Better performance
                observer: true, // Watch for changes
                observeParents: true,
                navigation: {
                    nextEl: `#swiper-pool-${poolNumber} .swiper-button-next`,
                    prevEl: `#swiper-pool-${poolNumber} .swiper-button-prev`,
                    disabledClass: 'swiper-button-disabled',
                    hiddenClass: 'swiper-button-hidden',
                    lockClass: 'swiper-button-lock'
                },
                scrollbar: {
                    el: `#swiper-pool-${poolNumber} .swiper-scrollbar`,
                    draggable: true,
                    hide: false,
                    lockClass: 'swiper-scrollbar-lock'
                },
                breakpoints: {
                    320: { slidesPerView: 1 },
                    480: { slidesPerView: 2 },
                    768: { slidesPerView: 3 },
                    1024: { slidesPerView: 4 },
                    1280: { slidesPerView: 5 }
                },
                on: {
                    init: function() {
                        // Batch initialize drag for all players
                        const players = poolElement.querySelectorAll('.player');
                        // Players will be made draggable by initializeDragAndDrop
                    }
                }
            });
        } catch (error) {
            console.error(`Failed to initialize swiper for pool ${poolNumber}:`, error);
        }
    });

    // Initial slots state
    updateSlotsState();

    // Handle player pool filtering with better performance
    if (DOM.activePoolButtons) {
        eventManager.addDelegatedEventListener(
            document, 
            '.tb-selection-header .btn:not([popovertarget])', 
            'click', 
            function() {
                // Batch remove active class
                DOM.activePoolButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Efficient pool switching
                const target = this.dataset.target;
                const swiperContainers = document.querySelectorAll('.swiper');
                
                // Batch visibility updates
                const updates = [];
                swiperContainers.forEach(swiperContainer => {
                    const poolElement = swiperContainer.querySelector('.tb-pool');
                    if (!poolElement) return;
                    
                    const shouldShow = poolElement.classList.contains(target);
                    updates.push({
                        container: swiperContainer,
                        pool: poolElement,
                        show: shouldShow,
                        swiperKey: poolElement.id.replace('tb-pool-', '')
                    });
                });
                
                // Apply all updates at once
                updates.forEach(({ container, pool, show, swiperKey }) => {
                    container.style.display = show ? 'block' : 'none';
                    pool.style.display = show ? 'flex' : 'none';
                    
                    if (show && window.teamBuilderSwipers[swiperKey]) {
                        updateSwiper(window.teamBuilderSwipers[swiperKey]);
                    }
                });
            }
        );
    }

    // Initialize Shopify Draggable for drag and drop functionality
    // Load the library dynamically if not already available
    loadShopifyDraggable()
        .then(() => {
            initializeDragAndDrop();
        })
        .catch(error => {
            console.error('Failed to load Shopify Draggable:', error);
            console.warn('Drag and drop functionality will be disabled');
        });

    // Optimized popover event handling
    if (DOM.playerPoolPopover) {
        eventManager.addEventListener(DOM.playerPoolPopover, 'toggle', function(e) {
            if (e.newState === 'open') {
                // Only update visible swipers for better performance
                requestAnimationFrame(() => {
                    const visibleSwipers = document.querySelectorAll('.swiper[style*="display: block"], .swiper:not([style*="display: none"])');
                    visibleSwipers.forEach(swiperContainer => {
                        const poolElement = swiperContainer.querySelector('.tb-pool');
                        if (poolElement) {
                            const swiperKey = poolElement.id.replace('tb-pool-', '');
                            const swiper = window.teamBuilderSwipers[swiperKey];
                            if (swiper) updateSwiper(swiper);
                        }
                    });
                });
            }
        });
    }

    // Enhanced team state restoration and initialization
    async function initializeTeamBuilderState() {
        const savedState = localStorage.getItem('teamBuilderState');
        let initialTeamId = null;
        let hasExistingPlayers = false;

        if (savedState) {
            try {
                const teamState = JSON.parse(savedState);
                
                // Use the saved active team if available
                if (teamState.activeTeam) {
                    initialTeamId = teamState.activeTeam;
                }
                
                // Check if there are any existing players
                const positions = ['forward', 'defenseman', 'goalie'];
                for (const position of positions) {
                    const players = teamState[position] || [];
                    if (players.length > 0) {
                        hasExistingPlayers = true;
                        // If no saved active team, use the team from the last placed player
                        if (!initialTeamId) {
                            const lastPlayer = players[players.length - 1];
                            if (lastPlayer && lastPlayer.teamId) {
                                initialTeamId = lastPlayer.teamId;
                            }
                        }
                        break;
                    }
                }
                
                // If we have existing players, load their state first
                if (hasExistingPlayers) {
                    await loadTeamState();
                }
            } catch (error) {
                console.error('Error parsing saved state:', error);
            }
        }

        // Set the initial team selection and load roster
        const initialTeamLink = initialTeamId ? 
            DOM.teamSelect.querySelector(`a[data-value="${initialTeamId}"]`) : 
            DOM.teamSelect.querySelector('a');

        if (initialTeamLink) {
            const teamName = initialTeamLink.textContent.trim();
            
            // Update dropdown label to reflect the correct team
            if (DOM.teamDropdownLabel) {
                DOM.teamDropdownLabel.innerHTML = `${teamName} <i class="bi bi-arrow-down-short"></i>`;
            }
            
            // Set the active team state
            TeamBuilder.setState({ activeTeam: initialTeamLink.dataset.value });
            window.activeTeam = initialTeamLink.dataset.value;
            
            // Enable the Add Players button
            if (DOM.selectPlayersBtn) {
                DOM.selectPlayersBtn.classList.remove('disabled');
            }
            
            // Update slots state
            updateSlotsState();
            
            // Load the team roster but skip state restore since we already did it
            await handleTeamSelection(initialTeamLink, true);
        }

        return { initialTeamId, hasExistingPlayers };
    }

    // Optimized initialization sequence
    initializeTeamBuilderState().then(({ initialTeamId, hasExistingPlayers }) => {
        // Handle team selection clicks
        eventManager.addDelegatedEventListener(DOM.teamSelect, 'a', 'click', function(e) {
            e.preventDefault();
            handleTeamSelection(this);
        });
    }).catch(error => {
        console.error('Failed to initialize team builder:', error);
        
        // Fallback to basic initialization
        const fallbackTeamLink = DOM.teamSelect.querySelector('a');
        if (fallbackTeamLink) {
            handleTeamSelection(fallbackTeamLink);
        }
    });

    // Initialize dropped player actions
    initializeDroppedPlayerActions();

    // Initial update of pool player states
    updatePoolPlayerStates();

    // Enhanced cleanup when leaving page
    const cleanup = () => {
        cleanupTeamBuilder();
        eventManager.removeAllEventListeners();
    };
    
    window.addEventListener('beforeunload', cleanup);
    window.addEventListener('unload', cleanup);
}


