import { fixAjaxResponseUrls } from './ajax-handler.js';

export function initRouteHandler(elements) {
    function routeLink(url, callback) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });

        elements.activityElement.style.display = 'block';
        elements.activityElement.style.opacity = 1;

        const xhr = new window.XMLHttpRequest();
        xhr.open("GET", url + "?rel=page", true);

        xhr.onload = function () {
            // Fix image paths in the response
            const fixedResponse = fixAjaxResponseUrls(this.responseText);
            elements.mainElement.innerHTML = fixedResponse;
            elements.mainElement.classList.remove('page-ani');
            elements.mainElement.classList.add('page-ani');

            elements.mainElement.addEventListener('animationend', function (e) {
                elements.mainElement.classList.remove('page-ani');
            }, { once: true });

            if (xhr.readyState === xhr.DONE && (xhr.status >= 200 && xhr.status < 300)) {
                if (this.response) {
                    callback.call(this, fixedResponse);
                    // Re-initialize skater leaders after content is loaded
                    import('./player-leaders.js').then(module => {
                        module.initializeSkaterLeaders();
                    });
                    if (url.includes('team-builder')) {
                        import('./teambuilder.js').then(module => {
                            module.initTeamBuilder();
                        });
                    }
                }
            }

            // IMPORTANT: Dispatch custom event after content loads (used on draft page)
            document.dispatchEvent(new CustomEvent('routeChanged'));
        };

        xhr.onloadend = function () {
            fadeOutElement(elements.activityElement);
        };

        xhr.onerror = function () {
            console.error("Error during AJAX request:", xhr.status, xhr.statusText);
            fadeOutElement(elements.activityElement);
        };

        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send();
    }

    // Handle page links
    const anchors = document.querySelectorAll("a[rel=page]");
    anchors.forEach(function (trigger) {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            let pageUrl = this.getAttribute("href");

            // Fix URLs to ensure they don't include ajax/ or pages/ in the path
            if (pageUrl.includes('/ajax/')) {
                pageUrl = pageUrl.replace('/ajax/', '/');
            }
            if (pageUrl.includes('/pages/')) {
                pageUrl = pageUrl.replace('/pages/', '/');
            }

            routeLink(pageUrl, function (data) {
                elements.mainElement.innerHTML = data;
            });

            if (pageUrl != window.location) {
                window.history.pushState({ url: pageUrl, type: 'page' }, '', pageUrl);
            }
            return false;
        });
    });

    // Update popstate handler to better manage history states
    window.addEventListener("popstate", function (event) {
        // Check if we have state data to determine the page type
        if (event.state) {
            const state = event.state;

            if (state.type === 'page') {
                // Handle regular page navigation
                routeLink(state.url || window.location.pathname, function (data) {
                    elements.mainElement.innerHTML = data;

                    if (localStorage.getItem('seeScores') == 'yes') {
                        const scoreSwitch = document.querySelector('.switch input');
                        if (scoreSwitch) scoreSwitch.checked = true;

                        const games = document.querySelectorAll('.no-team-selected .game');
                        games.forEach(game => game.classList.toggle('scores'));
                    }
                });
            } else if (state.gameId) {
                // Handle game page
                const gameId = state.gameId;

                elements.activityElement.style.display = 'block';
                elements.activityElement.style.opacity = 1;

                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    elements.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                    elements.mainElement.classList.add('page-ani');

                    elements.mainElement.addEventListener('animationend', function () {
                        elements.mainElement.classList.remove('page-ani');
                    }, { once: true });
                };

                xhr.onloadend = function () {
                    fadeOutElement(elements.activityElement);
                };

                // Determine endpoint based on URL pattern
                let endpoint = 'ajax/post-game';
                if (window.location.pathname.includes('live-game')) {
                    endpoint = 'ajax/live-game';
                } else if (window.location.pathname.includes('pre-game')) {
                    endpoint = 'ajax/pre-game';
                }

                xhr.open('POST', endpoint);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('gameId=' + gameId);
            } else if (state.team) {
                // Handle team page with improved path handling
                const activeTeam = state.team;
                const teamAbbr = state.abbr;

                // Use absolute path
                const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
                const newPath = baseUrl + '/' + teamAbbr;

                // Update URL if needed
                if (window.location.pathname !== newPath) {
                    window.history.replaceState(state, '', newPath);
                }

                elements.activityElement.style.display = 'block';
                elements.activityElement.style.opacity = 1;

                const xhr = new XMLHttpRequest();

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        elements.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                        elements.mainElement.classList.add('page-ani');

                        elements.mainElement.addEventListener('animationend', function () {
                            elements.mainElement.classList.remove('page-ani');
                        }, { once: true });
                    } else {
                        console.error('Failed to load team view:', xhr.status);
                        // Fallback to a refresh if the AJAX call fails
                        window.location.reload();
                    }
                };

                xhr.onerror = function () {
                    console.error('Network error on team view load');
                    window.location.reload();
                };

                xhr.onloadend = function () {
                    fadeOutElement(elements.activityElement);
                };

                // Use the team-view URL endpoint directly to avoid AJAX conflicts
                xhr.open('POST', 'ajax/team-view');
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('active_team=' + activeTeam);
            } else {
                // Fallback to generic handling for other states
                routeLink(window.location.pathname, function (data) {
                    elements.mainElement.innerHTML = data;
                });
            }
        } else {
            // No state, try to determine page type from URL
            const path = window.location.pathname;

            // Check if URL is a team abbreviation (3 letters)
            const pathParts = path.split('/');
            const lastPathPart = pathParts[pathParts.length - 1];

            if (/^[A-Z]{3}$/.test(lastPathPart)) {
                // Find the team ID from the abbreviation
                const teamElement = document.querySelector(`#team-selection a[data-abbr="${lastPathPart}"]`);
                if (teamElement) {
                    const activeTeam = teamElement.dataset.value;

                    elements.activityElement.style.display = 'block';
                    elements.activityElement.style.opacity = 1;

                    const xhr = new XMLHttpRequest();
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            elements.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                            elements.mainElement.classList.add('page-ani');

                            elements.mainElement.addEventListener('animationend', function () {
                                elements.mainElement.classList.remove('page-ani');
                            }, { once: true });
                        } else {
                            console.error('Failed to load team view:', xhr.status);
                            window.location.reload();
                        }
                    };

                    xhr.onerror = function () {
                        console.error('Network error on team view load');
                        window.location.reload();
                    };

                    xhr.onloadend = function () {
                        fadeOutElement(elements.activityElement);
                    };

                    xhr.open('POST', 'ajax/team-view');
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send('active_team=' + activeTeam);

                    return;
                } else {
                    console.warn('Team abbreviation not found in menu:', lastPathPart);
                    window.location.reload(); // Force a full page reload
                    return;
                }
            }

            // Default to the original behavior
            routeLink(window.location.pathname, function (data) {
                elements.mainElement.innerHTML = data;

                if (localStorage.getItem('seeScores') == 'yes') {
                    const scoreSwitch = document.querySelector('.switch input');
                    if (scoreSwitch) scoreSwitch.checked = true;

                    const games = document.querySelectorAll('.no-team-selected .game');
                    games.forEach(game => game.classList.toggle('scores'));
                }
            });
        }
    });
}

function fadeOutElement(element) {
    element.style.opacity = 0;
    setTimeout(() => {
        element.style.display = 'none';
    }, 500);
}
