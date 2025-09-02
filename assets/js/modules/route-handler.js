import { fixAjaxResponseUrls } from './ajax-handler.js';
import { checkRedditGameThread } from './reddit-thread-handler.js';

export function initRouteHandler(elements) {
    // Helper to resolve live DOM elements if initial `elements` are null or stale
    function resolveElements() {
        return {
            mainElement: (elements && elements.mainElement) || document.querySelector('main'),
            activityElement: (elements && elements.activityElement) || document.getElementById('activity') || document.getElementById('activity-sm') || null
        };
    }
    // Safely render HTML into the live <main> element.
    // If the response contains a top-level <main>, use its inner content to avoid nested <main> tags.
    function renderIntoMain(html) {
        const els = resolveElements();
        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMain = doc.querySelector('main');

            if (els.mainElement) {
                if (newMain) {
                    els.mainElement.innerHTML = newMain.innerHTML;
                } else {
                    els.mainElement.innerHTML = html;
                }
                return true;
            } else if (newMain) {
                // No existing main element - append parsed main to body
                document.body.appendChild(newMain);
                return true;
            }
        } catch (err) {
            console.warn('route-handler.renderIntoMain parse error:', err);
        }

        // Fallback: try direct write into existing main if available
        if (els.mainElement) {
            els.mainElement.innerHTML = html;
            return true;
        }
        return false;
    }
    function routeLink(url, callback) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });

        const els = resolveElements();
        if (els.activityElement) {
            els.activityElement.style.display = 'block';
            els.activityElement.style.opacity = 1;
        }

        const xhr = new window.XMLHttpRequest();
        xhr.open("GET", url + "?rel=page", true);

        xhr.onload = function () {
            // Fix image paths in the response
            const fixedResponse = fixAjaxResponseUrls(this.responseText);
            const els2 = resolveElements();
            // Render safely into main (avoid nested <main> if response contains one)
            const rendered = renderIntoMain(fixedResponse);
            if (rendered && resolveElements().mainElement) {
                const me = resolveElements().mainElement;
                me.classList.remove('page-ani');
                me.classList.add('page-ani');
                me.addEventListener('animationend', function (e) {
                    me.classList.remove('page-ani');
                }, { once: true });
            } else {
                console.warn('route-handler: main element not found to render AJAX response');
            }

            if (xhr.readyState === xhr.DONE && (xhr.status >= 200 && xhr.status < 300)) {
                if (this.response) {
                    callback.call(this, fixedResponse);
                    // Use auto-initializer for better timing and consistency
                    import('./auto-initializer.js').then(module => {
                        module.initAfterAjax(document);
                    });
                }
            }            // IMPORTANT: Dispatch custom event after content loads (used on draft page)
            document.dispatchEvent(new CustomEvent('routeChanged'));
            
            // Check for Reddit game thread if we're on a live game page
            if (url.includes('live-game')) {
                setTimeout(() => {
                    const gameId = new URLSearchParams(window.location.search).get('gameId') || 
                                  document.querySelector('.reddit-game-thread[data-game-id]')?.dataset.gameId;
                    
                    if (gameId) {
                        checkRedditGameThread(gameId);
                    }
                }, 500);
            }
        };

        xhr.onloadend = function () {
            const els3 = resolveElements();
            if (els3.activityElement) fadeOutElement(els3.activityElement);
        };

        xhr.onerror = function () {
            console.error("Error during AJAX request:", xhr.status, xhr.statusText);
            const els4 = resolveElements();
            if (els4.activityElement) fadeOutElement(els4.activityElement);
        };
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send();
    }

    // Delegate clicks for links with rel=page so dynamically-inserted anchors still work
    document.addEventListener('click', function (e) {
        const el = e.target.closest('a[rel="page"]');
        if (!el) return;

        // Allow default behavior for modified clicks (new tab, ctrl/meta, middle click)
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        e.preventDefault();
        let pageUrl = el.getAttribute('href');

        // Fix URLs to ensure they don't include ajax/ or pages/ in the path
        if (pageUrl.includes('/ajax/')) pageUrl = pageUrl.replace('/ajax/', '/');
        if (pageUrl.includes('/pages/')) pageUrl = pageUrl.replace('/pages/', '/');

        routeLink(pageUrl, function (data) {
            const elsLocal = resolveElements();
            renderIntoMain(data);
        });

        if (pageUrl != window.location) {
            window.history.pushState({ url: pageUrl, type: 'page' }, '', pageUrl);
        }
        return false;
    });

    // Update popstate handler to better manage history states
    window.addEventListener("popstate", function (event) {
        // Check if we have state data to determine the page type
        if (event.state) {
            const state = event.state;

            if (state.type === 'page') {
                // Handle regular page navigation
                routeLink(state.url || window.location.pathname, function (data) {
                    const elsLocal = resolveElements();
                    if (renderIntoMain(data)) {
                        if (localStorage.getItem('seeScores') == 'yes') {
                            const scoreSwitch = document.querySelector('.switch input');
                            if (scoreSwitch) scoreSwitch.checked = true;

                            const games = document.querySelectorAll('.no-team-selected .game');
                            games.forEach(game => game.classList.toggle('scores'));
                        }
                    }
                });
            } else if (state.gameId) {
                // Handle game page
                const gameId = state.gameId;
                const elsLocal = resolveElements();
                if (elsLocal.activityElement) {
                    elsLocal.activityElement.style.display = 'block';
                    elsLocal.activityElement.style.opacity = 1;
                }

                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    if (elsLocal.mainElement) {
                        elsLocal.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                        elsLocal.mainElement.classList.add('page-ani');

                        elsLocal.mainElement.addEventListener('animationend', function () {
                            elsLocal.mainElement.classList.remove('page-ani');
                        }, { once: true });
                    }
                };

                xhr.onloadend = function () {
                    if (elsLocal.activityElement) fadeOutElement(elsLocal.activityElement);
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

                const elsLocal2 = resolveElements();
                if (elsLocal2.activityElement) {
                    elsLocal2.activityElement.style.display = 'block';
                    elsLocal2.activityElement.style.opacity = 1;
                }

                const xhr = new XMLHttpRequest();

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        if (elsLocal2.mainElement) {
                            elsLocal2.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                            elsLocal2.mainElement.classList.add('page-ani');

                            elsLocal2.mainElement.addEventListener('animationend', function () {
                                elsLocal2.mainElement.classList.remove('page-ani');
                            }, { once: true });
                        }
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
                    if (elsLocal2.activityElement) fadeOutElement(elsLocal2.activityElement);
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

                    const elsTeam = resolveElements();
                    if (elsTeam.activityElement) {
                        elsTeam.activityElement.style.display = 'block';
                        elsTeam.activityElement.style.opacity = 1;
                    }

                    const xhr = new XMLHttpRequest();
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            if (elsTeam.mainElement) {
                                elsTeam.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                                elsTeam.mainElement.classList.add('page-ani');

                                elsTeam.mainElement.addEventListener('animationend', function () {
                                    elsTeam.mainElement.classList.remove('page-ani');
                                }, { once: true });
                            }
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
                        if (elsTeam.activityElement) fadeOutElement(elsTeam.activityElement);
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
                    const elsLocal3 = resolveElements();
                    if (elsLocal3.mainElement) {
                        elsLocal3.mainElement.innerHTML = data;

                        if (localStorage.getItem('seeScores') == 'yes') {
                            const scoreSwitch = document.querySelector('.switch input');
                            if (scoreSwitch) scoreSwitch.checked = true;

                            const games = document.querySelectorAll('.no-team-selected .game');
                            games.forEach(game => game.classList.toggle('scores'));
                        }
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
