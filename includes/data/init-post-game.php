<?php

$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'. $gameId .'/boxscore';
$curl = curlInit($ApiUrl);
$game = json_decode($curl);
$awayTeam = $game->awayTeam;
$awayTeamName = $game->awayTeam->commonName->default;
$awayTeamId = $game->awayTeam->id;
$awayTeamTri = $game->awayTeam->abbrev;
$awayTeamStats = $game->playerByGameStats->awayTeam;
$homeTeamStats = $game->playerByGameStats->homeTeam;
$homeTeam = $game->homeTeam;
$homeTeamName = $game->homeTeam->commonName->default;
$homeTeamId = $game->homeTeam->id;
$homeTeamTri = $game->homeTeam->abbrev;
$winner = 'home';
$winnerId = $homeTeamId;
$utcTimezone = new DateTimeZone('UTC');
$gameTime = new DateTime( $game->startTimeUTC, $utcTimezone );
if ($awayTeam->score > $homeTeam->score) {
    $winner = 'away';
    $winnerId = $awayTeamId;
}
$winnerTeam = $winnerId;
$endPeriod = $game->gameOutcome->lastPeriodType;

// Second API
$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'. $gameId .'/landing';
$curl = curlInit($ApiUrl);
$gameContent = json_decode($curl);
$boxscore = $gameContent->summary;

// Third API
$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'. $gameId .'/right-rail';
$curl = curlInit($ApiUrl);
$railContent = json_decode($curl);
$gameStats = $railContent->teamGameStats;
$gameVideo = isset($railContent->gameVideo) ? $railContent->gameVideo : null;
$awayGameStats = array();
foreach ($gameStats as $awayGameStat) {
    $awayGameStats[$awayGameStat->category] = $awayGameStat->awayValue;
}
$homeGameStats = array();
foreach ($gameStats as $homeGameStat) {
    $homeGameStats[$homeGameStat->category] = $homeGameStat->homeValue;
}