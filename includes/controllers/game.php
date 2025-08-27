<?php
/**
 * Game controller - helpers for pre/live/post game data and rendering
 */
function game_prepare_live($gameId)
{
    // Use controller fetch to get live game data
    $data = game_fetch_live_data($gameId);
    foreach ($data as $k => $v) { $GLOBALS[$k] = $v; }
    return $data;
}

function game_fetch_boxscore_json($gameId)
{
    $apiUrl = NHLApi::gameCenterBoxscore($gameId);
    $opts = [
        'http' => ['method' => 'GET','header' => ['User-Agent: Mozilla/5.0']],
        'ssl' => ['verify_peer' => false,'verify_peer_name' => false]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) return null;
    return $response;
}

function game_prepare_pre($gameId)
{
    // Fetch pre-game data via controller function
    $data = game_fetch_pre_data($gameId);
    // expose to globals and return
    foreach ($data as $k => $v) {
        $GLOBALS[$k] = $v;
    }
    return $data;
}

function game_fetch_pre_data($gameId)
{
    $ApiUrl = NHLApi::gameCenterLanding($gameId);
    $curl = curlInit($ApiUrl);
    $game = json_decode($curl);
    $awayTeam = $game->awayTeam;
    $awayTeamId = $game->awayTeam->id;
    $awayTeamAbbrev = $game->awayTeam->abbrev;
    $awayTeamLogo = $game->awayTeam->logo;
    $homeTeam = $game->homeTeam;
    $homeTeamId = $game->homeTeam->id;
    $homeTeamAbbrev = $game->homeTeam->abbrev;
    $homeTeamLogo = $game->homeTeam->logo;
    $utcTimezone = new DateTimeZone('UTC');
    $gameTime = new DateTime( $game->startTimeUTC, $utcTimezone );

    $ApiUrl = NHLApi::gameCenterRightRail($gameId);
    $curl = curlInit($ApiUrl);
    $railGame = json_decode($curl);
    $seasonStats = $railGame->teamSeasonStats ?? null;
    $seasonSeries = $railGame->seasonSeries ?? null;

    return compact(
        'game','awayTeam','awayTeamId','awayTeamAbbrev','awayTeamLogo',
        'homeTeam','homeTeamId','homeTeamAbbrev','homeTeamLogo','utcTimezone','gameTime',
        'railGame','seasonStats','seasonSeries'
    );
}

function game_prepare_post($gameId)
{
    $data = game_fetch_post_data($gameId);
    foreach ($data as $k => $v) { $GLOBALS[$k] = $v; }
    return $data;
}

function game_fetch_live_data($gameId)
{
    // First API - boxscore
    $ApiUrl = NHLApi::gameCenterBoxscore($gameId);
    $curl = curlInit($ApiUrl);
    $game = json_decode($curl);
    $awayTeam = $game->awayTeam;
    $awayTeamName = $game->awayTeam->commonName->default;
    $awayTeamId = $game->awayTeam->id;
    $awayTeamTri = $game->awayTeam->abbrev;
    $awayTeamStats = $game->playerByGameStats->awayTeam ?? null;
    $homeTeam = $game->homeTeam;
    $homeTeamName = $game->homeTeam->commonName->default;
    $homeTeamId = $game->homeTeam->id;
    $homeTeamTri = $game->homeTeam->abbrev;
    $homeTeamStats = $game->playerByGameStats->homeTeam ?? null;
    $periodNow = $game->periodDescriptor->number ?? null;
    $periodRemaining = $game->clock->timeRemaining ?? null;
    $periodPaused = $game->clock->inIntermission ?? null;
    $winner = 'home';
    $winnerId = $homeTeamId;
    $utcTimezone = new DateTimeZone('UTC');
    $gameTime = new DateTime($game->startTimeUTC, $utcTimezone);
    if (isset($awayTeam->score) && isset($homeTeam->score) && $awayTeam->score > $homeTeam->score) {
        $winner = 'away'; $winnerId = $awayTeamId;
    }
    $winnerTeam = $winnerId;

    // Second API - landing
    $ApiUrl = NHLApi::gameCenterLanding($gameId);
    $curl = curlInit($ApiUrl);
    $gameContent = json_decode($curl);
    $boxscore = $gameContent->summary ?? null;

    // Third API - right rail
    $ApiUrl = NHLApi::gameCenterRightRail($gameId);
    $curl = curlInit($ApiUrl);
    $railContent = json_decode($curl);
    $gameStats = $railContent->teamGameStats ?? null;
    $awayGameStats = array();
    if (is_array($gameStats)) {
        foreach ($gameStats as $awayGameStat) {
            $awayGameStats[$awayGameStat->category] = $awayGameStat->awayValue;
        }
    }
    $homeGameStats = array();
    if (is_array($gameStats)) {
        foreach ($gameStats as $homeGameStat) {
            $homeGameStats[$homeGameStat->category] = $homeGameStat->homeValue;
        }
    }

    return compact(
        'game','awayTeam','awayTeamName','awayTeamId','awayTeamTri','awayTeamStats',
        'homeTeam','homeTeamName','homeTeamId','homeTeamTri','homeTeamStats',
        'periodNow','periodRemaining','periodPaused','winner','winnerId','utcTimezone',
        'gameTime','winnerTeam','gameContent','boxscore','railContent','gameStats','awayGameStats','homeGameStats'
    );
}

