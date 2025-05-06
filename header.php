<?php
require_once 'path.php';
require_once 'includes/functions.php';
require_once "includes/MobileDetect.php";

$page = '';
$detect = new \Detection\MobileDetect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
$isIOS = $detect->isiOS();

// Generate merged CSS file - but only in production mode
$isDevelopment = ($_SERVER['HTTP_HOST'] == 'localhost');
$mergedCSS = $isDevelopment ? false : generateMergedCSS([
    'assets/css/datatables.css',
    'assets/css/global.css',
    'assets/css/team-builder.css',
    'assets/css/swiper.css',
    'assets/css/bootstrap-icons.min.css',
], 'merged.min.css');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" /> 
    <meta name="viewport" content="height=device-height, width=device-width, initial-scale=1.0, minimum-scale=1, maximum-scale=5.0, user-scalable=yes" />
    <title>NHL PLAY</title>
    <meta name="keywords" content="nhl, hockey, tracker, schedule, stats, statistics, live, stream, betting" />
    <meta name="description" content="Keep track of advanced statistics in the NHL. Get the latest scores, stats, standings, and much more." />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/favicon/favicon-192x192.png">
    <link rel="icon" type="image/png" sizes="48x48" href="assets/img/favicon/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon/favicon-32x32.png">

    <!-- Preload Google Fonts for faster loading and to prevent FOUT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" />
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Tomorrow:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tomorrow:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <?php if ($mergedCSS): ?>
        <link rel="stylesheet" type="text/css" media="screen" href="<?= $mergedCSS ?>">
    <?php else: ?>
        <link rel="stylesheet" type="text/css" media="screen" href="assets/css/datatables.css">
        <link rel="stylesheet" type="text/css" media="screen" href="assets/css/global.css">
        <link rel="stylesheet" type="text/css" media="screen" href="assets/css/team-builder.css">
        <link rel="stylesheet" type="text/css" media="screen" href="assets/css/swiper.css">
        <link rel="stylesheet" type="text/css" media="screen" href="assets/css/bootstrap-icons.min.css">
    <?php endif; ?>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-N1MMVBJY11"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-N1MMVBJY11');
    </script>
