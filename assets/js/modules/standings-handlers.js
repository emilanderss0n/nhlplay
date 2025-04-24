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
