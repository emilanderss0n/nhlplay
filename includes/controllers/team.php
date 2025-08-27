<?php
/**
 * Team controller - helpers for team views and reddit feed
 */
function team_prepare($activeTeam, $season = null)
{
    $season = $season ?? ($GLOBALS['season'] ?? null);
    $teamAbbrev = idToTeamAbbrev($activeTeam);
    $teamAbbrev2 = idToTeamAbbrevInjuries($activeTeam);

    $teamRosterStats = getTeamRosterStats($teamAbbrev, $season);
    $teamRosterInfo = getTeamRosterInfo($teamAbbrev, $season);
    $injuryCount = getInjuriesTeamCount($teamAbbrev2);
    $medianAge = getTeamMedianAge($teamRosterInfo);
    $teamInfo = getTeamStats($teamAbbrev);
    $teamStatsAdv = getTeamStatsAdv($activeTeam, $season);
    $schedules = team_fetch_schedule($activeTeam, $season);
    $injuredPlayerIds = getInjuredPlayerIds($teamAbbrev2);

    return compact(
        'teamAbbrev', 'teamAbbrev2', 'teamRosterStats', 'teamRosterInfo', 'injuryCount',
        'medianAge', 'teamInfo', 'teamStatsAdv', 'schedules', 'injuredPlayerIds'
    );
}

function team_fetch_schedule($active_team, $season = null)
{
    $teamAbbr = idToTeamAbbrev($active_team);
    $ApiUrl = NHLApi::teamSchedule($teamAbbr, $season);
    $curl = curlInit($ApiUrl);
    $resp = json_decode($curl);
    return team_normalize_schedule($resp);
}

/**
 * Normalize various schedule response shapes into an object with ->games array
 */
function team_normalize_schedule($resp)
{
    $out = new stdClass();
    $out->games = [];
    if (!$resp) return $out;

    if (is_array($resp)) {
        // already an array of games
        foreach ($resp as $g) $out->games[] = $g;
        return $out;
    }

    if (isset($resp->games) && is_array($resp->games)) {
        foreach ($resp->games as $g) $out->games[] = $g;
        return $out;
    }

    if (isset($resp->gamesByDate) && is_array($resp->gamesByDate)) {
        foreach ($resp->gamesByDate as $dateBlock) {
            if (isset($dateBlock->games) && is_array($dateBlock->games)) {
                foreach ($dateBlock->games as $g) $out->games[] = $g;
            }
        }
        return $out;
    }

    // fallback: try to find any property that looks like games
    foreach ($resp as $k => $v) {
        if (is_array($v) && isset($v[0]->gameDate)) {
            foreach ($v as $g) $out->games[] = $g;
            return $out;
        }
    }

    return $out;
}

function team_fetch_reddit_posts($subreddit, $limit = 8)
{
    $subreddit = preg_replace('/[^a-zA-Z0-9_]/', '', $subreddit);
    $limit = min(max(intval($limit), 1), 50);
    $cacheFile = __DIR__ . '/../../cache/reddit-' . $subreddit . '.json';
    $cacheLifetime = 1800; // 30 minutes

    if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - $cacheLifetime))) {
        $redditData = json_decode(file_get_contents($cacheFile));
    } else {
        $redditData = fetchRedditPosts($subreddit, 'hot', $limit + 5);
        if ($redditData !== false) {
            @file_put_contents($cacheFile, json_encode($redditData));
        } else {
            if (file_exists($cacheFile)) {
                $redditData = json_decode(file_get_contents($cacheFile));
            } else {
                return ['error' => 'No posts available from r/' . $subreddit];
            }
        }
    }

    $formattedPosts = [];
    $count = 0;
    if ($redditData && isset($redditData->data) && isset($redditData->data->children)) {
        foreach ($redditData->data->children as $post) {
            if ($count >= $limit) break;
            $postData = $post->data;
            if (isset($postData->stickied) && $postData->stickied) continue;
            $formattedPosts[] = [
                'title' => $postData->title,
                'author' => $postData->author,
                'permalink' => $postData->permalink,
                'score' => $postData->score,
                'comments' => $postData->num_comments,
                'created_utc' => $postData->created_utc
            ];
            $count++;
        }
    }

    return $formattedPosts;
}