</head>
<body class="<?= $page ?><?php if ($playoffs) { echo ' playoffs'; } ?>">
    <header>
        <div class="cont">
            <div class="header-title">
                <a href="index" class="logo">  
                    <?php if ($isIOS): ?>
                    <!-- iOS fallback image -->
                    <h3>NHLPLAY</h3>
                    <?php else: ?>    
                    <svg width="160" height="42" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="150 60 200 28" preserveAspectRatio="xMidYMid">
                        <defs>
                            <linearGradient id="editing-stripes-gradient" x1="0.1719" x2="0.8280" y1="0.1226" y2="0.8773">
                                <stop offset="0" style="stop-color: var(--stop01)"></stop>
                                <stop offset="1" style="stop-color: var(--stop02)"></stop>
                            </linearGradient>
                            <filter id="editing-stripes" x="0" y="0" width="100" height="200">
                                <feGaussianBlur stdDeviation="5" in="SourceAlpha" result="BLUR"/>
                                <feSpecularLighting surfaceScale="4" specularConstant="0.8" specularExponent="30" lighting-color="var(--light-color)" in="BLUR" result="SPECULAR">
                                <fePointLight x="40" y="-30" z="200"></fePointLight>
                                </feSpecularLighting>
                                <feComposite operator="in" in="SPECULAR" in2="SourceAlpha" result="COMPOSITE"/>
                                <feMerge>
                                    <feMergeNode in="SourceGraphic" />
                                    <feMergeNode in="COMPOSITE"/>
                                </feMerge>
                            </filter>
                        </defs>
                        <g filter="url(#editing-stripes)">
                            <g transform="translate(146.69997453689575, 90.05500030517578)">
                                <path d="M8.17-23.35L8.17 0L3.86 0L3.86-30.11L8.17-30.11L26.27-6.76L26.27-30.11L30.57-30.11L30.57 0L26.27 0L8.17-23.35ZM59.84-30.11L64.14-30.11L64.14 0L59.84 0L59.84-12.89L42.60-12.89L42.60 0L38.30 0L38.30-30.11L42.60-30.11L42.60-17.20L59.84-17.20L59.84-30.11ZM71.87-30.11L76.17-30.11L76.17-4.30L91.65-4.30L91.65 0L71.87 0L71.87-30.11ZM101.12 0L96.81 0L96.81-30.11L113.29-30.11L113.29-30.11Q120.60-30.11 120.60-22.78L120.60-22.78L120.60-18.50L120.60-18.50Q120.60-11.17 113.29-11.17L113.29-11.17L101.12-11.17L101.12 0ZM101.12-25.80L101.12-15.47L113.06-15.47L113.06-15.47Q114.79-15.47 115.54-16.23L115.54-16.23L115.54-16.23Q116.30-16.99 116.30-18.71L116.30-18.71L116.30-22.57L116.30-22.57Q116.30-24.29 115.54-25.05L115.54-25.05L115.54-25.05Q114.79-25.80 113.06-25.80L113.06-25.80L101.12-25.80ZM126.61-30.11L130.91-30.11L130.91-4.30L146.38-4.30L146.38 0L126.61 0L126.61-30.11ZM160.58-30.11L164.88-30.11L176.49 0L172.19 0L169.52-6.93L155.94-6.93L153.27 0L148.97 0L160.58-30.11ZM162.74-24.50L157.58-11.23L167.86-11.23L162.74-24.50ZM175.21-30.11L180.21-30.11L188.96-16.04L197.74-30.11L202.74-30.11L191.13-11.95L191.13 0L186.82 0L186.82-11.95L175.21-30.11Z" fill="url(#editing-stripes-gradient)"></path>
                            </g>
                        </g>
                    </svg>
                    <?php endif; ?>
                </a>
                <div id="activity"><span class="loader"></span></div>
            </div>
            <div class="sm-only">
                <a id="nav-mobile-search" href="javascript:void(0)"><i class="bi bi-search"></i></a>
                <label id="nav-mobile" class="hamburger">
                    <input type="checkbox">
                    <svg viewBox="0 0 32 32">
                        <path class="line line-top-bottom" d="M27 10 13 10C10.8 10 9 8.2 9 6 9 3.5 10.8 2 13 2 15.2 2 17 3.8 17 6L17 26C17 28.2 18.8 30 21 30 23.2 30 25 28.2 25 26 25 23.8 23.2 22 21 22L7 22"></path>
                        <path class="line" d="M7 16 27 16"></path>
                    </svg>
                </label>
            </div>
            <nav id="main-menu" role="navigation">
                <div class="wrapper">
                    
                    <div class="menu-right">
                        <div class="suggestion-input">
                            <div id="activity-sm"><span class="loader"></span></div>
                            <input id="player-search" type="text" placeholder="Player Search" autocomplete="off">
                            <div class="suggestion-box"></div>
                        </div>
                        <div class="menu-links">
                            <input class="dropdown" type="checkbox" id="dropdown1" name="dropdown1"/>
                            <label class="for-dropdown" for="dropdown1">Links <i class="bi bi-arrow-down-short"></i></label>
                            <div class="section-dropdown"> 
                                <a id="link-game-scores" href="<?= BASE_URL ?>/scores" rel="page">Scores <i class="bi bi-arrow-right-short"></i></a>
                                <a id="link-stat-leaders" href="<?= BASE_URL ?>/stat-leaders" rel="page">Stat Leaders <i class="bi bi-arrow-right-short"></i></a>
                                <a id="link-game-recaps" href="<?= BASE_URL ?>/recaps" rel="page">Game Recaps <i class="bi bi-arrow-right-short"></i></a>
                                <?php if ($deviceType == 'tablet' || $deviceType == 'computer'): ?>
                                    <a id="link-builder" href="<?= BASE_URL ?>/team-builder" rel="page">Team Builder <i class="bi bi-arrow-right-short"></i></a>
                                <?php endif; ?>
                                <a id="link-trades" href="<?= BASE_URL ?>/trades" rel="page">
                                    <span>Trades</span>
                                    <i class="bi bi-arrow-right-short"></i>
                                </a>
                                <a id="link-draft" href="<?= BASE_URL ?>/draft" rel="page">Draft <i class="bi bi-arrow-right-short"></i></a>
                                <a id="link-last-season" href="<?= BASE_URL ?>/last-season-overview" rel="page">Last Season <i class="bi bi-arrow-right-short"></i></a>
                            </div>
                        </div>
                        <ul class="menu-teams" aria-haspopup="true" style="display: none">
                            <li>
                                <button class="tablet-show">Teams <i class="bi bi-chevron-down"></i></button>
                                <div class="pre-cont" aria-label="submenu">
                                    <ul class="container">
                                        <?php include('includes/teamSelection.php'); ?>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                        <div class="menu-teams">
                            <input class="dropdown" type="checkbox" id="dropdown2" name="dropdown2"/>
                            <label class="for-dropdown" for="dropdown2">Teams <i class="bi bi-arrow-down-short"></i></label>
                            <div class="section-dropdown" id="team-selection"> 
                                <div class="fader-top"></div>
                                <div class="container">
                                <?php include('includes/teamSelection.php'); ?>
                                </div>
                                <div class="fader-bottom"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <div id="mobile-search">
        <div class="suggestion-input">
            <div id="activity-sm"><span class="loader"></span></div>
            <input id="player-search-mobile" type="text" placeholder="Player Search" autocomplete="off">
            <div class="suggestion-box"></div>
        </div>
    </div>

    <dialog id="gameLogModal">
        <div class="modal-header"><p>Post Game</p><a href="javascript:void(0);" id="closeGameLogModal"><i class="bi bi-x-lg"></i></a></div>
        <div class="content"></div>
    </dialog>
    <div id="gameLogOverlay"></div>

    <div class="overlay">
        <div id="activity-player">
            <span class="loader"></span>
        </div>
        <div id="player-modal" role="dialog"></div>
    </div>
</body>
</html>