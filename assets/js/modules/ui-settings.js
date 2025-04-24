export function initUISettings() {
    // Scores visibility toggle
    document.addEventListener('click', function (e) {
        if (e.target.closest('.see-score-check .switch input')) {
            const games = document.querySelectorAll('.no-team-selected .game');
            games.forEach(game => game.classList.toggle('scores'));

            const seeScores = document.querySelector('.no-team-selected .game')?.classList.contains('scores') ? 'yes' : 'no';
            localStorage.setItem('seeScores', seeScores);
        }
    });

    // Initialize scores visibility based on localStorage
    if (localStorage.getItem('seeScores') === 'yes') {
        const switchInput = document.querySelector('.switch input');
        if (switchInput) switchInput.checked = true;

        const games = document.querySelectorAll('.no-team-selected .game');
        games.forEach(game => game.classList.add('scores'));
    }
}
