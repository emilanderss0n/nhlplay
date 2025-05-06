import { fixAjaxResponseUrls } from './ajax-handler.js';

export function initLiveGames() {
    // Initialize live games
    document.querySelectorAll('.ajax-check').forEach(function (gameElement) {
        const gameId = gameElement.dataset.gameId;
        const hasLiveContainer = gameElement.querySelector('.live-game-time-container') !== null;

        if (hasLiveContainer) {
            updateLiveGame(gameId);
            // Update every 20 seconds
            setInterval(() => updateLiveGame(gameId), 20000);
        }
    });
}

function updateLiveGame(gameId) {
    // Check if current page is allowed to update live games
    const currentPath = window.location.pathname;
    // Allow updates on index.php, index, live-game, root path, or paths ending with /
    if (!currentPath.endsWith('index.php') &&
        !currentPath.endsWith('index') &&
        !currentPath.endsWith('/') &&
        !currentPath.endsWith('live-game')) {
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('GET', `ajax/live-info.php?gameId=${gameId}`, true);
    xhr.responseType = 'json';

    xhr.onload = function () {
        if (xhr.status === 200) {
            const data = xhr.response;
            const gameContainer = document.querySelector(`.extra[data-game-id="${gameId}"] .live-game-time-container`);
            const awayScoreElement = document.querySelector(`.away-score[data-game-id="${gameId}"]`);
            const homeScoreElement = document.querySelector(`.home-score[data-game-id="${gameId}"]`);

            if (gameContainer) {
                let htmlContent = '';
                if (data.clock.inIntermission) {
                    htmlContent = `
                        <div class="live-indicator${data.clock.inIntermission ? ' pause' : ''}"></div>
                        <div class="live-data period">Period: Intermission (${data.periodDescriptor.number})</div>
                    `;
                } else {
                    htmlContent = `
                        <div class="live-indicator${data.clock.inIntermission ? ' pause' : ''}"></div>
                        <div class="live-data period">Period: ${data.periodDescriptor.number} 
                            - <span class="time-remaining">${data.clock.timeRemaining}</span>
                        </div>
                    `;
                }
                gameContainer.innerHTML = fixAjaxResponseUrls(htmlContent);
            }

            // Update scores
            if (awayScoreElement) awayScoreElement.textContent = data.awayTeam.score;
            if (homeScoreElement) homeScoreElement.textContent = data.homeTeam.score;
        }
    };

    xhr.onerror = function () {
        console.error('Error fetching game data');
    };

    xhr.send();
}
