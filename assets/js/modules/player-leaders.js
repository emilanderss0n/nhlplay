export function initializeSkaterLeaders() {
    const runInitialization = () => {
        const leaderBoxes = document.querySelectorAll('.home-leaders .leaders-box');
        
        if (leaderBoxes.length === 0) {
            return;
        }
        
        leaderBoxes.forEach(function (scoring) {
        const players = scoring.querySelectorAll('.player');
        const playerTexts = scoring.querySelectorAll('.player-text');
        const valueTopElements = scoring.querySelectorAll('.value-top');
        const pointsElements = scoring.querySelectorAll('.points');

        if (players.length > 0) players[0].classList.add('active');
        if (playerTexts.length > 0) playerTexts[0].classList.add('active');
        if (valueTopElements.length > 0) valueTopElements[0].classList.add('active');
        if (pointsElements.length > 0) pointsElements[0].classList.add('active');

        let maxVal = 0;
        scoring.querySelectorAll('.points-cont .points-line').forEach(function (pointsLine) {
            const value = parseInt(pointsLine.dataset.value, 10);
            if (value > maxVal) {
                maxVal = value;
            }
        });

        scoring.querySelectorAll('.points-cont .points-line').forEach(function (pointsLine) {
            const value = parseInt(pointsLine.dataset.value, 10);
            const widthPercentage = (value / maxVal) * 84;
            pointsLine.style.width = widthPercentage + '%';
        });

        scoring.querySelectorAll('.points').forEach(function (point) {
            point.addEventListener('mouseenter', function () {
                const playerId = this.dataset.playerId;
                scoring.querySelectorAll('.player').forEach(el => el.classList.remove('active'));
                scoring.querySelectorAll('.points').forEach(el => el.classList.remove('active'));
                scoring.querySelectorAll('.player-text').forEach(el => el.classList.remove('active'));
                scoring.querySelectorAll('.value-top').forEach(el => el.classList.remove('active'));

                scoring.querySelector(`.player[data-player-cont="${playerId}"]`)?.classList.add('active');
                scoring.querySelector(`.player-text[data-player-text="${playerId}"]`)?.classList.add('active');
                scoring.querySelector(`.value-top[data-player-text="${playerId}"]`)?.classList.add('active');
                this.classList.add('active');
            });

            point.addEventListener('mouseleave', function () {
                const playerId = this.dataset.playerId;
                this.classList.remove('active');
                scoring.querySelector(`.points[data-player-id="${playerId}"]`)?.classList.add('active');
            });
        });
    });

    document.querySelectorAll(".home-leaders .leaders-box").forEach(function (box) {
        const loadElements = box.querySelectorAll(".load");
        loadElements.forEach(element => element.remove());

        box.querySelectorAll(".player-cont").forEach(el => el.classList.add("fadeInTop"));
        const pointsContElements = box.querySelectorAll(".points-cont");
        pointsContElements.forEach(el => {
            el.classList.add("fadeInTop");
            el.style.animationDelay = "0.3s";
        });
    });
    
    };
    
    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runInitialization);
    } else {
        runInitialization();
    }
}
