import { eventManager } from './utils.js';

// Cache DOM elements
const DOM = {
    dropArea: null,
    playerPool: null,
    teamSelect: null,
    selectPlayersBtn: null,
    teamDropdownLabel: null,
    clearBtn: null,
    dropdownCheckbox: null,
    playerPoolPopover: null
};

// Utility functions
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Memoized position checkers
const positionCache = new WeakMap();
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
    disableSlots(!window.activeTeam);
}

// Optimized pool player states with Map for O(1) lookup
function updatePoolPlayerStates() {
    if (!DOM.dropArea || !DOM.playerPool) return;

    const slottedPlayers = new Map();
    DOM.dropArea.querySelectorAll('.player-slot .player .name').forEach(name => {
        slottedPlayers.set(name.textContent, true);
    });
    
    DOM.playerPool.querySelectorAll('.player').forEach(poolPlayer => {
        const name = poolPlayer.querySelector('.name').textContent;
        poolPlayer.classList.toggle('in-slot', slottedPlayers.has(name));
    });
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
        if (!window.activeTeam) return;
        window.draggedItem = this;
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
        if (window.draggedItem && this.dataset.position === getPlayerPosition(window.draggedItem)) {
            this.classList.add('drag-over');
        }
    },

    leave(e) {
        this.classList.remove('drag-over');
    },

    over(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = this.dataset.position === getPlayerPosition(window.draggedItem) ? 'move' : 'none';
    },

    drop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        if (!window.draggedItem || this.dataset.position !== getPlayerPosition(window.draggedItem)) return;

        const existingPlayer = this.querySelector('.player');
        const isFromSlot = window.draggedItem.parentElement.classList.contains('player-slot');
        const draggedPlayerName = window.draggedItem.querySelector('.name').textContent;

        // Check if player is already in another slot
        if (!isFromSlot) {
            const isAlreadyInSlot = Array.from(DOM.dropArea.querySelectorAll('.player-slot .player')).some(
                player => player.querySelector('.name').textContent === draggedPlayerName
            );
            if (isAlreadyInSlot) return;
        }

        if (existingPlayer && isFromSlot) {
            // Swap players between slots
            window.draggedItem.parentElement.appendChild(existingPlayer);
            window.draggedItem.style.opacity = '1';
            this.appendChild(window.draggedItem);
        } else if (!isFromSlot) {
            // Clone from pool to slot
            const clone = window.draggedItem.cloneNode(true);
            Object.assign(clone.style, { opacity: '1', cursor: 'grab' });
            // Preserve team ID and player ID when cloning
            Object.assign(clone.dataset, {
                teamId: window.draggedItem.dataset.teamId,
                playerId: window.draggedItem.dataset.playerId
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
            window.draggedItem.style.opacity = '1';
            this.appendChild(window.draggedItem);
        }

        // Reset cursor and update states
        document.body.style.cursor = '';
        if (window.draggedItem) window.draggedItem.style.cursor = 'grab';
        updatePoolPlayerStates();
        debouncedSaveState();
    }
};

// Optimized state management
const debouncedSaveState = debounce(() => {
    const teamState = {
        forward: [],
        defenseman: [],
        goalie: []
    };
    
    ['forward', 'defenseman', 'goalie'].forEach(position => {
        const slots = Array.from(document.querySelectorAll(`.player-slot[data-position="${position}"]`));
        slots.forEach((slot, index) => {
            const player = slot.querySelector('.player');
            if (player) {
                teamState[position][index] = {
                    name: player.querySelector('.name').textContent,
                    playerId: player.dataset.playerId,
                    teamId: player.dataset.teamId,
                    slotIndex: index
                };
            }
        });
        // Remove empty slots
        teamState[position] = teamState[position].filter(Boolean);
    });
    
    localStorage.setItem('teamBuilderState', JSON.stringify(teamState));
}, 250);

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
        const previousTeamId = window.activeTeam;
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
                currentPool.innerHTML = newPool.innerHTML;
                // Reinitialize drag for new players
                currentPool.querySelectorAll('.player').forEach(initializePlayerDrag);
            }
        });
        
        // Update Swipers
        Object.values(window.teamBuilderSwipers).forEach(swiper => {
            if (swiper && swiper.update) {
                swiper.update();
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
        window.activeTeam = previousTeamId;
        if (DOM.selectPlayersBtn) {
            DOM.selectPlayersBtn.classList.add('disabled');
        }
        updateSlotsState();
    }
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
            // Load roster for this team
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
                    if (!slot || slot.querySelector('.player')) continue; // Skip if slot is taken

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

export function initTeamBuilder() {
    // Cache DOM elements
    Object.assign(DOM, {
        dropArea: document.querySelector('#team-builder-drop-area'),
        playerPool: document.querySelector('.tb-selection-players'),
        teamSelect: document.querySelector('#team-selection-custom'),
        selectPlayersBtn: document.querySelector('[popovertarget="team-builder-player-pool"]'),
        teamDropdownLabel: document.querySelector('.custom-select .for-dropdown'),
        clearBtn: document.getElementById('btn-clear-tb'),
        dropdownCheckbox: document.getElementById('dropdownBuilder'),
        playerPoolPopover: document.getElementById('team-builder-player-pool')
    });

    if (!DOM.dropArea || !DOM.playerPool || !DOM.teamSelect || !DOM.selectPlayersBtn || !DOM.teamDropdownLabel) return;

    // Handle clicks outside dropdown
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select') && DOM.dropdownCheckbox) {
            DOM.dropdownCheckbox.checked = false;
        }
    });

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
    window.draggedItem = null;
    window.activeTeam = null;

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
        
        // Add click handler for empty slots
        eventManager.addEventListener(slot, 'click', function() {
            if (!this.querySelector('.player')) {
                if (DOM.playerPoolPopover) {
                    DOM.playerPoolPopover.showPopover();
                    const targetTab = getPoolTarget(this.dataset.position);
                    document.querySelector(`.tb-selection-header .btn[data-target="${targetTab}"]`)?.click();
                }
            }
        });
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

    // Initialize the initial state of pool players
    updatePoolPlayerStates();
}