<?php
// Use the new NHL API utility
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

// Use the new NHL API utility
$ApiUrl = NHLApi::gameCenterRightRail($gameId);
$curl = curlInit($ApiUrl);
$railGame = json_decode($curl);
$seasonStats = $railGame->teamSeasonStats;
$seasonSeries = $railGame->seasonSeries;