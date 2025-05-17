<?php
// Check if team subreddit is already set (it should be set in team-view.php)
if (!isset($teamSubreddit) || empty($teamSubreddit)) {
    return; // Exit if no subreddit is defined for this team
}
?>
<div class="team-roster-header" style="margin-top: 3rem">
    <div class="team-roster-header-cont">
        <h5 class="stats">r/<?= $teamSubreddit ?> - Recent Posts</h5>
        <a href="https://www.reddit.com/r/<?= $teamSubreddit ?>/" target="_blank" rel="noopener noreferrer" class="btn sm">View r/<?= $teamSubreddit ?></a>
    </div>
</div>
<div class="reddit-feed" id="team-reddit-feed-section" data-subreddit="<?= $teamSubreddit ?>" data-limit="8">
    <div class="reddit-posts grid grid-300 grid-gap grid-gap-row">
        <div class="load">
            <div class="loading-spinner"></div>
        </div>
    </div>
</div>
