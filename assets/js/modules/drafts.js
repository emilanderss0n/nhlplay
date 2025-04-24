export function initDraftPage() {
    const draftTable = document.getElementById('draftRankings');
    if (draftTable && !draftTable.classList.contains('jsDataTable-table')) {
        new jsdatatables.JSDataTable('#draftRankings', {
            paging: false,
            searchable: true,
        });
    }

    document.addEventListener('click', function (event) {
        const target = event.target.closest('#show-previous-draft');
        if (target) {
            const draftYear = target.dataset.draftYear;
            const previousDraft = document.querySelector('.previous-draft');
            if (previousDraft) {
                const xhr = new XMLHttpRequest();
    
                xhr.onload = function () {
                    previousDraft.innerHTML = this.responseText;
                };
                xhr.onloadend = function () {
                    previousDraft.classList.toggle('show');
                };
    
                const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
                xhr.open('POST', baseUrl + '/ajax/draft-previous.php');
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('draftYear=' + draftYear);
            }
        }
    });
}