<?php
/**
 * Schedule normalization helpers
 */
function normalize_schedule($resp)
{
    $out = new stdClass();
    $out->games = [];
    if (!$resp) return $out;

    if (is_array($resp)) {
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

    if (isset($resp->gameWeek) && is_array($resp->gameWeek)) {
        foreach ($resp->gameWeek as $week) {
            if (isset($week->games) && is_array($week->games)) {
                foreach ($week->games as $g) $out->games[] = $g;
            }
        }
        return $out;
    }

    // fallback heuristics
    foreach ($resp as $k => $v) {
        if (is_array($v) && isset($v[0]->gameDate)) {
            foreach ($v as $g) $out->games[] = $g;
            return $out;
        }
    }

    return $out;
}
