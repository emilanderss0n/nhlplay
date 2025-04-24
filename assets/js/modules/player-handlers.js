import { fadeInElement, fadeOutElement, eventManager } from './utils.js';
import { fixAjaxResponseUrls } from './ajax-handler.js';

// Function to dynamically show the loader in the target container
export function showLoaderInContainer(container) {
    const loader = document.createElement('div');
    loader.id = 'activity-player';
    loader.innerHTML = '<span class="loader"></span>';
    loader.style.position = 'absolute';
    loader.style.top = '50%';
    loader.style.left = '50%';
    loader.style.margin = '0';
    loader.style.transform = 'translate(-50%, -50%)';
    loader.style.zIndex = '10000';
    container.style.position = 'relative'; // Ensure the container has relative positioning
    container.appendChild(loader);
}

// Function to remove the loader from the target container
export function removeLoaderFromContainer(container) {
    const loader = container.querySelector('#activity-player');
    if (loader) {
        container.removeChild(loader);
    }
}

// Function to initialize player handlers
export function initPlayerHandlers(elements) {
    // Use event delegation for handling player links
    eventManager.addDelegatedEventListener(document, '#player-link:not(.compare-player-item), [id^="player-link"]:not(.compare-player-item)', 'click', function (e) {
        e.preventDefault();

        fadeInElement(elements.playerActivityElement);
        const player = this.dataset.link;

        // Reset modal content and clear previous event listeners
        elements.playerModal.innerHTML = '';
        eventManager.removeEventListenersBySelector('#career-link, #season-link, #close, .overlay, #graph-toggle');

        // Store original content for toggling
        let seasonViewContent = '';
        let careerViewContent = '';
        let isCareerView = false;
        let isLoading = false;
        let clickCount = 0;
        let isModalOpen = true;
        let chartInitialized = false;
        let advancedStatsLoaded = false;

        function initPlayerModal() {
            const overlay = document.querySelector('.overlay');
            fadeInElement(overlay);

            // Initialize modal content
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                // Insert the HTML content
                elements.playerModal.innerHTML = fixAjaxResponseUrls(this.responseText);
                seasonViewContent = fixAjaxResponseUrls(this.responseText);
                setupModalEventListeners();

                // Extract and execute any script tags from the response
                const scriptContent = this.responseText.match(/<script[^>]*>([\s\S]*?)<\/script>/gi);
                if (scriptContent) {
                    scriptContent.forEach(function (script) {
                        const cleanScript = script.replace(/<\/?script[^>]*>/g, '');
                        eval(cleanScript);
                    });
                }

                // Hide player graph by default
                const playerGraph = elements.playerModal.querySelector('.player-graph');
                if (playerGraph) {
                    playerGraph.style.display = 'none';
                }
            };

            xhr.onloadend = function () {
                overlay.classList.add('open');
                document.body.classList.add('no-scroll');
                fadeOutElement(elements.playerActivityElement);
            };

            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/player-view.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('player=' + player);
        }

        initPlayerModal();

        // Function to load advanced stats if they haven't been loaded yet
        function loadAdvancedStats(playerId, callback) {
            if (advancedStatsLoaded) {
                if (callback) callback();
                return;
            }

            // Get the stats container to show the loader in
            const statsContainer = elements.playerModal.querySelector('.stats-player');
            if (statsContainer) {
                showLoaderInContainer(statsContainer);
            }

            const xhr = new XMLHttpRequest();
            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';

            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        // Update the advanced stats in the UI
                        if (response.advancedStats) {
                            const satValue = document.getElementById('sat-value');
                            const usatValue = document.getElementById('usat-value');
                            const evgdValue = document.getElementById('evgd-value');

                            if (satValue) satValue.textContent = response.advancedStats.formattedSAT || 'N/A';
                            if (usatValue) usatValue.textContent = response.advancedStats.formattedUSAT || 'N/A';
                            if (evgdValue) evgdValue.textContent = response.advancedStats.evenStrengthGoalDiff || '0';
                        }

                        advancedStatsLoaded = true;
                        if (statsContainer) {
                            removeLoaderFromContainer(statsContainer);
                        }

                        if (callback) callback();
                    } catch (error) {
                        console.error('Error parsing advanced stats:', error);
                        if (statsContainer) {
                            removeLoaderFromContainer(statsContainer);
                        }
                    }
                } else {
                    console.error('Error loading advanced stats. Status:', xhr.status);
                    if (statsContainer) {
                        removeLoaderFromContainer(statsContainer);
                    }
                }
            };

            xhr.onerror = function () {
                console.error('Network error loading advanced stats');
                if (statsContainer) {
                    removeLoaderFromContainer(statsContainer);
                }
            };

            // Get player type from toggle button's data-needs-stats attribute
            const graphToggle = elements.playerModal.querySelector('#graph-toggle');
            const isSkater = graphToggle ? graphToggle.dataset.needsStats !== 'true' : false;

            xhr.open('POST', baseUrl + '/ajax/player-advanced-stats.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('player=' + playerId + '&isSkater=' + isSkater);
        }

        function initPlayerChart() {
            if (!window.playerChartData) return false;

            const playerGraph = elements.playerModal.querySelector('.player-graph');
            if (!playerGraph) return false;

            const ctx = document.getElementById('playerStatsChart');
            if (!ctx) return false;

            const ctxContext = ctx.getContext('2d');
            const rootStyles = getComputedStyle(document.documentElement);

            // Determine chart type (radar for both skaters and goalies)
            const chartType = window.playerChartData.chartType || 'radar';
            const playerPosition = window.playerChartData.playerPosition;

            if (chartType === 'radar' && window.playerChartData.chartData) {
                const chartData = window.playerChartData.chartData;

                // Define colors based on position and category
                const categoryColors = {
                    // Skater categories
                    'scoring': 'rgba(255, 99, 132, 0.7)',
                    'playmaking': 'rgba(255, 159, 64, 0.7)',
                    'possession': 'rgba(255, 205, 86, 0.7)',
                    'transition': 'rgba(75, 192, 192, 0.7)',
                    'defense': 'rgba(54, 162, 235, 0.7)',
                    'physical': 'rgba(153, 102, 255, 0.7)',
                    'offense': 'rgba(255, 99, 132, 0.7)',
                    // Goalie categories
                    'saves': 'rgba(54, 162, 235, 0.7)',
                    'consistency': 'rgba(255, 159, 64, 0.7)',
                    'workload': 'rgba(75, 192, 192, 0.7)'
                };

                // Create datasets with colors by category
                const playerData = {
                    label: 'Player',
                    data: chartData.values,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: rootStyles.getPropertyValue('--main-link-color').trim(),
                    borderWidth: 2,
                    pointBackgroundColor: chartData.categories.map(function (cat) {
                        return categoryColors[cat] || 'rgb(54, 162, 235)';
                    }),
                    pointBorderColor: rootStyles.getPropertyValue('--main-bg-color').trim(),
                    pointHoverBorderColor: rootStyles.getPropertyValue('--main-bg-color').trim(),
                    pointRadius: 6,
                    fill: true
                };

                const benchmarkData = {
                    label: 'League Average',
                    data: chartData.benchmarks,
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderColor: 'rgba(128, 128, 128, 0.7)',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    pointBackgroundColor: 'rgba(128, 128, 128, 0.7)',
                    pointBorderColor: '#fff',
                    pointRadius: 0,
                    fill: false
                };

                const eliteData = {
                    label: 'Elite Level',
                    data: chartData.elite,
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderColor: 'rgba(255, 102, 0, 0.8)',
                    borderWidth: 1,
                    borderDash: [2, 2],
                    pointBackgroundColor: 'rgba(255, 102, 0, 0.8)',
                    pointBorderColor: '#fff',
                    pointRadius: 0,
                    fill: false
                };

                new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: chartData.metrics,
                        datasets: [benchmarkData, eliteData, playerData]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                angleLines: {
                                    color: 'rgba(200, 200, 200, 0.2)'
                                },
                                grid: {
                                    color: 'rgba(200, 200, 200, 0.2)'
                                },
                                pointLabels: {
                                    color: rootStyles.getPropertyValue('--paragraph-color').trim(),
                                    font: {
                                        size: 11
                                    }
                                },
                                beginAtZero: true,
                                max: 100,
                                min: 0,
                                ticks: {
                                    stepSize: 20,
                                    backdropColor: 'transparent',
                                    color: 'rgba(200, 200, 200, 0.7)',
                                    showLabelBackdrop: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: rootStyles.getPropertyValue('--paragraph-color').trim()
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const datasetLabel = context.dataset.label;
                                        const value = context.raw.toFixed(1);
                                        const tooltip = chartData.tooltips[context.dataIndex];

                                        if (datasetLabel === 'Player') {
                                            return [`${datasetLabel}: ${value}%`, tooltip];
                                        } else {
                                            return `${datasetLabel}: ${value}%`;
                                        }
                                    },
                                    title: function (context) {
                                        const category = chartData.categories[context[0].dataIndex];
                                        const metric = chartData.metrics[context[0].dataIndex];
                                        return `${category.toUpperCase()}: ${metric}`;
                                    }
                                }
                            }
                        }
                    }
                });
                return true;
            } else if (window.playerChartData.barData) {
                // Original bar chart for goalies or fallback
                const offenseCount = window.playerChartData.barData.categories.offense || 0;
                const defenseCount = window.playerChartData.barData.categories.defense || 0;

                const config = {
                    type: 'bar',
                    data: {
                        labels: window.playerChartData.barData.labels,
                        datasets: [{
                            data: window.playerChartData.barData.values,
                            backgroundColor: function (context) {
                                const index = context.dataIndex;
                                // Offensive metrics in blue, defensive in red
                                return index < offenseCount ?
                                    'rgba(54, 162, 235, 0.7)' :
                                    'rgba(255, 99, 132, 0.7)';
                            },
                            borderWidth: 1,
                            borderRadius: 4,
                            borderColor: function (context) {
                                const index = context.dataIndex;
                                return index < offenseCount ?
                                    'rgb(54, 162, 235)' :
                                    'rgb(255, 99, 132)';
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    color: 'rgba(200, 200, 200, 0.2)'
                                },
                                ticks: {
                                    color: rootStyles.getPropertyValue('--paragraph-color').trim(),
                                    callback: function (value) {
                                        return value + '%';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: rootStyles.getPropertyValue('--paragraph-color').trim()
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const value = context.raw.toFixed(1);
                                        const tooltip = window.playerChartData.barData.tooltips[context.dataIndex];
                                        return `${value}% - ${tooltip}`;
                                    }
                                }
                            }
                        }
                    }
                };

                new Chart(ctx, config);
            }

            return true;
        }

        // Handle career view toggle
        function handleCareerClick(e) {
            if (isLoading) return;
            isLoading = true;

            // Get the stats-player container that needs to be updated
            const statsPlayerContainer = elements.playerModal.querySelector('.stats-player');
            if (!statsPlayerContainer) {
                console.error('Could not find .stats-player container');
                isLoading = false;
                return;
            }

            // Show loader in the stats container
            showLoaderInContainer(statsPlayerContainer);

            // Get the player ID from the data-link attribute, or fall back to the global player variable
            const playerId = this.dataset.link || player;
            const headerText = elements.playerModal.querySelector('#season-career');

            // Toggle between season and career view
            if (!isCareerView) {
                // Switch to career view
                if (!careerViewContent) {
                    // If we haven't loaded career view yet, fetch it
                    const xhr = new XMLHttpRequest();
                    const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';

                    xhr.onload = function () {
                        const responseContent = fixAjaxResponseUrls(this.responseText);

                        // Store the content for future use
                        careerViewContent = responseContent;

                        // Remove loader and replace the entire .stats-player content
                        removeLoaderFromContainer(statsPlayerContainer);
                        statsPlayerContainer.innerHTML = responseContent;

                        // Update header text
                        if (headerText) {
                            headerText.textContent = 'Career Stats';
                        }

                        // Make sure event listeners are set up for the new content
                        setupModalEventListeners();

                        // Update state
                        isCareerView = true;
                        isLoading = false;
                    };

                    xhr.onerror = function () {
                        console.error('Error loading career data');
                        removeLoaderFromContainer(statsPlayerContainer);
                        isLoading = false;
                    };

                    xhr.open('POST', baseUrl + '/ajax/player-view-career.php');
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.send('player=' + playerId);
                } else {
                    // We already have the career view content, just swap it in
                    removeLoaderFromContainer(statsPlayerContainer);
                    statsPlayerContainer.innerHTML = careerViewContent;

                    // Update header text
                    if (headerText) {
                        headerText.textContent = 'Career Stats';
                    }

                    // Make sure event listeners are set up
                    setupModalEventListeners();

                    // Update state
                    isCareerView = true;
                    isLoading = false;
                }

                // Update button text
                this.innerHTML = 'Season';
            } else {
                // We need to get the season view content (should be in seasonViewContent)
                if (seasonViewContent) {
                    // Extract just the .stats-player content from the seasonViewContent
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = seasonViewContent;
                    const seasonStatsPlayer = tempDiv.querySelector('.stats-player');

                    removeLoaderFromContainer(statsPlayerContainer);
                    if (seasonStatsPlayer) {
                        // Replace the entire .stats-player content
                        statsPlayerContainer.innerHTML = seasonStatsPlayer.innerHTML;
                    } else {
                        // Fallback if we can't extract it properly
                        statsPlayerContainer.innerHTML = seasonViewContent;
                    }

                    // Update header text
                    if (headerText) {
                        headerText.textContent = 'Season Stats';
                    }

                    // Reset chart if it was initialized
                    const playerGraph = elements.playerModal.querySelector('.player-graph');
                    if (playerGraph && chartInitialized) {
                        playerGraph.style.display = 'none';
                    }

                    // Make sure event listeners are set up
                    setupModalEventListeners();

                    // Update state
                    isCareerView = false;
                    isLoading = false;

                    // Reset advanced stats loaded flag
                    advancedStatsLoaded = false;
                } else {
                    console.error('Season view content not available');
                    removeLoaderFromContainer(statsPlayerContainer);
                    isLoading = false;
                }

                // Update button text
                this.innerHTML = 'Career';
            }
        }

        function setupModalEventListeners() {
            // Clean up any previous handlers to avoid duplicates
            eventManager.removeEventListenersBySelector('#career-link, #season-link, #graph-toggle');

            // Add graph toggle handler
            const graphToggle = elements.playerModal.querySelector('#graph-toggle');
            if (graphToggle) {
                eventManager.addEventListener(graphToggle, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling

                    // Prevent action if modal is closed or loading
                    if (!isModalOpen || isLoading) return;

                    const playerGraph = elements.playerModal.querySelector('.player-graph');
                    if (!playerGraph) return;

                    // Toggle graph visibility
                    if (playerGraph.style.display === 'none') {
                        const playerId = this.dataset.player;
                        const needsStats = this.dataset.needsStats === 'true';

                        // Show loader in the graph container instead of global activity indicator
                        playerGraph.style.display = 'block';
                        showLoaderInContainer(playerGraph);
                        this.innerHTML = "<i class='bi bi-cloud-download'></i>Loading...";

                        // Set loading state
                        isLoading = true;

                        // If we need to load advanced stats first, do that before loading the radar data
                        const loadRadarData = function () {
                            // Fetch the radar data via AJAX
                            const xhr = new XMLHttpRequest();
                            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
                            xhr.open('POST', baseUrl + '/ajax/player-radar-stats.php');
                            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    try {
                                        // Parse the response and store it
                                        window.playerChartData = JSON.parse(xhr.responseText);

                                        // Remove loader and reset the canvas
                                        removeLoaderFromContainer(playerGraph);
                                        playerGraph.innerHTML = '<canvas id="playerStatsChart"></canvas>';

                                        // Initialize chart
                                        chartInitialized = initPlayerChart();

                                        // Update the button text
                                        graphToggle.innerHTML = "<i class='bi bi-x'></i>Radar";
                                    } catch (error) {
                                        console.error('Error parsing radar data:', error);
                                        playerGraph.innerHTML = '<div class="error">Failed to load radar data</div>';
                                        graphToggle.innerHTML = "Radar";
                                    }
                                } else {
                                    console.error('Error loading radar data. Status:', xhr.status);
                                    playerGraph.innerHTML = '<div class="error">Failed to load radar data</div>';
                                    graphToggle.innerHTML = "Radar";
                                }

                                // Reset loading state
                                isLoading = false;
                            };

                            xhr.onerror = function () {
                                console.error('Network error loading radar data');
                                removeLoaderFromContainer(playerGraph);
                                playerGraph.innerHTML = '<div class="error">Network error</div>';
                                graphToggle.innerHTML = "Radar";
                                isLoading = false;
                            };

                            // Get the player data from the button's data attribute
                            const playerData = graphToggle.dataset.playerData;

                            // Send the request with both player ID and data
                            xhr.send('player=' + playerId + '&playerData=' + encodeURIComponent(playerData));
                        };

                        // Load advanced stats if needed, then load radar data
                        if (needsStats && !advancedStatsLoaded) {
                            loadAdvancedStats(playerId, loadRadarData);
                        } else {
                            loadRadarData();
                        }
                    } else {
                        // Hide the graph
                        playerGraph.style.display = 'none';
                        this.innerHTML = "Radar";
                    }
                });
            }

            // Add career link handler with container-specific loader
            const careerLink = elements.playerModal.querySelector('#career-link');
            if (careerLink) {
                eventManager.addEventListener(careerLink, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling

                    // Prevent action if modal is closed or loading
                    if (!isModalOpen || isLoading) return;

                    // Track click count to prevent rapid clicking
                    clickCount++;
                    const currentClick = clickCount;

                    // Store a reference to 'this' for the setTimeout callback
                    const self = this;

                    // Debounce multiple clicks
                    setTimeout(function () {
                        if (currentClick !== clickCount) return; // Skip if not the latest click

                        if (isLoading) return;
                        isLoading = true;

                        // Get stats container and show loader in it
                        const statsPlayerContainer = elements.playerModal.querySelector('.stats-player');
                        if (!statsPlayerContainer) {
                            console.error('Could not find .stats-player container');
                            isLoading = false;
                            return;
                        }

                        // Show loader in the stats container
                        showLoaderInContainer(statsPlayerContainer);

                        const playerId = self.dataset.link || player;
                        const headerText = elements.playerModal.querySelector('#season-career');

                        if (!isCareerView) {
                            if (!careerViewContent) {
                                const xhr = new XMLHttpRequest();
                                const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';

                                xhr.onload = function () {
                                    const responseContent = fixAjaxResponseUrls(this.responseText);
                                    careerViewContent = responseContent;
                                    removeLoaderFromContainer(statsPlayerContainer);
                                    statsPlayerContainer.innerHTML = responseContent;

                                    if (headerText) {
                                        headerText.textContent = 'Career Stats';
                                    }

                                    setupModalEventListeners();
                                    isCareerView = true;
                                    isLoading = false;
                                };

                                xhr.onerror = function () {
                                    console.error('Error loading career data');
                                    removeLoaderFromContainer(statsPlayerContainer);
                                    isLoading = false;
                                };

                                xhr.open('POST', baseUrl + '/ajax/player-view-career.php');
                                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                                xhr.send('player=' + playerId);
                            } else {
                                removeLoaderFromContainer(statsPlayerContainer);
                                statsPlayerContainer.innerHTML = careerViewContent;

                                if (headerText) {
                                    headerText.textContent = 'Career Stats';
                                }

                                setupModalEventListeners();
                                isCareerView = true;
                                isLoading = false;
                            }

                            self.innerHTML = 'Season';
                        } else {
                            if (seasonViewContent) {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = seasonViewContent;
                                const seasonStatsPlayer = tempDiv.querySelector('.stats-player');

                                removeLoaderFromContainer(statsPlayerContainer);
                                if (seasonStatsPlayer) {
                                    statsPlayerContainer.innerHTML = seasonStatsPlayer.innerHTML;
                                } else {
                                    statsPlayerContainer.innerHTML = seasonViewContent;
                                }

                                if (headerText) {
                                    headerText.textContent = 'Season Stats';
                                }

                                const playerGraph = elements.playerModal.querySelector('.player-graph');
                                if (playerGraph && chartInitialized) {
                                    playerGraph.style.display = 'none';
                                }

                                setupModalEventListeners();
                                isCareerView = false;
                                isLoading = false;
                                advancedStatsLoaded = false;
                            } else {
                                console.error('Season view content not available');
                                removeLoaderFromContainer(statsPlayerContainer);
                                isLoading = false;
                            }

                            self.innerHTML = 'Career';
                        }
                    }, 50);
                });
            }

            // Close button handler
            const closeButton = elements.playerModal.querySelector('#close');
            if (closeButton) {
                eventManager.addEventListener(closeButton, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling

                    isModalOpen = false; // Mark modal as closed

                    const overlay = document.querySelector('.overlay');
                    overlay.classList.remove('open');

                    setTimeout(function () {
                        overlay.style.display = 'none';
                        elements.playerModal.innerHTML = '';
                        document.body.classList.remove('no-scroll');
                        eventManager.removeEventListenersBySelector('#career-link, #season-link, #close, .overlay, #graph-toggle');
                    }, 300);
                });
            }

            // Overlay click handler
            const overlay = document.querySelector('.overlay');
            eventManager.addEventListener(overlay, 'click', function (e) {
                if (!e.target.closest('#player-modal')) {
                    isModalOpen = false; // Mark modal as closed

                    overlay.classList.remove('open');

                    setTimeout(function () {
                        overlay.style.display = 'none';
                        elements.playerModal.innerHTML = '';
                        document.body.classList.remove('no-scroll');
                        eventManager.removeEventListenersBySelector('#career-link, #season-link, #close, .overlay, #graph-toggle');
                    }, 300);
                }
            });
        }
    });
}
