<?php
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }

include_once '../path.php';
include_once '../includes/functions.php';

?>

<main>
    <div class="wrap">
        <div id="compare-players">
            <div class="component-header">
                <h3 class="title">Compare Players</h3>
            </div>
            <div class="compare-search-container">
                <div class="suggestion-input">
                    <input id="player-search-compare-1" class="player-compare-search" type="text" placeholder="Player 1" autocomplete="off">
                    <div class="suggestion-box"></div>
                </div>
                <div class="suggestion-input">
                    <input id="player-search-compare-2" class="player-compare-search" type="text" placeholder="Player 2" autocomplete="off">
                    <div class="suggestion-box"></div>
                </div>
            </div>

            <div class="compare-container">
                <div id="player-compare-1" class="player-compare-container">
                    <div id="activity-player-compare"><span class="loader"></span></div>
                </div>
                <div class="compare-divider">
                    <div class="label">Games</div>
                    <div class="label">Goals</div>
                    <div class="label">Assists</div>
                    <div class="label">Points</div>
                    <div class="label">PPG</div>
                    <div class="label">+/-</div>
                    <div class="label">PIM</div>
                    <div class="label">Shots</div>
                    <div class="label">Shots %</div>
                    <div class="label">SAT%</div>
                    <div class="label">USAT%</div>
                    <div class="label">EV GD</div>
                </div>
                <div id="player-compare-2" class="player-compare-container">
                    <div id="activity-player-compare"><span class="loader"></span></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    $(function () {
        let player1Loaded = false;
        let player2Loaded = false;

        function checkAndCalculateDifferences() {
            if (player1Loaded && player2Loaded) {
                calculateDifferences();
            }
        }

        // Compare Players

        $(document).on('keyup', '.player-compare-search', function (e) {
            const container = $(this).next('.suggestion-box');
            const input = $(this);
            const keystroke = $(this).val();
            if (this.value.length < 3) return;
            $(container).show();
            $('#activity-sm').fadeIn("slow");
            const xhttp = new XMLHttpRequest();
            xhttp.onload = function () {
                $(container).html(this.responseText);
            };
            xhttp.open('POST', ajaxPath + 'suggestions-compare.php');
            xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhttp.send('keystroke=' + keystroke);
            $(document).mouseup(function (e) {
                // if the target of the click isn't the container nor a descendant of the container
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    container.hide();
                    input.val('');
                }
            });
        });

        // Compare Player View

        $(document).on('click', '#player-link-compare', function (e) {
            e.preventDefault();
            var player = $(this).data('link');
            var target = $(this).closest('.suggestion-box').prev('input').attr('id');

            // Remove previous event handlers
            $(document).off('click', '#close');

            function loadPlayerData(player, containerId) {
                $('#activity-player-compare').fadeIn();
                $('.suggestion-box').hide();
                const xhttp = new XMLHttpRequest();
                xhttp.onload = function () {
                    $('#' + containerId).html(this.responseText);
                    if (containerId === 'player-compare-1') {
                        player1Loaded = true;
                    } else if (containerId === 'player-compare-2') {
                        player2Loaded = true;
                    }
                    checkAndCalculateDifferences();
                };
                xhttp.onloadend = function () {
                    $('#activity-player-compare').fadeOut();
                    $('.compare-divider').addClass('active');
                };
                xhttp.open('POST', ajaxPath + 'compare-player-view.php');
                xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhttp.send('player=' + player);
            }

            if (target === 'player-search-compare-1') {
                loadPlayerData(player, 'player-compare-1');
            } else if (target === 'player-search-compare-2') {
                loadPlayerData(player, 'player-compare-2');
            }

            // Attach event handler for closing modal
            $(document).on('click', '#close', function (e) {
                $('#player-compare-1, #player-compare-2').empty();
                $('#player-compare-1, #player-compare-2').append('<div id="activity-player-compare"><span class="loader"></span></div>');
                $('.compare-divider').removeClass('active');
                player1Loaded = false;
                player2Loaded = false;
            });
        });

        function calculateDifferences() {
            var player1Stats = $('#player-compare-1 .stats-player .stat .value');
            var player2Stats = $('#player-compare-2 .stats-player .stat .value');

            player1Stats.each(function (index) {
                var player1Value = parseFloat($(this).text());
                var player2Value = parseFloat(player2Stats.eq(index).text());
                var difference = player1Value - player2Value;

                var player1Cell = $(this);
                var player2Cell = player2Stats.eq(index);

                // Determine if the stat should have decimals in the difference
                var decimalStats = ["PPG", "S%", "SAT%", "USAT%"];
                var statLabel = player1Cell.closest('.stat').find('.label').text();

                if (decimalStats.includes(statLabel)) {
                    difference = difference.toFixed(1);
                } else {
                    difference = difference.toFixed(0);
                }

                if (difference > 0) {
                    player1Cell.addClass('higher');
                    player2Cell.addClass('lower');
                } else if (difference < 0) {
                    player1Cell.addClass('lower');
                    player2Cell.addClass('higher');
                }

                player1Cell.append('<span class="difference"> (' + difference + ')</span>');
                player2Cell.append('<span class="difference"> (' + (-difference) + ')</span>');
            });
        }
    });
</script>
<?php if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include_once '../footer.php'; } ?>