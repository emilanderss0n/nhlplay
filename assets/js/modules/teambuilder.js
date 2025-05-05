import { eventManager, debounce } from './utils.js';

// Cache DOM elements
const DOM = {
    dropArea: null,
    playerPools: null,
    teamSelect: null,
    selectPlayersBtn: null,
    teamDropdownLabel: null,
    clearBtn: null,
    dropdownCheckbox: null,
    playerPoolPopover: null
};

// Position and slot caching for better performance
const positionCache = new WeakMap();
const positionSlotCache = new Map();

function getPositionSlots(position) {
    if (positionSlotCache.has(position)) {
        return positionSlotCache.get(position);
    }
    const slots = document.querySelectorAll(`.player-slot[data-position="${position}"]`);
    positionSlotCache.set(position, slots);
    return slots;
}

function cleanupTeamBuilder() {
    positionCache.clear();
    positionSlotCache.clear();
    Object.values(window.teamBuilderSwipers).forEach(swiper => {
        if (swiper && swiper.destroy) {
            swiper.destroy(true, true);
        }
    });
    window.teamBuilderSwipers = {};
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

// Optimized pool player states with batch DOM updates
function updatePoolPlayerStates() {
    if (!DOM.dropArea || !DOM.playerPools) return;

    const slottedPlayers = new Map();
    
    // Collect slotted players
    DOM.dropArea.querySelectorAll('.player-slot .player .name').forEach(name => {
        slottedPlayers.set(name.textContent, true);
    });
    
    // Update all pool players' states
    DOM.playerPools.forEach(pool => {
        pool.querySelectorAll('.player').forEach(poolPlayer => {
            const name = poolPlayer.querySelector('.name').textContent;
            poolPlayer.classList.toggle('in-slot', slottedPlayers.has(name));
        });
    });
}

// Event delegation optimization
function initializeEventDelegation() {
    DOM.dropArea.addEventListener('click', (e) => {
        const player = e.target.closest('.player');
        const slot = e.target.closest('.player-slot');
        
        if (player) {
            handlePlayerClick(player, e);
        } else if (slot && !slot.querySelector('.player')) {
            handleEmptySlotClick(slot);
        }
    });
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

// Optimized swiper updates
const debouncedSwiperUpdate = debounce((swiper) => {
    requestAnimationFrame(() => {
        swiper.update();
        swiper.updateSize();
        swiper.updateSlides();
        swiper.updateProgress();
        swiper.updateSlidesClasses();
    });
}, 16);

function updateSwiper(swiper) {
    debouncedSwiperUpdate(swiper);
}

// Optimized slot state management
function disableSlots(disabled = true) {
    if (!DOM.dropArea) return;
    const style = disabled ? { pointerEvents: 'none', opacity: '0.5' } : { pointerEvents: '', opacity: '' };
    DOM.dropArea.querySelectorAll('.player-slot').forEach(slot => {
        Object.assign(slot.style, style);
    });
}

function updateSlotsState() {
    disableSlots(!TeamBuilder.state.activeTeam);
}

// Optimized player drag initialization with cleanup
function initializePlayerDrag(player) {
    const cleanup = [];
    
    player.setAttribute('draggable', 'true');
    player.style.cursor = 'grab';
    
    cleanup.push(
        eventManager.addEventListener(player, 'dragstart', dragHandlers.start),
        eventManager.addEventListener(player, 'dragend', dragHandlers.end)
    );
    
    return () => cleanup.forEach(unsub => unsub());
}

// Optimized drag handlers
const dragHandlers = {
    start(e) {
        if (!TeamBuilder.state.activeTeam) return;
        TeamBuilder.setState({ draggedItem: this });
        this.classList.add('dragging');
        document.body.style.cursor = 'grabbing';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setDragImage(this, this.offsetWidth / 2, this.offsetHeight / 2);
        e.dataTransfer.setData('position', getPlayerPosition(this));
    },

    end() {
        this.classList.remove('dragging');
        document.body.style.cursor = '';
        requestAnimationFrame(() => {
            DOM.dropArea?.querySelectorAll('.player-slot').forEach(slot => 
                slot.classList.remove('drag-over')
            );
        });
    },

    enter(e) {
        e.preventDefault();
        if (TeamBuilder.state.draggedItem && this.dataset.position === getPlayerPosition(TeamBuilder.state.draggedItem)) {
            this.classList.add('drag-over');
        }
    },

    leave(e) {
        this.classList.remove('drag-over');
    },

    over(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = this.dataset.position === getPlayerPosition(TeamBuilder.state.draggedItem) ? 'move' : 'none';
    },

    drop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        if (!TeamBuilder.state.draggedItem || this.dataset.position !== getPlayerPosition(TeamBuilder.state.draggedItem)) return;

        const existingPlayer = this.querySelector('.player');
        const isFromSlot = TeamBuilder.state.draggedItem.parentElement.classList.contains('player-slot');
        const draggedPlayerName = TeamBuilder.state.draggedItem.querySelector('.name').textContent;

        // Check if player is already in another slot
        if (!isFromSlot) {
            const isAlreadyInSlot = Array.from(DOM.dropArea.querySelectorAll('.player-slot .player')).some(
                player => player.querySelector('.name').textContent === draggedPlayerName
            );
            if (isAlreadyInSlot) return;
        }

        if (existingPlayer && isFromSlot) {
            // Swap players between slots
            TeamBuilder.state.draggedItem.parentElement.appendChild(existingPlayer);
            TeamBuilder.state.draggedItem.style.opacity = '1';
            this.appendChild(TeamBuilder.state.draggedItem);
        } else if (!isFromSlot) {
            // Clone from pool to slot
            const clone = TeamBuilder.state.draggedItem.cloneNode(true);
            Object.assign(clone.style, { opacity: '1', cursor: 'grab' });
            // Preserve team ID and player ID when cloning
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

        // Reset cursor and update states
        document.body.style.cursor = '';
        if (TeamBuilder.state.draggedItem) TeamBuilder.state.draggedItem.style.cursor = 'grab';
        updatePoolPlayerStates();
        TeamBuilder.notifyStateChange();
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

        // Clear all slots first
        document.querySelectorAll('.player-slot').forEach(slot => {
            slot.innerHTML = '';
        });

        // Load rosters for all teams that have saved players
        for (const teamId of teamIds) {
            const formData = new FormData();
            formData.append('active_team', teamId);
            
            const response = await fetch('ajax/team-builder.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) continue;
            
            const html = await response.text();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // For each position type, restore players to their exact slots
            ['forward', 'defenseman', 'goalie'].forEach(position => {
                const players = teamState[position] || [];
                for (const savedPlayer of players) {
                    if (!savedPlayer || savedPlayer.teamId !== teamId) continue;

                    // Use the saved slot index to find the exact slot
                    const slotIndex = savedPlayer.slotIndex;
                    const slots = document.querySelectorAll(`.player-slot[data-position="${position}"]`);
                    const slot = slots[slotIndex];
                    if (!slot || slot.querySelector('.player')) continue;

                    // Find player in the loaded roster
                    const poolPlayer = tempDiv.querySelector(`.tb-pool .player[data-player-id="${savedPlayer.playerId}"]`);
                    if (!poolPlayer) continue;

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
                }
            });
        }

        // After restoring all players, update pool player states
        updatePoolPlayerStates();
    } catch (error) {
        console.error('Error loading team state:', error);
    }
}

async function handleTeamSelection(teamElement, skipStateRestore = false) {
    const teamId = teamElement.dataset.value;
    const teamName = teamElement.textContent.trim();
    
    if (DOM.dropdownCheckbox) {
        DOM.dropdownCheckbox.checked = false;
    }
    
    if (DOM.teamDropdownLabel) {
        DOM.teamDropdownLabel.innerHTML = `${teamName} <i class="bi bi-arrow-down-short"></i>`;
    }
    
    try {
        const previousTeamId = TeamBuilder.state.activeTeam;
        TeamBuilder.setState({ activeTeam: teamId });
        window.activeTeam = teamId;

        if (DOM.selectPlayersBtn) {
            DOM.selectPlayersBtn.classList.remove('disabled');
        }
        
        updateSlotsState();

        const formData = new FormData();
        formData.append('active_team', teamId);
        
        // Fetch new roster before making any UI changes
        const response = await fetch('ajax/team-builder.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error('Network response was not ok');
        
        const html = await response.text();
        
        // Create a temporary container to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Update player pools without clearing slots
        ['forwards', 'defensemen', 'goalies'].forEach((type, index) => {
            const poolNumber = index + 1;
            const newPool = tempDiv.querySelector(`#tb-pool-${poolNumber}`);
            const currentPool = document.querySelector(`#tb-pool-${poolNumber}`);
            
            if (newPool && currentPool) {
                // Clear existing content first
                currentPool.innerHTML = '';
                // Add new content
                Array.from(newPool.children).forEach(child => {
                    currentPool.appendChild(child.cloneNode(true));
                });
                
                // Reinitialize drag for new players
                currentPool.querySelectorAll('.player').forEach(initializePlayerDrag);
            }
        });
        
        // Update Swipers and ensure they're properly initialized
        Object.values(window.teamBuilderSwipers).forEach(swiper => {
            if (swiper && swiper.update) {
                requestAnimationFrame(() => {
                    swiper.update();
                    swiper.updateSize();
                    swiper.updateSlides();
                });
            }
        });

        // Only restore state when explicitly requested (not during initial load)
        if (!skipStateRestore) {
            await loadTeamState();
        }

        // Update pool player states after everything is done
        updatePoolPlayerStates();
    } catch (error) {
        console.error('Error loading team roster:', error);
        TeamBuilder.setState({ activeTeam: previousTeamId });
        window.activeTeam = previousTeamId;
        if (DOM.selectPlayersBtn) {
            DOM.selectPlayersBtn.classList.add('disabled');
        }
        updateSlotsState();
    }
}

export function initTeamBuilder() {
    // Cache DOM elements
    Object.assign(DOM, {
        dropArea: document.querySelector('#team-builder-drop-area'),
        playerPools: document.querySelectorAll('.tb-selection-players .tb-pool'),
        teamSelect: document.querySelector('#team-selection-custom'),
        selectPlayersBtn: document.querySelector('[popovertarget="team-builder-player-pool"]'),
        teamDropdownLabel: document.querySelector('.custom-select .for-dropdown'),
        clearBtn: document.getElementById('btn-clear-tb'),
        dropdownCheckbox: document.getElementById('dropdownBuilder'),
        playerPoolPopover: document.getElementById('team-builder-player-pool')
    });

    if (!DOM.dropArea || !DOM.playerPools || !DOM.teamSelect || !DOM.selectPlayersBtn || !DOM.teamDropdownLabel) return;

    // Initialize event delegation
    initializeEventDelegation();

    // Initialize state management
    window.activeTeam = TeamBuilder.state.activeTeam;
    window.draggedItem = TeamBuilder.state.draggedItem;

    // Add clear button functionality
    if (DOM.clearBtn) {
        eventManager.addEventListener(DOM.clearBtn, 'click', function() {
            if (confirm('Are you sure you want to clear all players?')) {
                // Remove all players from slots
                DOM.dropArea.querySelectorAll('.player-slot .player').forEach(player => {
                    player.remove();
                });
                // Clear localStorage
                localStorage.removeItem('teamBuilderState');
                // Update pool player states to reflect cleared slots
                updatePoolPlayerStates();
            }
        });
    }

    // Initialize Swiper for each pool
    const pools = ['forwards', 'defensemen', 'goalies'];
    window.teamBuilderSwipers = {};

    // Initialize pools
    pools.forEach((poolType, index) => {
        const poolNumber = index + 1;
        const swiperContainer = document.getElementById(`swiper-pool-${poolNumber}`);
        const poolElement = document.getElementById(`tb-pool-${poolNumber}`);
        
        if (!swiperContainer || !poolElement) return;

        // Set initial visibility
        [swiperContainer.style.display, poolElement.style.display] = 
            index === 0 ? ['block', 'flex'] : ['none', 'none'];

        // Initialize Swiper
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
                    poolElement.querySelectorAll('.player').forEach(initializePlayerDrag);
                }
            }
        });
    });

    // Initial slots state
    updateSlotsState();

    // Handle player pool filtering using event delegation
    eventManager.addDelegatedEventListener(document, '.tb-selection-header .btn:not([popovertarget])', 'click', function() {
        // Remove active class from all buttons
        document.querySelectorAll('.tb-selection-header .btn').forEach(btn => {
            if (!btn.hasAttribute('popovertarget')) {
                btn.classList.remove('active');
            }
        });
        this.classList.add('active');
        
        // Switch pools
        const target = this.dataset.target;
        document.querySelectorAll('.swiper').forEach(swiperContainer => {
            const poolElement = swiperContainer.querySelector('.tb-pool');
            if (!poolElement) return;
            
            const show = poolElement.classList.contains(target);
            [swiperContainer.style.display, poolElement.style.display] = 
                show ? ['block', 'flex'] : ['none', 'none'];
            
            if (show) {
                const swiperKey = poolElement.id.replace('tb-pool-', '');
                const swiper = window.teamBuilderSwipers[swiperKey];
                if (swiper) updateSwiper(swiper);
            }
        });
    });

    // Initialize all slots
    DOM.dropArea.querySelectorAll('.player-slot').forEach(slot => {
        eventManager.addEventListener(slot, 'dragenter', dragHandlers.enter);
        eventManager.addEventListener(slot, 'dragleave', dragHandlers.leave);
        eventManager.addEventListener(slot, 'dragover', dragHandlers.over);
        eventManager.addEventListener(slot, 'drop', dragHandlers.drop);
    });

    // Add popover show event listener to update Swiper
    if (DOM.playerPoolPopover) {
        eventManager.addEventListener(DOM.playerPoolPopover, 'toggle', function(e) {
            if (e.newState === 'open') {
                document.querySelectorAll('.swiper').forEach(swiperContainer => {
                    if (swiperContainer.style.display !== 'none') {
                        const poolElement = swiperContainer.querySelector('.tb-pool');
                        if (poolElement) {
                            const swiperKey = poolElement.id.replace('tb-pool-', '');
                            const swiper = window.teamBuilderSwipers[swiperKey];
                            if (swiper) updateSwiper(swiper);
                        }
                    }
                });
            }
        });
    }

    // First load all saved players from all teams
    loadTeamState().then(() => {
        // After loading players, set up team selection
        // Get saved state to determine initial team
        const savedState = localStorage.getItem('teamBuilderState');
        let initialTeamId = null;
        
        if (savedState) {
            try {
                const teamState = JSON.parse(savedState);
                // Find the first player with a teamId
                for (const position of ['forward', 'defenseman', 'goalie']) {
                    const players = teamState[position] || [];
                    const firstPlayer = players.find(p => p.teamId);
                    if (firstPlayer) {
                        initialTeamId = firstPlayer.teamId;
                        break;
                    }
                }
            } catch (error) {
                console.error('Error parsing saved state:', error);
            }
        }

        // Select initial team based on saved state or first team
        const initialTeamLink = initialTeamId ? 
            DOM.teamSelect.querySelector(`a[data-value="${initialTeamId}"]`) : 
            DOM.teamSelect.querySelector('a');

        if (initialTeamLink) {
            // Set initial dropdown label
            DOM.teamDropdownLabel.innerHTML = `${initialTeamLink.textContent.trim()} <i class="bi bi-arrow-down-short"></i>`;
            // Initialize the pool with this team's roster
            handleTeamSelection(initialTeamLink);
        }

        // Handle team selection clicks
        eventManager.addDelegatedEventListener(DOM.teamSelect, 'a', 'click', function(e) {
            e.preventDefault();
            handleTeamSelection(this);
        });
    });

    // Initialize dropped player actions
    initializeDroppedPlayerActions();

    // Initialize the initial state of pool players
    updatePoolPlayerStates();

    // Cleanup when leaving page
    window.addEventListener('unload', cleanupTeamBuilder);
}