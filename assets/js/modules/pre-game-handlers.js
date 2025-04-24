/**
 * Initializes pre-game page elements including team leaders and countdown
 */
export function initPreGamePage() {
    // Initialize team point leaders boxes
    initTeamPointLeaders();

    // Initialize game countdown
    initGameCountdown();
}

/**
 * Initializes team point leaders boxes with hover events and animations
 */
function initTeamPointLeaders() {
    document.querySelectorAll('.leaders-box').forEach(function (scoring) {
        // Set initial active states
        const firstPlayer = scoring.querySelector('.player');
        const firstPlayerText = scoring.querySelector('.player-text');
        const firstValueTop = scoring.querySelector('.value-top');
        const firstPoints = scoring.querySelector('.points');

        if (firstPlayer) firstPlayer.classList.add('active');
        if (firstPlayerText) firstPlayerText.classList.add('active');
        if (firstValueTop) firstValueTop.classList.add('active');
        if (firstPoints) firstPoints.classList.add('active');

        // Find maximum value for scaling
        let maxVal = 0;
        scoring.querySelectorAll('.points-cont .points-line').forEach(function (line) {
            const value = parseInt(line.dataset.value);
            if (value > maxVal) {
                maxVal = value;
            }
        });

        // Set width percentages based on max value
        scoring.querySelectorAll('.points-cont .points-line').forEach(function (line) {
            const value = parseInt(line.dataset.value);
            const widthPercentage = (value / maxVal) * 84;
            line.style.width = widthPercentage + '%';
        });

        // Add event listeners for hover effects
        scoring.querySelectorAll('.points').forEach(function (point) {
            point.addEventListener('mouseenter', function () {
                const playerId = this.dataset.playerId;

                // Remove active class from all elements
                scoring.querySelectorAll('.player, .points, .player-text, .value-top').forEach(function (el) {
                    el.classList.remove('active');
                });

                // Add active class to relevant elements
                scoring.querySelector(`.player[data-player-cont="${playerId}"]`)?.classList.add('active');
                scoring.querySelector(`.player-text[data-player-text="${playerId}"]`)?.classList.add('active');
                scoring.querySelector(`.value-top[data-player-text="${playerId}"]`)?.classList.add('active');
                this.classList.add('active');
            });

            point.addEventListener('mouseleave', function () {
                const playerId = this.dataset.playerId;
                this.classList.remove('active');
                this.classList.add('active');
            });
        });
    });

    // Add animation classes
    document.querySelectorAll('.home-leaders .leaders-box').forEach(function (box) {
        const playerCont = box.querySelector('.player-cont');
        if (playerCont) playerCont.classList.add('fadeInTop');

        const pointsCont = box.querySelector('.points-cont');
        if (pointsCont) {
            pointsCont.classList.add('fadeInTop');
            pointsCont.style.animationDelay = '0.3s';
        }
    });
}

/**
 * Initializes the game countdown timer
 */
function initGameCountdown() {
    const countdownElement = document.getElementById("countdown");
    if (!countdownElement) return;

    // Get the game time from the element's data attribute or extract from HTML
    let gameTimeStr = countdownElement.dataset.gameTime;

    // If no data attribute, try to get from a script tag or parent element
    if (!gameTimeStr) {
        // Look for game time in nearby script or elements
        const gameTimeElement = document.querySelector('.game-date');
        if (gameTimeElement) {
            // Extract datetime from text content (Y / m / d - H:i UTC)
            const dateText = gameTimeElement.textContent.trim();
            const dateMatch = dateText.match(/(\d{4})\s*\/\s*(\d{1,2})\s*\/\s*(\d{1,2})\s*-\s*(\d{1,2}):(\d{1,2})/);

            if (dateMatch) {
                const [_, year, month, day, hour, minute] = dateMatch;
                gameTimeStr = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}:00Z`;
            }
        }
    }

    if (!gameTimeStr) {
        countdownElement.innerHTML = "Time unavailable";
        return;
    }

    const gameTime = new Date(gameTimeStr).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = gameTime - now;

        if (distance < 0) {
            countdownElement.innerHTML = "Game has started!";
            clearInterval(interval);
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownElement.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";
    }

    const interval = setInterval(updateCountdown, 1000);
    updateCountdown();

    // Store the interval ID in the element's dataset for cleanup
    countdownElement.dataset.intervalId = interval;
}

/**
 * Cleans up pre-game resources (like timers) when navigating away
 */
export function cleanupPreGamePage() {
    const countdownElement = document.getElementById("countdown");
    if (countdownElement && countdownElement.dataset.intervalId) {
        clearInterval(parseInt(countdownElement.dataset.intervalId));
    }
}