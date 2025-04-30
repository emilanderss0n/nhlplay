export function initStandingsHandlers() {
    // Standings: Conference
    document.addEventListener('click', function (e) {
        if (e.target.id === 'standings-conference') {
            e.preventDefault();

            document.querySelectorAll('.standings-filter .btn').forEach(btn => {
                btn.classList.remove('active');
            });

            e.target.classList.add('active');

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const standingsHome = document.getElementById('standings-home');
                if (standingsHome) {
                    standingsHome.innerHTML = xhr.responseText;
                    standingsHome.classList.add('page-ani');

                    standingsHome.addEventListener('animationend', function () {
                        standingsHome.classList.remove('page-ani');
                    }, { once: true });
                }
                let dt = new jsdatatables.JSDataTable('.conferenceTable', {
                    paging: false,
                    searchable: true,
                });
            };

            xhr.open('GET', 'ajax/standings-conference.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send();
        }
    });

    // Standings: Divisions
    document.addEventListener('click', function (e) {
        if (e.target.id === 'standings-divisions') {
            e.preventDefault();

            document.querySelectorAll('.standings-filter .btn').forEach(btn => {
                btn.classList.remove('active');
            });

            e.target.classList.add('active');

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const standingsHome = document.getElementById('standings-home');
                if (standingsHome) {
                    standingsHome.innerHTML = xhr.responseText;
                    standingsHome.classList.add('page-ani');

                    standingsHome.addEventListener('animationend', function () {
                        standingsHome.classList.remove('page-ani');
                    }, { once: true });
                }
                let dt = new jsdatatables.JSDataTable('.divisionTable', {
                    paging: false,
                    searchable: true,
                });
            };

            xhr.open('GET', 'ajax/standings-divisions.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send();
        }
    });

    // Standings: League
    document.addEventListener('click', function (e) {
        if (e.target.id === 'standings-league') {
            e.preventDefault();

            document.querySelectorAll('.standings-filter .btn').forEach(btn => {
                btn.classList.remove('active');
            });

            e.target.classList.add('active');

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const standingsHome = document.getElementById('standings-home');
                if (standingsHome) {
                    standingsHome.innerHTML = xhr.responseText;
                    standingsHome.classList.add('page-ani');

                    standingsHome.addEventListener('animationend', function () {
                        standingsHome.classList.remove('page-ani');
                    }, { once: true });
                }
                let dt = new jsdatatables.JSDataTable('#leagueTable', {
                    paging: false,
                    searchable: true,
                });
            };

            xhr.open('GET', 'ajax/standings-league.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send();
        }
    });
}

export function initPlayoffSeriesHandlers() {
    const modal = document.getElementById('seriesModal');
    const closeBtn = modal?.querySelector('.close');
    const seriesContent = document.getElementById('seriesContent');

    if (!modal || !closeBtn || !seriesContent) return;

    // Close modal when clicking the X button
    closeBtn.onclick = function() {
        modal.classList.add('closing');

        // Wait for transition to end before actually closing it
        modal.addEventListener('transitionend', () => {
            requestAnimationFrame(() => {
                modal.close();
                modal.classList.remove('closing');
            });
        }, { once: true });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.classList.add('closing');

            // Wait for transition to end before actually closing it
            modal.addEventListener('transitionend', () => {
                requestAnimationFrame(() => {
                    modal.close();
                    modal.classList.remove('closing');
                });
            }, { once: true });
        }
    }

    // Add click handlers to playoff games
    document.querySelectorAll('.playoffs-bracket .game[data-series-letter]').forEach(game => {
        game.addEventListener('click', async function() {
            const seriesLetter = this.dataset.seriesLetter;
            const season = this.dataset.season;

            try {
                const response = await fetch(`ajax/playoff-series.php?season=${season}&series=${seriesLetter}`);
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.text();
                seriesContent.innerHTML = data;
                modal.showModal();
            } catch (error) {
                console.error('Error fetching series data:', error);
            }
        });
    });
}