function game_fetch_post_data($gameId)
{
    // First API - boxscore
    $ApiUrl = NHLApi::gameCenterBoxscore($gameId);
    $curl = curlInit($ApiUrl);
    $game = json_decode($curl);
    $awayTeam = $game->awayTeam;
    $awayTeamName = $game->awayTeam->commonName->default;
    $awayTeamId = $game->awayTeam->id;
    $awayTeamTri = $game->awayTeam->abbrev;
    $awayTeamStats = $game->playerByGameStats->awayTeam ?? null;
    $homeTeamStats = $game->playerByGameStats->homeTeam ?? null;
    $homeTeam = $game->homeTeam;
    $homeTeamName = $game->homeTeam->commonName->default;
    $homeTeamId = $game->homeTeam->id;
    $homeTeamTri = $game->homeTeam->abbrev;
    $winner = 'home';
    $winnerId = $homeTeamId;
    $utcTimezone = new DateTimeZone('UTC');
    $gameTime = new DateTime($game->startTimeUTC, $utcTimezone);
    if (isset($awayTeam->score) && isset($homeTeam->score) && $awayTeam->score > $homeTeam->score) {
        $winner = 'away'; $winnerId = $awayTeamId;
    }
    $winnerTeam = $winnerId;
    $endPeriod = $game->gameOutcome->lastPeriodType ?? null;

    // Second API - landing
    $ApiUrl = NHLApi::gameCenterLanding($gameId);
    $curl = curlInit($ApiUrl);
    $gameContent = json_decode($curl);
    $boxscore = $gameContent->summary ?? null;

    // Third API - right rail
    $ApiUrl = NHLApi::gameCenterRightRail($gameId);
    $curl = curlInit($ApiUrl);
    $railContent = json_decode($curl);
    $gameStats = $railContent->teamGameStats ?? null;
    $gameVideo = $railContent->gameVideo ?? null;
    $awayGameStats = array();
    if (is_array($gameStats)) {
        foreach ($gameStats as $awayGameStat) {
            $awayGameStats[$awayGameStat->category] = $awayGameStat->awayValue;
        }
    }
    $homeGameStats = array();
    if (is_array($gameStats)) {
        foreach ($gameStats as $homeGameStat) {
            $homeGameStats[$homeGameStat->category] = $homeGameStat->homeValue;
        }
    }

    return compact(
        'game','awayTeam','awayTeamName','awayTeamId','awayTeamTri','awayTeamStats',
        'homeTeam','homeTeamName','homeTeamId','homeTeamTri','homeTeamStats',
        'winner','winnerId','utcTimezone','gameTime','winnerTeam','endPeriod',
        'gameContent','boxscore','railContent','gameStats','gameVideo','awayGameStats','homeGameStats'
    );
}

function game_find_reddit_thread($awayName, $homeName)
{
    // Simple fallback to return a reddit search URL
    $q = urlencode($awayName . ' ' . $homeName . ' thread');
    return 'https://www.reddit.com/r/hockey/search/?q=' . $q . '&sort=new';
}
