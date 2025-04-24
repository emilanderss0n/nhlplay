export function initDraftRankingsTable() {
    const draftTable = document.getElementById('draftRankings');
    if (draftTable && !draftTable.classList.contains('jsDataTable-table')) {
        new jsdatatables.JSDataTable('#draftRankings', {
            paging: false,
            searchable: true,
        });
    }
}