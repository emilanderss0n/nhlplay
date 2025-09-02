/**
 * YouTube Videos Module
 * Handles displaying YouTube videos with lazy loading
 */

export function initYouTubeVideos() {
    // Initialize all YouTube video containers on the page
    const videoContainers = document.querySelectorAll('.youtube-videos');
    videoContainers.forEach(container => {
        initYouTubeContainer(container);
    });
}

/**
 * Initialize a single YouTube video container
 * @param {HTMLElement} container - The video container element
 */
function initYouTubeContainer(container) {
    if (!container) return;
    
    // Create an Intersection Observer to detect when youtube-videos comes into view
    const youtubeObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // If the element is in view
            if (entry.isIntersecting) {
                // Load YouTube videos
                loadYouTubeVideos(entry.target);
                // Stop observing after loading once
                observer.unobserve(entry.target);
            }
        });
    }, {
        // Start loading when element is 200px from viewport
        rootMargin: '200px',
        threshold: 0.1
    });
    
    // Start observing the youtube-videos element
    youtubeObserver.observe(container);
    
    // If videos are already in the DOM (not lazy loaded), render them
    if (typeof window.youtubeVideosData !== 'undefined' && window.youtubeVideosData.items) {
        renderYouTubeVideos(window.youtubeVideosData.items, container);
        // Clear the global variable to prevent reuse
        delete window.youtubeVideosData;
    }
    
    /**
     * Load YouTube videos for a specific container
     * @param {HTMLElement} container - The video container element
     */
    function loadYouTubeVideos(container) {
        if (!container) return;
        
        // Show loading indicator
        const loadElement = container.querySelector('#activity');
        if (loadElement) {
            loadElement.style.display = 'flex';
        }
        
        // Get maxResults from data attribute or default to 12
        const maxResults = container.dataset.maxResults || 12;
        
        // Get seasonBreak from data attribute or detect from global variable
        const seasonBreak = container.dataset.seasonBreak || 
                           (typeof window.seasonBreak !== 'undefined' ? window.seasonBreak : 'false');
        
        // Determine the correct base URL for the environment
        let baseUrl;
        if (window.location.hostname === 'localhost') {
            baseUrl = '/nhl/ajax/';
        } else {
            baseUrl = '/ajax/';
        }
        
        // Build the final URL
        const ajaxUrl = window.location.origin + baseUrl + 'youtube-videos.php';
        
        // Fetch YouTube videos from our AJAX endpoint
        fetch(`${ajaxUrl}?maxResults=${maxResults}&seasonBreak=${seasonBreak}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(resp => {
            // Normalize response: support envelope { success, videos }
            let videos = [];
            if (resp && Array.isArray(resp.videos)) {
                videos = resp.videos;
                // Log cache status for debugging
                if (resp.cache_status) {
                    console.log(`YouTube Videos: Cache ${resp.cache_status} (${resp.cache_file || 'unknown'})`);
                }
            } else if (resp && resp.success && Array.isArray(resp.data)) {
                videos = resp.data;
            }
            
            // Check if we got videos
            if (videos && Array.isArray(videos) && videos.length > 0) {
                renderYouTubeVideos(videos, container);
            } else {
                // Show error message if no videos found
                container.innerHTML = `
                    <div class="alert info">
                        <div class="alert-content">
                            <i class="bi bi-info-circle"></i>
                            <span>No NHL videos available at the moment. Please try again later or <a href="https://www.youtube.com/results?search_query=NHL+hockey" target="_blank" rel="noopener noreferrer">search YouTube directly</a>.</span>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading YouTube videos:', error);
            container.innerHTML = `
                <div class="alert danger">
                    <div class="alert-content">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>There was an error loading YouTube videos. Please try again later.</span>
                    </div>
                </div>
            `;
        })
        .finally(() => {
            // Hide loading indicator
            if (loadElement) {
                loadElement.style.display = 'none';
            }
        });
    }
}

