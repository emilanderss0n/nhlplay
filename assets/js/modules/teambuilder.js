import { eventManager } from './utils.js';

export function initTeamBuilder() {
    const playerPool = document.querySelector('.tb-selection-players');
    const dropArea = document.querySelector('#team-builder-drop-area');
    const teamSelect = document.querySelector('#team-selection-custom');
    const selectPlayersBtn = document.querySelector('[popovertarget="team-builder-player-pool"]');
    const teamDropdownLabel = document.querySelector('.custom-select .for-dropdown');
    
    if (!playerPool || !dropArea || !teamSelect || !selectPlayersBtn || !teamDropdownLabel) return;

    // Initialize Swiper for each pool
    const pools = ['forwards', 'defensemen', 'goalies'];
    window.teamBuilderSwipers = {};
    let draggedItem = null;
    let activeTeam = null;

    // Disable all slots initially
    const disableSlots = (disabled = true) => {
        dropArea.querySelectorAll('.player-slot').forEach(slot => {
            if (disabled) {
                slot.style.pointerEvents = 'none';
                slot.style.opacity = '0.5';
            } else {
                slot.style.pointerEvents = '';
                slot.style.opacity = '';
            }
        });
    };

    // Initialize slots as disabled
    disableSlots(true);

    // Handle team selection
    eventManager.addDelegatedEventListener(teamSelect, 'a', 'click', async function(e) {
        e.preventDefault();
        const teamId = this.dataset.value;
        const teamName = this.textContent.trim();
        
        // Close the dropdown
        const dropdownCheckbox = document.getElementById('dropdownBuilder');
        if (dropdownCheckbox) {
            dropdownCheckbox.checked = false;
        }
        
        // Update dropdown label
        if (teamDropdownLabel) {
            teamDropdownLabel.innerHTML = `${teamName} <i class="bi bi-arrow-down-short"></i>`;
        }

        // Update UI to loading state
        selectPlayersBtn.classList.add('disabled');
        disableSlots(true);
        
        try {
            // Fetch updated player pool
            const formData = new FormData();
            formData.append('active_team', teamId);
            
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
            
            // Update player pools
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
            
            // Enable UI elements
            activeTeam = teamId;
            selectPlayersBtn.classList.remove('disabled');
            disableSlots(false);
            
            // Update Swipers
            Object.values(window.teamBuilderSwipers).forEach(swiper => {
                if (swiper && swiper.update) {
                    swiper.update();
                }
            });
        } catch (error) {
            console.error('Error loading team roster:', error);
        }
    });

    // Helper functions
    const getPlayerPosition = player => {
        return player.classList.contains('forward') ? 'forward' : 
               player.classList.contains('defenseman') ? 'defenseman' : 'goalie';
    };

    const getPoolTarget = position => {
        return position === 'forward' ? 'forwards' : 
               position === 'defenseman' ? 'defensemen' : 'goalies';
    };

    const updateSwiper = swiper => {
        requestAnimationFrame(() => {
            swiper.update();
            swiper.updateSize();
            swiper.updateSlides();
            swiper.updateProgress();
            swiper.updateSlidesClasses();
        });
    };

    // Function to update pool player states
    const updatePoolPlayerStates = () => {
        // Get all players in slots
        const slottedPlayers = Array.from(dropArea.querySelectorAll('.player-slot .player'));
        
        // Get all players in the pool
        const poolPlayers = Array.from(playerPool.querySelectorAll('.player'));
        
        // Reset all pool players
        poolPlayers.forEach(poolPlayer => {
            poolPlayer.classList.remove('in-slot');
        });
        
        // Mark pool players that are in slots
        poolPlayers.forEach(poolPlayer => {
            const poolPlayerName = poolPlayer.querySelector('.name').textContent;
            const isInSlot = slottedPlayers.some(slottedPlayer => 
                slottedPlayer.querySelector('.name').textContent === poolPlayerName
            );
            if (isInSlot) {
                poolPlayer.classList.add('in-slot');
            }
        });
    };

    // Event handlers
    const dragHandlers = {
        start(e) {
            if (!activeTeam) return;
            draggedItem = this;
            this.classList.add('dragging');
            document.body.style.cursor = 'grabbing';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setDragImage(this, this.offsetWidth / 2, this.offsetHeight / 2);
            e.dataTransfer.setData('position', getPlayerPosition(this));
        },

        end(e) {
            this.classList.remove('dragging');
            document.body.style.cursor = '';
            document.querySelectorAll('.player-slot').forEach(slot => slot.classList.remove('drag-over'));
        },

        enter(e) {
            e.preventDefault();
            if (draggedItem && this.dataset.position === getPlayerPosition(draggedItem)) {
                this.classList.add('drag-over');
            }
        },

        leave(e) {
            this.classList.remove('drag-over');
        },

        over(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = this.dataset.position === getPlayerPosition(draggedItem) ? 'move' : 'none';
        },

        drop(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            if (!draggedItem || this.dataset.position !== getPlayerPosition(draggedItem)) return;

            const existingPlayer = this.querySelector('.player');
            const isFromSlot = draggedItem.parentElement.classList.contains('player-slot');
            const draggedPlayerName = draggedItem.querySelector('.name').textContent;

            // Check if player is already in another slot
            if (!isFromSlot) {
                const isAlreadyInSlot = Array.from(dropArea.querySelectorAll('.player-slot .player')).some(
                    player => player.querySelector('.name').textContent === draggedPlayerName
                );
                if (isAlreadyInSlot) return;
            }

            if (existingPlayer && isFromSlot) {
                // Swap players between slots
                draggedItem.parentElement.appendChild(existingPlayer);
                draggedItem.style.opacity = '1';
                this.appendChild(draggedItem);
            } else if (!isFromSlot) {
                // Clone from pool to slot
                const clone = draggedItem.cloneNode(true);
                clone.style.opacity = '1';
                clone.style.cursor = 'grab';
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
                draggedItem.style.opacity = '1';
                this.appendChild(draggedItem);
            }

            // Reset cursor and update pool player states
            document.body.style.cursor = '';
            if (draggedItem) draggedItem.style.cursor = 'grab';
            updatePoolPlayerStates();
        }
    };

    function initializePlayerDrag(player) {
        player.setAttribute('draggable', 'true');
        player.style.cursor = 'grab';
        eventManager.addEventListener(player, 'dragstart', dragHandlers.start);
        eventManager.addEventListener(player, 'dragend', dragHandlers.end);
        // Prevent Swiper from interfering with drag
        ['touchstart', 'touchmove', 'touchend'].forEach(event => {
            eventManager.addEventListener(player, event, e => e.stopPropagation(), { passive: false });
        });
    }

    function initializeSlotDrop(slot) {
        eventManager.addEventListener(slot, 'dragenter', dragHandlers.enter);
        eventManager.addEventListener(slot, 'dragleave', dragHandlers.leave);
        eventManager.addEventListener(slot, 'dragover', dragHandlers.over);
        eventManager.addEventListener(slot, 'drop', dragHandlers.drop);
        
        // Add click handler for empty slots
        eventManager.addEventListener(slot, 'click', function() {
            if (!this.querySelector('.player')) {
                const poolPopover = document.getElementById('team-builder-player-pool');
                if (poolPopover) {
                    poolPopover.showPopover();
                    const targetTab = getPoolTarget(this.dataset.position);
                    document.querySelector(`.tb-selection-header .btn[data-target="${targetTab}"]`)?.click();
                }
            }
        });
    }

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
    dropArea.querySelectorAll('.player-slot').forEach(initializeSlotDrop);

    // Initialize the initial state of pool players
    updatePoolPlayerStates();
}