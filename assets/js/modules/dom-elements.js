export function initDOMElements() {
    return {
        mainElement: document.querySelector('main'),
        activityElement: document.getElementById('activity'),
        activitySmElement: document.getElementById('activity-sm'),
        playerActivityElement: document.getElementById('activity-player'),
        playerModal: document.getElementById('player-modal'),
        gameLogModal: document.getElementById('gameLogModal'),
        gameLogOverlay: document.getElementById('gameLogOverlay'),
        mainMenu: document.getElementById('main-menu'),
        navMobile: document.getElementById('nav-mobile'),
        playerSearchMobile: document.getElementById('nav-mobile-search')
    };
}
