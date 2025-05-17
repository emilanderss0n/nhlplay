import { initPreGamePage, cleanupPreGamePage } from './pre-game-handlers.js';
import { fixAjaxResponseUrls } from './ajax-handler.js';
import { checkRedditGameThread } from './reddit-thread-handler.js';

export function initGameHandlers(elements) {

    // Box Score
    document.addEventListener('click', function (e) {
        if (e.target.closest('.game.final')) {
            e.preventDefault();
            const gameElement = e.target.closest('.game.final');
            const gameId = gameElement.dataset.postLink;

            elements.activityElement.style.display = 'block';
            elements.activityElement.style.opacity = 1;

            window.scrollTo({ top: 0, behavior: 'smooth' });

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                window.history.pushState({ gameId: gameId, type: 'game' }, '', 'post-game?gameId=' + gameId);
                elements.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                elements.mainElement.classList.add('page-ani');

                elements.mainElement.addEventListener('animationend', function () {
                    elements.mainElement.classList.remove('page-ani');
                }, { once: true });
            };

            xhr.onloadend = function () {
                fadeOutElement(elements.activityElement);
            };

            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/post-game.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send('gameId=' + gameId);
        }
    });

    // Live Score
    document.addEventListener('click', function (e) {
        if (e.target.closest('.game.live')) {
            e.preventDefault();
            const gameElement = e.target.closest('.game.live');
            const gameId = gameElement.dataset.postLink;

            elements.activityElement.style.display = 'block';
            elements.activityElement.style.opacity = 1;

            window.scrollTo({ top: 0, behavior: 'smooth' });

            let isFirstLoad = true;

            function loadGameData() {
                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    if (isFirstLoad) {
                        window.history.pushState({ gameId: gameId, type: 'game' }, '', 'live-game?gameId=' + gameId);
                        elements.mainElement.classList.add('page-ani');
                        isFirstLoad = false;
                    }                    elements.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                    elements.mainElement.addEventListener('animationend', function () {
                        elements.mainElement.classList.remove('page-ani');
                    }, { once: true });                    
                    // Trigger the Reddit game thread check after the content is loaded
                    // Use setTimeout to ensure DOM is fully ready before checking
                    setTimeout(() => {
                        // Get the game ID from the URL or DOM
                        const gameId = new URLSearchParams(window.location.search).get('gameId') || 
                                       document.querySelector('.reddit-game-thread[data-game-id]')?.dataset.gameId;
                        
                        if (gameId) {
                            checkRedditGameThread(gameId);
                        }
                    }, 500);
                };

                xhr.onloadend = function () {
                    fadeOutElement(elements.activityElement);
                };

                const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
                xhr.open('POST', baseUrl + '/ajax/live-game.php');
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('gameId=' + gameId);
            }

            loadGameData();
        }
    });

    // Pre-game
    document.addEventListener('click', function (e) {
        if (e.target.closest('.game.preview:not(.disabled)')) {
            e.preventDefault();
            const gameElement = e.target.closest('.game.preview:not(.disabled)');
            const gameId = gameElement.dataset.postLink;

            if (gameId === null) {
                return false;
            }

            elements.activityElement.style.display = 'block';
            elements.activityElement.style.opacity = 1;

            window.scrollTo({ top: 0, behavior: 'smooth' });

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                window.history.pushState({ gameId: gameId, type: 'game' }, '', 'pre-game?gameId=' + gameId);
                elements.mainElement.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                elements.mainElement.classList.add('page-ani');

                // Initialize pre-game components after loading content
                initPreGamePage();

                elements.mainElement.addEventListener('animationend', function () {
                    elements.mainElement.classList.remove('page-ani');
                }, { once: true });
            };

            xhr.onloadend = function () {
                fadeOutElement(elements.activityElement);
            };

            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/pre-game.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send('gameId=' + gameId);
        }
    });
}

function fadeOutElement(element) {
    element.style.opacity = 0;
    setTimeout(() => {
        element.style.display = 'none';
    }, 500);
}
