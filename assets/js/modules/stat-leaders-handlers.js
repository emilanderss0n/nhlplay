import { fixAjaxResponseUrls } from './ajax-handler.js';

export function initStatLeadersHandlers() {
    // Handle stat-select option clicks
    document.addEventListener('click', function (e) {
        if (e.target.closest('.stat-select .option')) {
            e.preventDefault();
            const option = e.target.closest('.stat-select .option');
            const type = option.dataset.type;
            const list = option.dataset.list;
            const load = option.dataset.load;

            // Update active tab
            document.querySelectorAll(`.stat-select .option.${list}`).forEach(el => {
                el.classList.remove('active');
            });
            option.classList.add('active');

            // Hide all stat holders for this category
            document.querySelectorAll(`.stat-holder.${list}`).forEach(el => {
                el.style.display = 'none';
            });

            // Show activity loader
            const activityContent = document.querySelector(`.activity-content.${list}`);
            if (activityContent) {
                activityContent.style.display = 'block';
            }

            if (load) {
                // Load content on demand
                const holder = document.querySelector(`.stat-${type}.${list}`);
                if (!holder) return;

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/stat-leaders-demand-load.php');
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        // Fix any image paths in the response
                        holder.innerHTML = fixAjaxResponseUrls(xhr.responseText);
                        holder.style.display = 'block';

                        // Hide activity indicator
                        if (activityContent) {
                            activityContent.style.display = 'none';
                        }
                    } else {
                        holder.innerHTML = '<div class="error">Failed to load data</div>';
                    }
                };

                xhr.onerror = function () {
                    holder.innerHTML = '<div class="error">Error loading data</div>';
                    if (activityContent) {
                        activityContent.style.display = 'none';
                    }
                };

                // Get season from window or default to current season
                const season = window.season || '20242025';
                xhr.send(`type=${list}&category=${type}&season=${season}&loadOnDemand=${load}`);
            } else {
                // Content already loaded, just show it
                document.querySelectorAll(`.stat-${type}.${list}`).forEach(el => {
                    el.style.display = 'block';
                });

                if (activityContent) {
                    activityContent.style.display = 'none';
                }
            }
        }
    });
}

export function initStatLeadersTableHandler() {
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.season-select-link');
        if (link) {
            e.preventDefault();
            const table = document.getElementById('playerStatsTable');
            const season = link.getAttribute('data-season');
            
            fetch('ajax/stat-leaders-table.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'season=' + encodeURIComponent(season)
            })
            .then(res => res.text())
            .then(html => {
                const tableContainer = table.parentNode;
                tableContainer.innerHTML = html;
            });
        }
    });
}