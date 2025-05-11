import { initDOMElements } from './modules/dom-elements.js';
import { initializeSkaterLeaders } from './modules/player-leaders.js';
import { initRouteHandler } from './modules/route-handler.js';
import { initMenuHandlers, checkRecentTrades } from './modules/menu-handlers.js';
import { initTeamHandlers } from './modules/team-handlers.js';
import { initGameHandlers } from './modules/game-handlers.js';
import { initStandingsHandlers } from './modules/standings-handlers.js';
import { initPlayerHandlers } from './modules/player-handlers.js';
import { initUISettings } from './modules/ui-settings.js';
import { initLiveGames } from './modules/live-games.js';
import { initStatLeadersHandlers } from './modules/stat-leaders-handlers.js';
import { convertUTCTimesToLocal } from './modules/utils.js';
import { initPreGamePage } from './modules/pre-game-handlers.js';
import { initDraftPage } from './modules/drafts.js';
import { initTeamBuilder} from './modules/teambuilder.js';
import { initRedditPosts } from './modules/reddit-handlers.js';


document.addEventListener('DOMContentLoaded', function () {
    const elements = initDOMElements();
    initializeSkaterLeaders();
    initRouteHandler(elements);
    initMenuHandlers();
    checkRecentTrades();
    initTeamHandlers(elements);
    initGameHandlers(elements);
    initStandingsHandlers();
    initPlayerHandlers(elements);
    initUISettings();
    initLiveGames();
    initStatLeadersHandlers();
    convertUTCTimesToLocal();
    initDraftPage();
    initTeamBuilder();
    initRedditPosts();

    if (document.querySelector('.pre-game-cont')) {
        initPreGamePage();
    }
});