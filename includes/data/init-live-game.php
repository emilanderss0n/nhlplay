<?php
$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'. $gameId .'/boxscore';
$curl = curlInit($ApiUrl);
$game = json_decode($curl);
$awayTeam = $game->awayTeam;
$awayTeamName = $game->awayTeam->commonName->default;
$awayTeamId = $game->awayTeam->id;
$awayTeamTri = $game->awayTeam->abbrev;
$awayTeamStats = $game->playerByGameStats->awayTeam;
$homeTeam = $game->homeTeam;
$homeTeamName = $game->homeTeam->commonName->default;
$homeTeamId = $game->homeTeam->id;
$homeTeamTri = $game->homeTeam->abbrev;
$homeTeamStats = $game->playerByGameStats->homeTeam;
$periodNow = $game->periodDescriptor->number;
$periodRemaining = $game->clock->timeRemaining;
$periodPaused = $game->clock->inIntermission;
$winner = 'home';
$winnerId = $homeTeamId;
$utcTimezone = new DateTimeZone('UTC');
$gameTime = new DateTime( $game->startTimeUTC, $utcTimezone );
if ($awayTeam->score > $homeTeam->score) {
    $winner = 'away';
    $winnerId = $awayTeamId;
}
$winnerTeam = $winnerId;

$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'. $gameId .'/landing';
$curl = curlInit($ApiUrl);
$gameContent = json_decode($curl);

$boxscore = $gameContent->summary;

// Third API
$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'. $gameId .'/right-rail';
$curl = curlInit($ApiUrl);
$railContent = json_decode($curl);
$gameStats = $railContent->teamGameStats;
$awayGameStats = array();
foreach ($gameStats as $awayGameStat) {
    $awayGameStats[$awayGameStat->category] = $awayGameStat->awayValue;
}
$homeGameStats = array();
foreach ($gameStats as $homeGameStat) {
    $homeGameStats[$homeGameStat->category] = $homeGameStat->homeValue;
}