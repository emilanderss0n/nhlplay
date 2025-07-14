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
    
    // Remove scroll support if active
    removeScrollSupportDuringDrag();
    
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

// Optimized player drag initialization with better cleanup tracking
function initializePlayerDrag(player) {
    if (!player) return null;
    
    // Check if already initialized to avoid duplicate listeners
    if (poolPlayerCache.has(player)) {
        return poolPlayerCache.get(player);
    }
    
    const cleanup = [];
    
    player.setAttribute('draggable', 'true');
    player.style.cursor = 'grab';
    
    // Use passive listeners where possible for better performance
    cleanup.push(
        eventManager.addEventListener(player, 'dragstart', dragHandlers.start, { passive: false }),
        eventManager.addEventListener(player, 'dragend', dragHandlers.end, { passive: true })
    );
    
    const cleanupFunction = () => {
        cleanup.forEach(unsub => unsub && unsub());
        poolPlayerCache.delete(player);
    };
    
    poolPlayerCache.set(player, cleanupFunction);
    return cleanupFunction;
}

// Optimized drag handlers with better performance and scroll support
const dragHandlers = {
    start(e) {
        if (!TeamBuilder.state.activeTeam) {
            e.preventDefault();
            return false;
        }
        
        TeamBuilder.setState({ draggedItem: this });
        this.classList.add('dragging');
        document.body.style.cursor = 'grabbing';
        
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setDragImage(this, this.offsetWidth / 2, this.offsetHeight / 2);
        e.dataTransfer.setData('position', getPlayerPosition(this));
        
        // Pre-cache position for better performance during drag
        const position = getPlayerPosition(this);
        e.dataTransfer.setData('text/plain', position);
        
        // Add scroll event listeners during drag
        addScrollSupportDuringDrag();
    },

    end() {
        this.classList.remove('dragging');
        document.body.style.cursor = '';
        
        // Remove scroll event listeners
        removeScrollSupportDuringDrag();
        
        // Use requestAnimationFrame for better performance
        requestAnimationFrame(() => {
            if (DOM.allSlots) {
                DOM.allSlots.forEach(slot => slot.classList.remove('drag-over'));
            }
        });
    },

    enter(e) {
        e.preventDefault();
        if (TeamBuilder.state.draggedItem && 
            this.dataset.position === getPlayerPosition(TeamBuilder.state.draggedItem)) {
            this.classList.add('drag-over');
        }
    },

    leave(e) {
        // Use throttling to reduce excessive class removals
        if (!this.contains(e.relatedTarget)) {
            this.classList.remove('drag-over');
        }
    },

    over(e) {
        e.preventDefault();
        const draggedPosition = TeamBuilder.state.draggedItem ? 
            getPlayerPosition(TeamBuilder.state.draggedItem) : null;
        e.dataTransfer.dropEffect = this.dataset.position === draggedPosition ? 'move' : 'none';
    },

    drop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        if (!TeamBuilder.state.draggedItem || 
            this.dataset.position !== getPlayerPosition(TeamBuilder.state.draggedItem)) {
            return;
        }

        const existingPlayer = this.querySelector('.player');
        const isFromSlot = TeamBuilder.state.draggedItem.parentElement.classList.contains('player-slot');
        const draggedPlayerName = TeamBuilder.state.draggedItem.querySelector('.name').textContent;

        // Optimized duplicate check using cached DOM elements
        if (!isFromSlot) {
            const isAlreadyInSlot = Array.from(DOM.allSlots).some(slot => {
                const player = slot.querySelector('.player');
                return player && player.querySelector('.name').textContent === draggedPlayerName;
            });
            if (isAlreadyInSlot) return;
        }

        // Use DocumentFragment for better performance during DOM manipulation
        const fragment = document.createDocumentFragment();
        
        if (existingPlayer && isFromSlot) {
            // Swap players between slots
            TeamBuilder.state.draggedItem.parentElement.appendChild(existingPlayer);
            TeamBuilder.state.draggedItem.style.opacity = '1';
            this.appendChild(TeamBuilder.state.draggedItem);
        } else if (!isFromSlot) {
            // Clone from pool to slot with optimized copying
            const clone = TeamBuilder.state.draggedItem.cloneNode(true);
            Object.assign(clone.style, { opacity: '1', cursor: 'grab' });
            Object.assign(clone.dataset, {
                teamId: TeamBuilder.state.draggedItem.dataset.teamId,
                playerId: TeamBuilder.state.draggedItem.dataset.playerId
            });
            
            initializePlayerDrag(clone);
            clone.querySelectorAll('*').forEach(el => {
                el.removeAttribute('draggable');
                el.removeAttribute('href');
            });
            
            if (existingPlayer) existingPlayer.remove();
            this.appendChild(clone);
        } else {
            // Move between slots
            if (existingPlayer) existingPlayer.remove();
            TeamBuilder.state.draggedItem.style.opacity = '1';
            this.appendChild(TeamBuilder.state.draggedItem);
        }

        // Batch state updates
        document.body.style.cursor = '';
        if (TeamBuilder.state.draggedItem) {
            TeamBuilder.state.draggedItem.style.cursor = 'grab';
        }
        
        // Debounced updates for better performance
        requestAnimationFrame(() => {
            updatePoolPlayerStates();
            TeamBuilder.notifyStateChange();
        });
    }
};

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

        // Clear all slots first
        document.querySelectorAll('.player-slot').forEach(slot => {
            slot.innerHTML = '';
        });

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
                initializePlayerDrag(clone);
                clone.querySelectorAll('*').forEach(el => {
                    el.removeAttribute('draggable');
                    el.removeAttribute('href');
                });
                slot.appendChild(clone);
            });
        });

        // After restoring all players, update pool player states
        updatePoolPlayerStates();
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
        
        const teamRosters = await response.json();
        
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
                    initializePlayerDrag(clonedChild);
                    fragment.appendChild(clonedChild);
                });
                
                currentPool.appendChild(fragment);
            }
        });
        
        // Cache pool players for better performance
        DOM.poolPlayers = document.querySelectorAll('.tb-pool .player');
        
        // Batch update Swipers
        requestAnimationFrame(() => {
            Object.values(window.teamBuilderSwipers || {}).forEach(swiper => {
                if (swiper && swiper.update) {
                    updateSwiper(swiper);
                }
            });
        });

        // Restore state if needed
        if (!skipStateRestore) {
            await loadTeamState();
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
    // Cache all DOM elements upfront for better performance
    Object.assign(DOM, {
        dropArea: document.querySelector('#team-builder-drop-area'),
        playerPools: document.querySelectorAll('.tb-selection-players .tb-pool'),
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

    // Early return if essential elements are missing
    if (!DOM.dropArea || !DOM.playerPools || !DOM.teamSelect || 
        !DOM.selectPlayersBtn || !DOM.teamDropdownLabel) {
        console.warn('Team builder: Essential DOM elements not found');
        return;
    }

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
                        players.forEach(initializePlayerDrag);
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

    // Batch initialize all slots with drag handlers
    DOM.allSlots.forEach(slot => {
        eventManager.addEventListener(slot, 'dragenter', dragHandlers.enter, { passive: false });
        eventManager.addEventListener(slot, 'dragleave', dragHandlers.leave, { passive: true });
        eventManager.addEventListener(slot, 'dragover', dragHandlers.over, { passive: false });
        eventManager.addEventListener(slot, 'drop', dragHandlers.drop, { passive: false });
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

// Scroll support during drag operations
let scrollDuringDragHandler = null;
let autoScrollInterval = null;

function addScrollSupportDuringDrag() {
    // Enable mouse wheel scrolling during drag
    scrollDuringDragHandler = function(e) {
        // Don't prevent default - allow normal scrolling
        const scrollAmount = e.deltaY * 2; // Multiply for more responsive scrolling
        
        // Scroll the window directly
        window.scrollBy(0, scrollAmount);
    };
    
    // Add wheel event listener - use capture phase to catch before other handlers
    document.addEventListener('wheel', scrollDuringDragHandler, { 
        passive: false, 
        capture: true 
    });
    
    // Add keyboard scroll support
    const keyboardScrollHandler = function(e) {
        if (!TeamBuilder.state.draggedItem) return;
        
        const scrollAmount = 50;
        switch(e.key) {
            case 'ArrowUp':
                e.preventDefault();
                window.scrollBy(0, -scrollAmount);
                break;
            case 'ArrowDown':
                e.preventDefault();
                window.scrollBy(0, scrollAmount);
                break;
            case 'PageUp':
                e.preventDefault();
                window.scrollBy(0, -window.innerHeight * 0.8);
                break;
            case 'PageDown':
                e.preventDefault();
                window.scrollBy(0, window.innerHeight * 0.8);
                break;
        }
    };
    
    document.addEventListener('keydown', keyboardScrollHandler, { passive: false });
    
    // Store reference for cleanup
    scrollDuringDragHandler.keyboardHandler = keyboardScrollHandler;
    
    // Add auto-scroll near edges with improved detection
    autoScrollInterval = setInterval(() => {
        if (!TeamBuilder.state.draggedItem) return;
        
        const mouseY = window.lastMouseY || 0;
        const windowHeight = window.innerHeight;
        const scrollZone = 80; // pixels from edge to trigger auto-scroll
        const scrollSpeed = 8;
        
        if (mouseY < scrollZone && window.scrollY > 0) {
            // Scroll up
            window.scrollBy(0, -scrollSpeed);
        } else if (mouseY > windowHeight - scrollZone) {
            // Scroll down
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            if (window.scrollY < maxScroll) {
                window.scrollBy(0, scrollSpeed);
            }
        }
    }, 16); // ~60fps
    
    // Track mouse position for auto-scroll - use multiple events
    document.addEventListener('dragover', trackMousePosition, { passive: true });
    document.addEventListener('mousemove', trackMousePosition, { passive: true });
}

function removeScrollSupportDuringDrag() {
    if (scrollDuringDragHandler) {
        document.removeEventListener('wheel', scrollDuringDragHandler, { capture: true });
        
        // Remove keyboard handler if it exists
        if (scrollDuringDragHandler.keyboardHandler) {
            document.removeEventListener('keydown', scrollDuringDragHandler.keyboardHandler);
        }
        
        scrollDuringDragHandler = null;
    }
    
    if (autoScrollInterval) {
        clearInterval(autoScrollInterval);
        autoScrollInterval = null;
    }
    
    document.removeEventListener('dragover', trackMousePosition);
    document.removeEventListener('mousemove', trackMousePosition);
    
    // Clear stored mouse position
    delete window.lastMouseY;
}

function trackMousePosition(e) {
    window.lastMouseY = e.clientY;
}