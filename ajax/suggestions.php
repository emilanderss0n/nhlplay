<?php
include_once '../path.php';
include_once '../includes/functions.php';
$ApiUrl = 'https://search.d3.nhle.com/api/v1/search/player?culture=en-us&limit=20&q='. $_POST['keystroke']. '&active=true';
$curl = curlInit($ApiUrl);
$players = json_decode($curl);

if (empty($players)) {
    echo '<div class="suggest-message">No matches</div>';
    return;
}

foreach($players as $suggestion) {
    $name = $suggestion->name;
    $name = explode(' ', $name);

    $firstName = substr($name[0], 0, 1);
    $lastName = (isset($name[count($name)-1])) ? $name[count($name)-1] : '';
    $randomNum = substr(str_shuffle("0123456789"), 0, 2);
    ?>
    <a id="player-link" data-link="<?= $suggestion->playerId ?>"><?= $firstName ?>. <?= $lastName ?>
        <div class="headshot">
            <svg class="headshot_wrap" width="128" height="128" style="transform-origin: 0px 0px; transform: scale(0.4);">
                <circle cx="64" cy="72" r="56" fill="<?= teamToColor($suggestion->teamId) ?>"></circle>
                <defs>
                    <linearGradient id="gradient:r<?= $randomNum ?>:" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="20%" stop-opacity="0" stop-color="#000000"></stop>
                        <stop offset="65%" stop-opacity=".25" stop-color="#000000"></stop>
                    </linearGradient>
                </defs>
                <circle cx="64" cy="72" r="56" fill="url(#gradient:r<?= $randomNum ?>:)"></circle>
                <mask id="circleMask:r<?= $randomNum ?>:">
                    <svg>
                        <path fill="#FFFFFF" d="M128 0H0V72H8C8 79.354 9.44848 86.636 12.2627 93.4303C15.077 100.224 19.2019 106.398 24.402 111.598C29.6021 116.798 35.7755 120.923 42.5697 123.737C49.364 126.552 56.646 128 64 128C71.354 128 78.636 126.552 85.4303 123.737C92.2245 120.923 98.3979 116.798 103.598 111.598C108.798 106.398 112.923 100.225 115.737 93.4303C118.552 86.636 120 79.354 120 72H128V0Z"></path>
                    </svg>
                </mask>
                <image mask="url(#circleMask:r<?= $randomNum ?>:)" id="canTop" height="128" href="https://assets.nhle.com/mugs/nhl/<?= $season ?>/<?= $suggestion->teamAbbrev ?>/<?= $suggestion->playerId ?>.png"></image>
            </svg>
        </div><!-- END .headshot -->
    </a>
    <?php
}
?>