function renderYouTubeVideos(videos, container) {
    // Create modal if it doesn't exist
    createVideoModal();
    
    container.innerHTML = ''; // Clear any existing content

    // Build Swiper structure
    const swiperEl = document.createElement('div');
    swiperEl.className = 'swiper youtube-swiper';

    const wrapper = document.createElement('div');
    wrapper.className = 'swiper-wrapper';
    swiperEl.appendChild(wrapper);

    // Navigation & pagination elements (scoped inside container)
    const prevBtn = document.createElement('button');
    prevBtn.className = 'youtube-swiper-prev swiper-button-prev';
    prevBtn.setAttribute('aria-label', 'Previous videos');

    const nextBtn = document.createElement('button');
    nextBtn.className = 'youtube-swiper-next swiper-button-next';
    nextBtn.setAttribute('aria-label', 'Next videos');

    const pagination = document.createElement('div');
    pagination.className = 'youtube-swiper-pagination swiper-pagination';

    // Create slides
    videos.forEach(item => {
        if (!item.snippet || !item.snippet.resourceId) {
            return; // Skip invalid items
        }

        const videoId = item.snippet.resourceId.videoId;
        const title = stripEmoji(item.snippet.title || '');
        const thumbnail = item.snippet.thumbnails?.standard?.url || item.snippet.thumbnails?.high?.url;
        const channelTitle = item.snippet.channelTitle || '';

        const slideEl = document.createElement('div');
        slideEl.className = 'swiper-slide';

        const videoElement = createVideoElement(videoId, title, thumbnail, channelTitle);
        // Keep existing card markup inside the slide
        slideEl.appendChild(videoElement);

        wrapper.appendChild(slideEl);
    });

    // Append navigation/pagination and swiper to container
    swiperEl.appendChild(pagination);
    swiperEl.appendChild(prevBtn);
    swiperEl.appendChild(nextBtn);
    container.appendChild(swiperEl);

    // Initialize Swiper if available. If not yet loaded, retry on window load and poll briefly.
    function tryInitSwiper() {
        if (typeof Swiper === 'undefined') return false;

        try {
            new Swiper(swiperEl, {
                slidesPerView: 1.1,
                spaceBetween: 12,
                centeredSlides: false,
                loop: false,
                lazy: {
                    loadPrevNext: true,
                },
                keyboard: {
                    enabled: true,
                },
                breakpoints: {
                    640: { slidesPerView: 2.05, spaceBetween: 12 },
                    900: { slidesPerView: 3.05, spaceBetween: 14 }
                },
                pagination: {
                    el: pagination,
                    clickable: true,
                },
                navigation: {
                    nextEl: nextBtn,
                    prevEl: prevBtn,
                }
            });
            return true;
        } catch (e) {
            console.warn('Failed to initialize Swiper for YouTube videos', e);
            return false;
        }
    }

    if (!tryInitSwiper()) {
        // If Swiper isn't available yet, wait for the window load event (external deferred scripts will have run)
        window.addEventListener('load', () => {
            if (!tryInitSwiper()) {
                // As a final fallback, poll a few times in case of timing edge-cases
                let attempts = 0;
                const maxAttempts = 6; // ~3s
                const interval = setInterval(() => {
                    attempts += 1;
                    if (tryInitSwiper() || attempts >= maxAttempts) {
                        clearInterval(interval);
                    }
                }, 500);
            }
        });
    }
}

function createVideoElement(videoId, title, thumbnail, channelTitle) {
    const videoDiv = document.createElement('div');
    videoDiv.className = 'youtube-video-card';
    videoDiv.tabIndex = 0;
    
    videoDiv.innerHTML = `
        <div class="video-thumbnail-container">
            <img src="${thumbnail}" alt="${title}" class="video-thumbnail" loading="lazy">
        </div>
        <div class="video-title">${title}</div>
    `;
    
    videoDiv.addEventListener('click', () => {
        openVideoModal(videoId, channelTitle);
    });
    
    return videoDiv;
}

function createVideoModal() {
    // Check if modal already exists
    if (document.getElementById('youtube-video-modal')) {
        return;
    }

    // Add basic dialog styles if not already added
    if (!document.getElementById('youtube-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'youtube-modal-styles';
        document.head.appendChild(style);
    }

    const dialog = document.createElement('dialog');
    dialog.id = 'youtube-video-modal';
    dialog.innerHTML = `
        <div class="modal-header">
            <h3 class="modal-title"></h3>
            <button class="modal-close" aria-label="Close video">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="youtube-video-container"></div>
        </div>
    `;

    document.body.appendChild(dialog);

    // Add event listeners
    const closeBtn = dialog.querySelector('.modal-close');
    
    closeBtn.addEventListener('click', () => {
        dialog.close();
    });
    
    // Close on clicking outside (native dialog backdrop click)
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.close();
        }
    });
    
    // Handle dialog close event
    dialog.addEventListener('close', () => {
        const videoContainer = dialog.querySelector('.youtube-video-container');
        // Clear iframe to stop video
        videoContainer.innerHTML = '';
    });
}

function openVideoModal(videoId, channelTitle) {
    const dialog = document.getElementById('youtube-video-modal');
    const modalTitle = dialog.querySelector('.modal-title');
    const videoContainer = dialog.querySelector('.youtube-video-container');
    
    // Set title
    modalTitle.textContent = channelTitle;
    
    // Create iframe
    const iframe = document.createElement('iframe');
    iframe.width = "100%";
    iframe.height = "400";
    iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
    iframe.frameBorder = "0";
    iframe.allowFullscreen = true;
    iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
    
    // Clear previous content and add iframe
    videoContainer.innerHTML = '';
    videoContainer.appendChild(iframe);
    
    // Show modal using native dialog method
    dialog.showModal();
}

// Small helper: remove emoji characters from a string while preserving other characters.
function stripEmoji(str) {
    if (!str) return '';
    try {
        // Prefer Unicode property if supported
        return str.replace(/\p{Extended_Pictographic}/gu, '').replace(/\uFE0F/g, '').trim();
    } catch (e) {
        // Fallback for environments without Unicode property support
        return str.replace(/[\u2700-\u27BF]|[\uE000-\uF8FF]|[\u1F600-\u1F64F]|[\u1F300-\u1F5FF]|[\u1F680-\u1F6FF]|[\u1F1E6-\u1F1FF]|[\u1F900-\u1F9FF]|[\u1FA70-\u1FAFF]/g, '').replace(/\uFE0F/g, '').trim();
    }
}