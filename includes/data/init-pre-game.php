<?php
$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'.$gameId.'/landing';
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

$ApiUrl = 'https://api-web.nhle.com/v1/gamecenter/'.$gameId.'/right-rail';
$curl = curlInit($ApiUrl);
$railGame = json_decode($curl);
$seasonStats = $railGame->teamSeasonStats;
$seasonSeries = $railGame->seasonSeries;