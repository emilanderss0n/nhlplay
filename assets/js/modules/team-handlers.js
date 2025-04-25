import { fadeInElement, fadeOutElement, eventManager } from './utils.js';
import { fixAjaxResponseUrls } from './ajax-handler.js';

function sortRosterByPoints(rosterElement) {
    const rosterItems = Array.from(rosterElement.children);
    rosterItems.sort(function (a, b) {
        const val1 = parseInt(a.dataset.points || 0);
        const val2 = parseInt(b.dataset.points || 0);
        return val2 - val1;
    });

    // Remove existing items
    rosterItems.forEach(item => item.remove());
    // Add them back in sorted order
    rosterItems.forEach(item => rosterElement.appendChild(item));
}

export function initTeamHandlers(elements) {
    // Team roster filter
    document.addEventListener('click', function (e) {
        if (e.target.closest('.filter-team-roster .filter-btn')) {
            const filterBtn = e.target.closest('.filter-team-roster .filter-btn');
            const type = filterBtn.dataset.type;

            document.querySelectorAll('.filter-team-roster .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            filterBtn.classList.add('active');

            document.querySelectorAll('.player').forEach(player => {
                player.style.display = 'none';
            });

            // Sort roster by points from data attribute
            const teamRoster = document.querySelector(".team-roster");
            if (teamRoster) {
                sortRosterByPoints(teamRoster);
            }

            document.querySelectorAll('.' + type).forEach((el, index) => {
                el.style.display = 'block';
                el.style.opacity = '0';

                setTimeout(function () {
                    setTimeout(function () {
                        el.style.opacity = '1';
                    }, 100 * index);
                }, 500);
            });
        }
    });

    // Team selection
    document.addEventListener('click', function (e) {
        if (e.target.closest('#team-selection a')) {
            e.preventDefault();
            const teamLink = e.target.closest('#team-selection a');
            const activeTeam = teamLink.dataset.value;
            const teamAbbr = teamLink.dataset.abbr;

            elements.activityElement.style.display = 'block';
            elements.activityElement.style.opacity = 1;

            window.scrollTo({ top: 0, behavior: 'smooth' });

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                // Use absolute path for team navigation
                const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
                const newPath = baseUrl + '/' + teamAbbr;

                window.history.pushState(
                    { team: activeTeam, type: 'team', abbr: teamAbbr },
                    '',
                    newPath
                );

                // Fix image paths in the response
                elements.mainElement.innerHTML = fixAjaxResponseUrls(this.responseText);
                elements.mainElement.classList.add('page-ani');

                elements.mainElement.addEventListener('animationend', function () {
                    elements.mainElement.classList.remove('page-ani');
                }, { once: true });

                // Sort roster by points immediately after loading
                const teamRoster = elements.mainElement.querySelector(".team-roster");
                if (teamRoster) {
                    sortRosterByPoints(teamRoster);
                }
            };

            xhr.onloadend = function () {
                fadeOutElement(elements.activityElement);
                // Destroy any existing swiper instance before creating a new one
                if (window.teamViewSwiper && typeof window.teamViewSwiper.destroy === 'function') {
                    window.teamViewSwiper.destroy(true, true);
                }
                
                // Create new swiper instance
                const swiperContainer = document.querySelector('.schedule-games.swiper');
                if (swiperContainer) {
                    window.teamViewSwiper = new Swiper(swiperContainer, {
                        slidesPerView: 3,
                        spaceBetween: 10,
                        breakpoints: {
                            320: { slidesPerView: 1, spaceBetween: 10 },
                            480: { slidesPerView: 1, spaceBetween: 20 },
                            768: { slidesPerView: 2, spaceBetween: 20 },
                            1024: { slidesPerView: 3, spaceBetween: 20 }
                        }
                    });
                }
            };

            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/team-view.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send('active_team=' + activeTeam);
        }
    });

    // Team view game log - using event delegation
    let previousContent = '';

    eventManager.addDelegatedEventListener(document, '#showGameLog', 'click', function (e) {
        e.preventDefault();
        const activeTeam = this.dataset.value;
        const container = document.getElementById('teamMain');

        container.classList.remove('ani');
        previousContent = container.innerHTML;

        elements.activityElement.style.display = 'block';
        elements.activityElement.style.opacity = 1;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            if (xhr.status === 200) {
                container.innerHTML = fixAjaxResponseUrls(xhr.responseText);

                // Clean up any existing game log handlers
                eventManager.removeEventListenersBySelector('.team-game-log .log-game, #closeGameLog, #closeGameLogModal');

                // Add new event listeners for the dynamically loaded content
                setupGameLogEventListeners();
            }
        };

        xhr.onloadend = function () {
            fadeOutElement(elements.activityElement);
            container.classList.add('ani');
        };

        const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
        xhr.open('POST', baseUrl + '/ajax/team-view-gamelog.php');
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send('active_team=' + activeTeam);
    });

    function setupGameLogEventListeners() {
        // Game log post-game modal using the event manager
        document.querySelectorAll('.team-game-log .log-game').forEach(logGame => {
            eventManager.addEventListener(logGame, 'click', function (e) {
                e.preventDefault();
                const gameId = this.dataset.postLink;
                const postGameModal = document.getElementById('gameLogModal');
                const postGameModalContent = postGameModal.querySelector('.content');

                elements.activityElement.style.display = 'block';
                elements.activityElement.style.opacity = 1;

                window.scrollTo({ top: 0, behavior: 'smooth' });

                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    postGameModalContent.innerHTML = xhr.responseText;
                };

                xhr.onloadend = function () {
                    fadeOutElement(elements.activityElement);
                    postGameModal.style.display = 'block';
                    fadeInElement(document.getElementById('gameLogOverlay'));
                };

                const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
                xhr.open('POST', baseUrl + '/ajax/post-game.php');
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('gameId=' + gameId);
            });
        });

        // Close game log modal
        const closeGameLogModalBtn = document.getElementById('closeGameLogModal');
        if (closeGameLogModalBtn) {
            eventManager.addEventListener(closeGameLogModalBtn, 'click', function (e) {
                e.preventDefault();
                const postGameModal = document.getElementById('gameLogModal');
                postGameModal.style.display = 'none';
                postGameModal.innerHTML = '';

                const gameLogOverlay = document.getElementById('gameLogOverlay');
                gameLogOverlay.style.display = 'none';
            });
        }

        // Close game log
        const closeGameLogBtn = document.getElementById('closeGameLog');
        if (closeGameLogBtn) {
            eventManager.addEventListener(closeGameLogBtn, 'click', function (e) {
                e.preventDefault();
                const container = document.getElementById('teamMain');

                container.classList.remove('ani');
                elements.activityElement.style.display = 'block';
                elements.activityElement.style.opacity = 1;

                if (previousContent) {
                    container.innerHTML = previousContent;

                    // Clean up any game log related event listeners
                    eventManager.removeEventListenersBySelector('.team-game-log .log-game, #closeGameLog, #closeGameLogModal');

                    // Re-initialize content as needed
                    initializeTeamContent(container);

                    container.classList.add('ani');
                    fadeOutElement(elements.activityElement);

                    const gameLogOverlay = document.getElementById('gameLogOverlay');
                    if (gameLogOverlay) gameLogOverlay.style.display = 'none';
                }
            });
        }
    }

    // Advanced Team Stats
    eventManager.addDelegatedEventListener(document, '#showTeamAdvStats', 'click', function (e) {
        e.preventDefault();
        const activeTeam = this.dataset.value;
        const container = document.getElementById('teamMain');

        container.classList.remove('ani');
        previousContent = container.innerHTML;

        elements.activityElement.style.display = 'block';
        elements.activityElement.style.opacity = 1;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            if (xhr.status === 200) {
                container.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                // Clean up any game log related event listeners
                eventManager.removeEventListenersBySelector('#closeTeamAdvStats');
                setupAdvTeamStatsEventListeners();
            }
        };

        xhr.onloadend = function () {
            fadeOutElement(elements.activityElement);
            container.classList.add('ani');
            let tas1 = new jsdatatables.JSDataTable('#teamAdvTable1', {
                paging: false,
                searchable: true,
                sortable: false,
            });
            let tas2 = new jsdatatables.JSDataTable('#teamAdvTable2', {
                paging: false,
                searchable: true,
                sortable: false,
            });
            let tas3 = new jsdatatables.JSDataTable('#teamAdvTable3', {
                paging: false,
                searchable: true,
                sortable: false,
            });
            let tas4 = new jsdatatables.JSDataTable('#teamAdvTable4', {
                paging: false,
                searchable: true,
                sortable: false,
            });
            let tas5 = new jsdatatables.JSDataTable('#teamAdvTable5', {
                paging: false,
                searchable: true,
                sortable: false,
            });
            let tas6 = new jsdatatables.JSDataTable('#teamAdvTable6', {
                paging: false,
                searchable: true,
                sortable: false,
            });
        };

        const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
        xhr.open('POST', baseUrl + '/ajax/team-view-adv-stats.php');
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send('active_team=' + activeTeam);
    });

    function setupAdvTeamStatsEventListeners() {
        // Advanced Team Stats
        const closeTeamAdvStats = document.getElementById('closeTeamAdvStats');
        if (closeTeamAdvStats) {
            eventManager.addEventListener(closeTeamAdvStats, 'click', function (e) {
                e.preventDefault();
                const container = document.getElementById('teamMain');

                container.classList.remove('ani');
                elements.activityElement.style.display = 'block';
                elements.activityElement.style.opacity = 1;

                if (previousContent) {
                    container.innerHTML = previousContent;

                    // Clean up any game log related event listeners
                    eventManager.removeEventListenersBySelector('#closeTeamAdvStats');

                    // Re-initialize content as needed
                    initializeTeamContent(container);

                    container.classList.add('ani');
                    fadeOutElement(elements.activityElement);
                }
            });
        }
    }

    function initializeTeamContent(container) {
        // Sort roster by points
        const teamRoster = container.querySelector(".team-roster");
        if (teamRoster) {
            const rosterItems = Array.from(teamRoster.children);
            rosterItems.sort(function (a, b) {
                const val1 = parseInt(a.dataset.points || 0);
                const val2 = parseInt(b.dataset.points || 0);
                return val2 - val1;
            });

            rosterItems.forEach(item => teamRoster.appendChild(item));
        }

        // Show players based on active filter
        const activeFilter = container.querySelector('.filter-team-roster .filter-btn.active')?.dataset.type;
        if (activeFilter) {
            container.querySelectorAll('.player').forEach(player => {
                player.style.display = 'none';
            });

            container.querySelectorAll('.' + activeFilter).forEach(el => {
                el.style.display = 'block';
                el.style.opacity = '1';
            });
        }
        
        // Reinitialize Swiper if it exists in the content
        const swiperContainer = container.querySelector('.schedule-games.swiper');
        if (swiperContainer) {
            // Destroy any existing swiper instance before creating a new one
            if (window.teamViewSwiper && typeof window.teamViewSwiper.destroy === 'function') {
                window.teamViewSwiper.destroy(true, true);
            }
            
            // Create new swiper instance
            window.teamViewSwiper = new Swiper(swiperContainer, {
                slidesPerView: 3,
                spaceBetween: 10,
                breakpoints: {
                    320: { slidesPerView: 1, spaceBetween: 10 },
                    480: { slidesPerView: 1, spaceBetween: 20 },
                    768: { slidesPerView: 2, spaceBetween: 20 },
                    1024: { slidesPerView: 3, spaceBetween: 20 }
                }
            });
        }
    }

    // Game log filter buttons
    document.addEventListener('click', function (e) {
        if (e.target.closest('.filter-game-log .filter-btn')) {
            const filterBtn = e.target.closest('.filter-game-log .filter-btn');
            const type = filterBtn.dataset.type;

            document.querySelectorAll('.filter-game-log .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            filterBtn.classList.add('active');

            const logGames = document.querySelectorAll('.log-game');

            if (type === 'all') {
                logGames.forEach(game => game.style.display = 'block');
            } else {
                logGames.forEach(game => {
                    game.style.display = game.classList.contains(type) ? 'block' : 'none';
                });
            }

            document.querySelectorAll('.log-game:not([style*="display: none"])').forEach((el, index) => {
                el.style.opacity = '0';
                setTimeout(function () {
                    setTimeout(function () {
                        el.style.opacity = '1';
                    }, 75 * index);
                }, 150);
            });
        }
    });

    // Clickable links to team page
    document.addEventListener('click', function (e) {
        if (e.target.id === 'team-link' || e.target.closest('#team-link')) {
            e.preventDefault();
            const teamLink = e.target.id === 'team-link' ? e.target : e.target.closest('#team-link');
            const activeTeam = teamLink.dataset.link;

            const teamSelection = document.querySelector(`#team-selection a[data-value="${activeTeam}"]`);
            if (teamSelection) {
                // Simulate click on the team selection
                const clickEvent = new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                });
                teamSelection.dispatchEvent(clickEvent);
            }
        }
    });

    // Injury list toggle
    eventManager.addDelegatedEventListener(document, '#injury-list-toggle', 'click', function(e) {
        e.preventDefault();
        const injuryList = document.querySelector('#injury-list');
        if (injuryList) {
            injuryList.classList.toggle('show');
        }
    });
    
    // Initialize swiper on page load if it exists
    const swiperContainer = document.querySelector('.schedule-games.swiper');
    if (swiperContainer) {
        // Create initial swiper instance and store it globally
        window.teamViewSwiper = new Swiper(swiperContainer, {
            slidesPerView: 3,
            spaceBetween: 10,
            breakpoints: {
                320: { slidesPerView: 1, spaceBetween: 10 },
                480: { slidesPerView: 1, spaceBetween: 20 },
                768: { slidesPerView: 2, spaceBetween: 20 },
                1024: { slidesPerView: 3, spaceBetween: 20 }
            }
        });
    }
}
