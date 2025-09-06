import { toggleHeightTransition } from './utils.js';

export function initMenuHandlers() {
    // Handle clicks outside dropdown menus
    document.addEventListener('click', function (e) {
        // Main menu links dropdown
        if (!e.target.closest('.menu-links')) {
            const menuLinksDropdown = document.querySelector('#main-menu .menu-links .dropdown');
            if (menuLinksDropdown) menuLinksDropdown.checked = false;
        }

        // Teams dropdown
        if (!e.target.closest('.menu-teams')) {
            const menuTeamsDropdown = document.querySelector('#main-menu .menu-teams .dropdown');
            if (menuTeamsDropdown) menuTeamsDropdown.checked = false;
        }
    });

    // Close menus when links clicked
    document.querySelectorAll('.menu-links .section-dropdown a').forEach(link => {
        link.addEventListener('click', function () {
            const dropdown = document.querySelector('#main-menu .menu-links .dropdown');
            if (dropdown) dropdown.checked = false;
        });
    });

    document.querySelectorAll('.menu-teams .section-dropdown a').forEach(link => {
        link.addEventListener('click', function () {
            const dropdown = document.querySelector('#main-menu .menu-teams .dropdown');
            if (dropdown) dropdown.checked = false;
        });
    });

    // Mobile menu toggle
    const navMobileCheckbox = document.querySelector('#nav-mobile input[type="checkbox"]');
    if (navMobileCheckbox) {
        navMobileCheckbox.addEventListener('change', function () {
            const mainMenu = document.getElementById('main-menu');
            mainMenu.classList.toggle('open', this.checked);
            document.body.classList.toggle('no-scroll', this.checked);
            document.getElementById('nav-mobile').classList.toggle('open', this.checked);
        });
    }

    // Close mobile menu when clicking links: close UI but let browser handle navigation
    document.addEventListener('click', function (e) {
        const mainMenu = document.getElementById('main-menu');
        const anchor = e.target.closest('a');
        if (mainMenu && mainMenu.classList.contains('open') && anchor && mainMenu.contains(anchor)) {
            // Respect modifier keys and non-primary buttons (user may want new tab / context menu)
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            // Close the menu UI (do not prevent default navigation)
            mainMenu.classList.remove('open');
            document.body.classList.remove('no-scroll');
            const navMobile = document.getElementById('nav-mobile');
            if (navMobile) navMobile.classList.remove('open');
            const navMobileCheckbox = document.querySelector('#nav-mobile input[type="checkbox"]');
            if (navMobileCheckbox) navMobileCheckbox.checked = false;
        }
    });

    // Injuries toggle
    document.addEventListener('click', function (e) {
        if (e.target.id === 'injuriesLink') {
            e.preventDefault();
            toggleHeightTransition('injuriesLink', 'injuriesAnchor', '.transition-zoom-in');
        }
    });

    // Player search
    document.addEventListener('click', function (e) {
        if (e.target.id === 'player-search') {
            const mainMenu = document.getElementById('main-menu');
            if (mainMenu) {
                mainMenu.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }
    });

    // Player search with suggestions
    document.addEventListener('keyup', function (e) {
        if (e.target.id === 'player-search') {
            const searchInput = e.target;
            const container = searchInput.closest('.suggestion-input').querySelector('.suggestion-box');
            const activitySmElement = document.getElementById('activity-sm');
            const keystroke = searchInput.value;

            if (keystroke.length < 3) return;

            container.style.display = 'block';
            activitySmElement.style.display = 'block';
            activitySmElement.style.opacity = 1;

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                container.innerHTML = this.responseText;
                fadeOutElement(activitySmElement);
            };

            // Fix the URL for suggestions to use the base URL
            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/suggestions.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('keystroke=' + keystroke);

            document.addEventListener('mouseup', function handleClickOutside(evt) {
                if (!container.contains(evt.target) && !searchInput.contains(evt.target)) {
                    container.style.display = 'none';
                    searchInput.value = '';
                    document.removeEventListener('mouseup', handleClickOutside);
                }
            });

            // Close suggestion box when a search result is clicked (desktop)
            container.addEventListener('click', function(evt) {
                if (evt.target.closest('#player-link')) {
                    container.style.display = 'none';
                    searchInput.value = '';
                }
            });
        }
    });

    // Mobile search toggle handler
    const navMobileSearch = document.getElementById('nav-mobile-search');
    if (navMobileSearch) {
        navMobileSearch.addEventListener('click', function (e) {
            e.preventDefault();
            const mobileSearch = document.getElementById('mobile-search');
            const searchInput = document.getElementById('player-search-mobile');

            if (mobileSearch) {
                mobileSearch.classList.toggle('active');
                if (mobileSearch.classList.contains('active')) {
                    searchInput?.focus();
                } else {
                    if (searchInput) {
                        searchInput.value = '';
                        const suggestionBox = mobileSearch.querySelector('.suggestion-box');
                        if (suggestionBox) {
                            suggestionBox.style.display = 'none';
                        }
                    }
                }
            }
        });
    }

    // Player search mobile
    document.addEventListener('click', function (e) {
        if (e.target.id === 'player-search-mobile') {
            const mainMenu = document.getElementById('main-menu');
            if (mainMenu) {
                mainMenu.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }
    });

    // Player search with suggestions for mobile
    document.addEventListener('keyup', function (e) {
        if (e.target.id === 'player-search-mobile') {
            const searchInput = e.target;
            const container = searchInput.closest('.suggestion-input').querySelector('.suggestion-box');
            const activitySmElement = document.getElementById('activity-sm');
            const keystroke = searchInput.value;

            if (keystroke.length < 3) return;

            container.style.display = 'block';
            activitySmElement.style.display = 'block';
            activitySmElement.style.opacity = 1;

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                container.innerHTML = this.responseText;
                fadeOutElement(activitySmElement);
            };

            // Fix the URL for suggestions to use the base URL
            const baseUrl = window.location.pathname.startsWith('/nhl') ? '/nhl' : '';
            xhr.open('POST', baseUrl + '/ajax/suggestions.php');
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('keystroke=' + keystroke);

            document.addEventListener('mouseup', function handleClickOutside(evt) {
                if (!container.contains(evt.target) && !searchInput.contains(evt.target)) {
                    container.style.display = 'none';
                    searchInput.value = '';
                    document.removeEventListener('mouseup', handleClickOutside);
                }
            });

            // Close suggestion input when a search result is clicked (mobile)
            container.addEventListener('click', function(evt) {
                if (evt.target.closest('#player-link')) {
                    const mobileSearch = document.getElementById('mobile-search');
                    if (mobileSearch) {
                        mobileSearch.classList.remove('active');
                    }
                    container.style.display = 'none';
                    searchInput.value = '';
                }
            });
        }
    });
}

function fadeOutElement(element) {
    element.style.opacity = 0;
    setTimeout(() => {
        element.style.display = 'none';
    }, 500);
}